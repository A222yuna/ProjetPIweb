"""
Face Recognition Web App
========================
Uses DeepFace (Facenet model) to:
  - Register faces from uploaded photos
  - Identify a new uploaded photo against all registered faces

Run:
    pip install flask deepface opencv-python-headless Pillow tf-keras
    python app.py
Then open: http://localhost:5000
"""

import os, json, shutil, uuid
os.environ["TF_CPP_MIN_LOG_LEVEL"] = "3"
os.environ["TF_ENABLE_ONEDNN_OPTS"] = "0"

from flask import Flask, request, jsonify, render_template, send_from_directory
from deepface import DeepFace
import numpy as np
import cv2
from PIL import Image
import io

app = Flask(__name__)

BASE_DIR      = os.path.dirname(__file__)
KNOWN_DIR     = os.path.join(BASE_DIR, "known_faces")   # one sub-folder per person
UPLOAD_DIR    = os.path.join(BASE_DIR, "uploads")
DB_JSON       = os.path.join(BASE_DIR, "faces_db.json")
MODEL_NAME    = "Facenet"          # fast + accurate; swap to "ArcFace" for higher accuracy
DETECTOR      = "opencv"           # fast detector; swap to "retinaface" for harder cases
DISTANCE_METRIC = "cosine"
THRESHOLD     = 0.40               # cosine distance — lower = stricter match

os.makedirs(KNOWN_DIR, exist_ok=True)
os.makedirs(UPLOAD_DIR, exist_ok=True)


# ── helpers ──────────────────────────────────────────────────────────────────

def load_db():
    if os.path.exists(DB_JSON):
        with open(DB_JSON) as f:
            return json.load(f)
    return {}   # { name: { "label": str, "photos": [path, ...], "embedding": [...] } }

def save_db(db):
    with open(DB_JSON, "w") as f:
        json.dump(db, f, indent=2)

def get_embedding(img_path):
    """Return mean embedding vector for image (list of floats)."""
    result = DeepFace.represent(
        img_path=img_path,
        model_name=MODEL_NAME,
        detector_backend=DETECTOR,
        enforce_detection=True,
    )
    # result is a list of dicts (one per face detected)
    return result[0]["embedding"]

def cosine_distance(a, b):
    a, b = np.array(a), np.array(b)
    return 1 - np.dot(a, b) / (np.linalg.norm(a) * np.linalg.norm(b) + 1e-10)

def save_upload(file_storage):
    """Save uploaded file, return its path."""
    ext = os.path.splitext(file_storage.filename)[-1].lower() or ".jpg"
    fname = f"{uuid.uuid4().hex}{ext}"
    path = os.path.join(UPLOAD_DIR, fname)
    file_storage.save(path)
    return path


# ── routes ───────────────────────────────────────────────────────────────────

@app.route("/")
def index():
    return render_template("index.html")

@app.route("/api/faces", methods=["GET"])
def list_faces():
    db = load_db()
    return jsonify([
        {"name": name, "label": info.get("label",""), "count": len(info.get("photos",[]))}
        for name, info in db.items()
    ])

@app.route("/api/register", methods=["POST"])
def register():
    name  = request.form.get("name","").strip()
    label = request.form.get("label","").strip()
    file  = request.files.get("photo")

    if not name or not file:
        return jsonify({"error": "Name and photo required"}), 400

    # Save photo
    img_path = save_upload(file)

    try:
        embedding = get_embedding(img_path)
    except Exception as e:
        os.remove(img_path)
        return jsonify({"error": f"Could not detect a face: {str(e)}"}), 422

    # Copy to known_faces/<name>/
    person_dir = os.path.join(KNOWN_DIR, name)
    os.makedirs(person_dir, exist_ok=True)
    dest = os.path.join(person_dir, os.path.basename(img_path))
    shutil.copy(img_path, dest)

    # Update DB
    db = load_db()
    if name not in db:
        db[name] = {"label": label, "photos": [], "embeddings": []}
    db[name]["photos"].append(dest)
    db[name]["embeddings"].append(embedding)
    if label:
        db[name]["label"] = label
    save_db(db)

    return jsonify({"success": True, "name": name, "total_photos": len(db[name]["photos"])})


@app.route("/api/identify", methods=["POST"])
def identify():
    file = request.files.get("photo")
    if not file:
        return jsonify({"error": "No photo provided"}), 400

    img_path = save_upload(file)

    try:
        query_emb = get_embedding(img_path)
    except Exception as e:
        os.remove(img_path)
        return jsonify({"error": f"Could not detect a face: {str(e)}"}), 422

    db = load_db()
    if not db:
        return jsonify({"error": "No faces registered yet. Please register first."}), 404

    # Compare against every registered person (mean of their embeddings)
    best_name  = None
    best_dist  = float("inf")
    results    = []

    for name, info in db.items():
        embeddings = info.get("embeddings", [])
        if not embeddings:
            continue
        # Mean embedding for this person
        mean_emb = np.mean(embeddings, axis=0).tolist()
        dist = cosine_distance(query_emb, mean_emb)
        conf = round(max(0, 1 - dist / THRESHOLD) * 100, 1)
        results.append({"name": name, "label": info.get("label",""), "distance": round(dist,4), "confidence": conf})
        if dist < best_dist:
            best_dist  = dist
            best_name  = name

    results.sort(key=lambda x: x["distance"])

    if best_dist <= THRESHOLD:
        match = next(r for r in results if r["name"] == best_name)
        return jsonify({
            "matched": True,
            "name": best_name,
            "label": match["label"],
            "confidence": match["confidence"],
            "distance": round(best_dist, 4),
            "all_results": results[:5],
        })
    else:
        return jsonify({
            "matched": False,
            "message": "No match found",
            "closest": results[0] if results else None,
            "all_results": results[:5],
        })


@app.route("/api/delete/<name>", methods=["DELETE"])
def delete_face(name):
    db = load_db()
    if name not in db:
        return jsonify({"error": "Not found"}), 404
    # Remove files
    person_dir = os.path.join(KNOWN_DIR, name)
    if os.path.exists(person_dir):
        shutil.rmtree(person_dir)
    del db[name]
    save_db(db)
    return jsonify({"success": True})


@app.route("/api/compare", methods=["POST"])
def compare_faces():
    file1 = request.files.get("photo1")  # Reference photo
    file2 = request.files.get("photo2")  # Live photo

    if not file1 or not file2:
        return jsonify({"error": "Both photos are required"}), 400

    path1 = save_upload(file1)
    path2 = save_upload(file2)

    try:
        result = DeepFace.verify(
            img1_path=path1,
            img2_path=path2,
            model_name=MODEL_NAME,
            detector_backend=DETECTOR,
            distance_metric=DISTANCE_METRIC,
            enforce_detection=True
        )

        os.remove(path1)
        os.remove(path2)

        return jsonify({
            "verified": result["verified"],
            "distance": round(result["distance"], 4),
            "threshold": THRESHOLD,
            "model": MODEL_NAME
        })
    except Exception as e:
        try:
            os.remove(path1)
            os.remove(path2)
        except:
            pass
        return jsonify({"error": f"Face comparison failed: {str(e)}"}), 422


@app.route("/api/clear", methods=["DELETE"])
def clear_all():
    db = load_db()
    for name in list(db.keys()):
        person_dir = os.path.join(KNOWN_DIR, name)
        if os.path.exists(person_dir):
            shutil.rmtree(person_dir)
    save_db({})
    return jsonify({"success": True})


if __name__ == "__main__":
    print("\n  Face Recognition App")
    print("  ─────────────────────────────────")
    print("  Open: http://localhost:5000")
    print("  Model:", MODEL_NAME)
    print("  Threshold (cosine):", THRESHOLD)
    print("─────────────────────────────────\n")
    app.run(debug=True, port=5000, host='0.0.0.0')

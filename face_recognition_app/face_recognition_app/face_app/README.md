# Face Recognition App

A local web app that registers faces and identifies them from uploaded photos.
Uses **DeepFace + Facenet** — a deep learning face embedding model.

## Quick Start

```bash
# 1. Install dependencies
pip install flask deepface opencv-python-headless Pillow tf-keras

# 2. Run the app
python app.py

# 3. Open in browser
http://localhost:5000
```

## How to use

### Register yourself
1. Click **Register** tab
2. Upload a clear frontal photo of your face
3. Type your name → click **Register Face**
4. ✅ Repeat with 2–3 more photos for better accuracy

### Identify a photo
1. Click **Identify** tab
2. Upload any photo of you (different from registered ones)
3. Click **Identify Face**
4. App shows: your name + confidence % + distance score

## How it works

```
Your photo → Face Detection (OpenCV) → Facenet Embedding (128-D vector)
                                              ↓
                                  Compare with all registered faces
                                  (cosine distance)
                                              ↓
                                  Match if distance < 0.40 threshold
```

- **Model**: Facenet (128-dimensional face embeddings)
- **Detector**: OpenCV (fast); change to `retinaface` in app.py for harder cases
- **Matching**: Cosine similarity on mean embedding per person
- **Threshold**: 0.40 cosine distance (tunable in app.py)

## Improve accuracy

| Tip | Why |
|-----|-----|
| Register 3–5 photos per person | App uses mean embedding — more photos = more robust |
| Use different lighting/angles  | Covers more variation |
| Frontal clear face, no blur    | Detector works best |
| Change `DETECTOR = "retinaface"` | More robust detection |
| Change `MODEL_NAME = "ArcFace"` | Higher accuracy model |

## File structure

```
face_app/
├── app.py              ← Flask backend
├── requirements.txt
├── templates/
│   └── index.html      ← Web UI
├── known_faces/        ← Registered face photos (auto-created)
│   └── YourName/
│       └── photo.jpg
├── uploads/            ← Temporary uploads (auto-created)
└── faces_db.json       ← Database of names + embeddings (auto-created)
```

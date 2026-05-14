# Face Recognition Integration

## Overview
This project integrates a Python Flask-based face recognition service using DeepFace (Facenet model) for psychologist identity verification before confirming appointments.

## Architecture

### Components
1. **Flask API** (`face_recognition_app/face_recognition_app/face_app/`)
   - DeepFace-based face recognition service
   - Endpoints: `/api/compare`, `/api/register`, `/api/identify`, etc.
   - Model: Facenet (128-dimensional embeddings)
   - Distance metric: Cosine similarity
   - Threshold: 0.40 (configurable in `app.py`)

2. **Symfony Controller** (`src/Controller/Psychologue/FaceVerificationController.php`)
   - Handles face verification workflow
   - Routes:
     - `GET /psychologue/face-verification/{appointmentId}` - Display verification page
     - `POST /psychologue/face-verification/{appointmentId}/check` - Verify face

3. **Frontend** (`templates/psychologue/face/verify.html.twig`)
   - Webcam capture interface
   - Sends live photo to Symfony backend
   - Displays verification results

## Setup

### Option 1: Docker Compose (Recommended)

1. Build and start all services:
```bash
docker-compose up -d --build
```

2. The Flask service will be available at `http://face_recognition:5000` (internal) or `http://localhost:5000` (external)

3. Update `.env` if needed:
```env
FACE_RECOGNITION_API_URL=http://face_recognition:5000
```

### Option 2: Manual Setup

1. Install Python dependencies:
```bash
cd face_recognition_app/face_recognition_app/face_app
pip install -r requirements.txt
```

2. Start the Flask app:
```bash
python app.py
```

3. The service will run on `http://localhost:5000`

4. Ensure `.env` points to the correct URL:
```env
FACE_RECOGNITION_API_URL=http://localhost:5000
```

## Configuration

### Environment Variables

**`.env`**:
```env
FACE_RECOGNITION_API_URL=http://localhost:5000
```

**`config/services.yaml`**:
```yaml
parameters:
    face_recognition_api_url: '%env(FACE_RECOGNITION_API_URL)%'
```

### Flask Configuration

Edit `face_recognition_app/face_recognition_app/face_app/app.py`:

```python
MODEL_NAME = "Facenet"          # Options: Facenet, ArcFace, VGG-Face, etc.
DETECTOR = "opencv"             # Options: opencv, retinaface, mtcnn, etc.
DISTANCE_METRIC = "cosine"      # Options: cosine, euclidean, euclidean_l2
THRESHOLD = 0.40                # Lower = stricter match
```

## Usage Flow

1. Psychologist attempts to confirm an appointment
2. If they have a profile photo, they're redirected to face verification
3. Webcam captures a live photo
4. Symfony sends both photos to Flask `/api/compare` endpoint
5. Flask returns verification result:
   ```json
   {
     "verified": true,
     "distance": 0.23,
     "threshold": 0.40,
     "model": "Facenet"
   }
   ```
6. If verified, appointment is confirmed; otherwise, user can retry

## API Endpoints

### `/api/compare` (POST)
Compare two photos for face verification.

**Request**:
- `photo1` (file): Reference photo
- `photo2` (file): Live photo

**Response**:
```json
{
  "verified": true,
  "distance": 0.23,
  "threshold": 0.40,
  "model": "Facenet"
}
```

### Other Endpoints
- `GET /api/faces` - List registered faces
- `POST /api/register` - Register a new face
- `POST /api/identify` - Identify a photo
- `DELETE /api/delete/<name>` - Remove a person
- `DELETE /api/clear` - Clear all faces

## Troubleshooting

### Flask service not responding
```bash
# Check if service is running
docker-compose ps

# View logs
docker-compose logs face_recognition

# Restart service
docker-compose restart face_recognition
```

### Face not detected
- Ensure good lighting
- Face should be clearly visible and frontal
- Try adjusting `DETECTOR` to `retinaface` for better detection

### Verification always fails
- Check threshold in `app.py` (increase if too strict)
- Ensure reference photo is clear and recent
- Check logs for distance values

### Connection refused
- Verify Flask service is running: `curl http://localhost:5000/api/faces`
- Check `FACE_RECOGNITION_API_URL` in `.env`
- Ensure firewall allows port 5000

## Security Notes

- Face verification is an additional security layer, not a replacement for authentication
- Profile photos should be stored securely
- Consider rate limiting on verification endpoints
- Use HTTPS in production
- Regularly update DeepFace and dependencies

## Performance

- First request may be slow (model loading)
- Subsequent requests: ~1-2 seconds per verification
- Consider warming up the service on startup
- For high traffic, consider caching embeddings

## Production Deployment

1. Use environment-specific `.env` files
2. Set `FACE_RECOGNITION_API_URL` to production Flask URL
3. Enable HTTPS for Flask service
4. Set `app.run(debug=False)` in `app.py`
5. Use a production WSGI server (gunicorn, uwsgi)
6. Monitor Flask service health
7. Set up proper logging and error tracking

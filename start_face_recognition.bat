@echo off
title Face Recognition Service
echo Starting Face Recognition Service...
echo.
cd /d "%~dp0face_recognition_app\face_recognition_app\face_app"
python app.py
echo.
echo Flask stopped. Press any key to close.
pause > nul

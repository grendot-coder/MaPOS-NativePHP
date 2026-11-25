@echo off
title MaPOS Database Switcher
cd /d "%~dp0"

echo ====================================================
echo  MaPOS Database Profile Switcher
echo ====================================================
echo  [1] Switch to Local SQLite (Offline Register Mode)
echo  [2] Switch to Supabase PostgreSQL (Cloud Multi-Device Mode)
echo ====================================================
set /p choice="Pilih mode database (1 atau 2): "

if "%choice%"=="1" (
    echo.
    echo Mengaktifkan profil SQLite...
    copy /y .env.sqlite .env >nul
    "C:\tools\php85\php.exe" artisan optimize:clear
    echo [SUKSES] MaPOS sekarang berjalan dengan database lokal SQLite!
    goto end
)

if "%choice%"=="2" (
    echo.
    echo Mengaktifkan profil Supabase (PostgreSQL)...
    copy /y .env.supabase .env >nul
    "C:\tools\php85\php.exe" artisan optimize:clear
    echo [SUKSES] MaPOS sekarang terhubung langsung ke Cloud Supabase!
    goto end
)

echo Pilihan tidak valid.
:end
echo.
pause

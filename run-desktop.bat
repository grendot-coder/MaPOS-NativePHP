@echo off
title MaPOS Desktop App (NativePHP)
cd /d "%~dp0"
set ELECTRON_RUN_AS_NODE=
echo ====================================================
echo  Memulai MaPOS Native Desktop App
echo ====================================================
php artisan native:run -D
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo [ERROR] Gagal menjalankan NativePHP. Mencoba menjalankan dengan instalasi ulang dependensi...
    php artisan native:run
)
pause
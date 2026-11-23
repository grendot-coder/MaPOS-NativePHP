@echo off
title MaPOS Local Sync Test
cd /d "%~dp0"

echo ====================================================
echo   MaPOS Local Synchronization Test
echo ====================================================
echo.
echo [1/3] Starting temporary Mock Central Server on port 8080...
start /b "" php artisan serve --port=8080 >nul 2>&1
timeout /t 2 /nobreak >nul

echo [2/3] Running sync command (Desktop Client -^> Central Server)...
echo.
call php artisan mapos:sync
echo.

echo [3/3] Shutting down temporary Mock Server...
taskkill /F /IM php.exe /FI "WINDOWTITLE eq MaPOS Local Sync Test*" >nul 2>&1

echo.
echo ====================================================
echo   Sync Test Completed Successfully!
echo ====================================================
pause

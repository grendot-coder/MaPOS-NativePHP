@echo off
setlocal
title Build MaPOS Inno Setup Installer
cd /d "%~dp0"

echo ====================================================
echo  [1/2] Building NativePHP Windows Application...
echo ====================================================
if not exist ".env" (
    echo [ERROR] .env file not found!
    pause
    exit /b 1
)

copy /y ".env" ".env.build.bak" >nul
powershell -NoProfile -Command "$c = Get-Content '.env' -Raw; $c = $c -replace '(?m)^APP_ENV=.*', 'APP_ENV=production' -replace '(?m)^APP_DEBUG=.*', 'APP_DEBUG=false' -replace '(?m)^DB_DATABASE=.*\r?\n?', ''; Set-Content -Path '.env' -Value $c -NoNewline"
call php artisan optimize:clear >nul 2>&1

call php artisan native:build
set "BUILD_ERR=%ERRORLEVEL%"

move /y ".env.build.bak" ".env" >nul
call php artisan optimize:clear >nul 2>&1

if not "%BUILD_ERR%"=="0" (
    echo [ERROR] NativePHP build failed!
    pause
    exit /b %BUILD_ERR%
)

echo ====================================================
echo  [2/2] Compiling Professional Inno Setup Installer...
echo ====================================================
if exist "%LOCALAPPDATA%\Programs\Inno Setup 6\ISCC.exe" (
    "%LOCALAPPDATA%\Programs\Inno Setup 6\ISCC.exe" installer.iss
) else if exist "C:\Program Files (x86)\Inno Setup 6\ISCC.exe" (
    "C:\Program Files (x86)\Inno Setup 6\ISCC.exe" installer.iss
) else if exist "C:\Program Files\Inno Setup 6\ISCC.exe" (
    "C:\Program Files\Inno Setup 6\ISCC.exe" installer.iss
) else (
    echo [ERROR] Inno Setup compiler (ISCC.exe) not found!
    pause
    exit /b 1
)

echo ====================================================
echo  SUCCESS! Your installer is in: dist-inno\
echo ====================================================
pause

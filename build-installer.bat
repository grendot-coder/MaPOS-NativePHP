@echo off
title Build MaPOS Inno Setup Installer
cd /d "%~dp0"

echo ====================================================
echo  [1/2] Building NativePHP Windows Application...
echo ====================================================
call php artisan native:build

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

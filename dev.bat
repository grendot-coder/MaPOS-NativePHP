@echo off
title MaPOS Dev Server
cd /d "%~dp0"
echo ====================================================
echo  Memulai MaPOS Workstation Dev Server
echo  (PHP Server + Vite Hot-Reload + Queue Worker)
echo ====================================================
composer run dev
pause

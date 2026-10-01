@echo off
cd /d "%~dp0"
echo Verification des modifications...
git status --short
git add -A
git diff --cached --quiet
if %errorlevel% neq 0 (
    echo Modifications detectees. Envoi vers GitHub...
    for /f "tokens=1-4 delims=/ " %%a in ("%date%") do set mydate=%%a-%%b-%%c
    for /f "tokens=1-2 delims=: " %%a in ("%time%") do set mytime=%%a:%%b
    git commit -m "Mise a jour automatique (%mydate% %mytime%)"
    git push origin main
    echo.
    echo Synchronisation terminee avec succes !
) else (
    echo Aucune modification detectee.
)
pause

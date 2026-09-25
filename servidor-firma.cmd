@echo off
REM Levanta el stub local del API Smart-Sign en http://127.0.0.1:8087
setlocal
set PHP=C:\php83\php.exe
set INI=C:\php83\php.ini
cd /d "%~dp0"
echo ==========================================================
echo   Smart-Sign STUB (PHP 8.3) -^> http://127.0.0.1:8087
echo   Endpoint: POST /api/signature/smart-sign
echo   Firma SIMULADA - solo para pruebas locales
echo   Ctrl+C para detener
echo ==========================================================
"%PHP%" -c "%INI%" -S 127.0.0.1:8087 tmp\smart-sign-stub\server.php
endlocal

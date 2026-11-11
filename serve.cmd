@echo off
title EmbryoCase - Laboratory Server
echo ==============================================================
echo             EMBRYOCASE LABORATORY PLATFORM SERVER            
echo   Embryo Data & Research Management System (PHP 8.4+ Laravel)
echo ==============================================================
echo Access URL: http://localhost:8000
echo Press Ctrl+C to terminate the laboratory server.
echo.
"%~dp0tools\php\php.exe" "%~dp0artisan" serve --port=8000
pause


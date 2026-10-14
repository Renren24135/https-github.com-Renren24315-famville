@echo off
REM Keeps booking-approval/void/cash-count emails sending in the background.
REM Without this running, those emails just sit queued and never go out —
REM the page itself will still be fast either way, since the request no
REM longer waits on the mail server.
REM
REM Double-click this file to start it, and leave the window open. To make
REM it start automatically with Windows, put a shortcut to this file in:
REM   shell:startup
cd /d "%~dp0"
php artisan queue:work --tries=3 --sleep=3
pause

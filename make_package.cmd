@echo off
setlocal
rem del \\DUNE4K\DuneSD\dune_plugin_logs\proiptv.log >nul 2>&1
del \\DUNE8K\android_fe7b_ac1b\dune_plugin_logs\proiptv.log >nul 2>&1

set /p VERSION=<build\version.txt
for /f "delims=" %%a in ('git log --oneline ^| find "" /v /c') do @set BUILD=%%a

php -f build\update.php %VERSION% %BUILD% %1

del dune_plugin_proiptv.zip >nul

pushd dune_plugin
7z a ..\dune_plugin_proiptv.zip >nul
popd

del dune_plugin\changelog.md >nul 2>&1
del dune_plugin\providers*.json >nul 2>&1

echo copy to Diskstation
copy /Y dune_plugin_proiptv.zip \\DISKSTATION\Downloads\ >nul
echo.

if '%1' == 'debug' goto :EOF

choice /T 5 /D N /M "Upload"
if ERRORLEVEL 2 goto :EOF

echo create GIT tag
git tag %VERSION%.%BUILD%

echo copy to Dropbox
copy /Y .\dune_plugin_proiptv.zip E:\Dropbox\Public\ >nul
copy /Y .\dune_plugin_proiptv.zip E:\Dropbox\Public\dune_plugin_proiptv.%VERSION%.%BUILD%.zip >nul
copy /Y .\build\providers_%VERSION%.json .\providers_%VERSION%.json >nul
echo.

set /p CREDS=<creds.txt
echo %CREDS%
"C:\Program Files (x86)\WinSCP\WinSCP.com" ^
  /log="%~dp0WinSCP.log" /ini=nul ^
  /command ^
    "open %CREDS%" ^
	"put update_proiptv.tar.gz" ^
	"put update_proiptv.xml" ^
	"cd ../config" ^
	"put providers_%VERSION%.json" ^
    "exit"

set WINSCP_RESULT=%ERRORLEVEL%
if %WINSCP_RESULT% equ 0 (
  echo Success
) else (
  echo Error
)

del .\providers_%VERSION%.json >nul

exit /b %WINSCP_RESULT%

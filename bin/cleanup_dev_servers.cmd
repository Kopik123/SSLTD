@echo off
setlocal EnableExtensions EnableDelayedExpansion

rem Kill stale local dev servers (primarily PHP built-in servers) that can
rem block ports during automated QA runs.
rem
rem We only target loopback listeners in common dev port ranges.

for /f "tokens=2,5" %%A in ('netstat -ano ^| findstr LISTENING ^| findstr "127.0.0.1:"') do (
  set "ADDR=%%A"
  set "PID=%%B"
  for /f "tokens=2 delims=:" %%P in ("!ADDR!") do (
    set "PORT=%%P"
    set /a PORTN=!PORT! 2>nul
    if not "!PORTN!"=="" (
      if !PORTN! geq 8000 if !PORTN! leq 9000 (
        echo [cleanup] Killing PID !PID! on 127.0.0.1:!PORTN!
        taskkill /PID !PID! /F >nul 2>nul
      )
      if !PORTN! geq 18000 if !PORTN! leq 18150 (
        echo [cleanup] Killing PID !PID! on 127.0.0.1:!PORTN!
        taskkill /PID !PID! /F >nul 2>nul
      )
    )
  )
)

endlocal
exit /b 0


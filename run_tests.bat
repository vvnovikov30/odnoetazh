@echo off
rem ============================================================================
rem  run_tests.bat - сценарные тесты сайта ОдноЭтажники.РФ
rem
rem  Использование:
rem      run_tests.bat              локальный прогон (сервер на 8000 поднимется сам)
rem      run_tests.bat local        то же самое, явно
rem      run_tests.bat prod         проверка боевого сайта (заявки НЕ отправляются)
rem      run_tests.bat local --no-color     любые ключи tests\test_scenario.py
rem
rem  Код возврата: 0 - всё зелёное, 1 - есть ошибки (удобно для CI).
rem ============================================================================
setlocal EnableExtensions EnableDelayedExpansion
chcp 65001 >nul

set "REPO_DIR=%~dp0"
if "%REPO_DIR:~-1%"=="\" set "REPO_DIR=%REPO_DIR:~0,-1%"
set "TEST_SCRIPT=%REPO_DIR%\tests\test_scenario.py"

rem --- 1. Цель и дополнительные ключи -----------------------------------------
set "TARGET=local"
set "EXTRA="
for %%A in (%*) do (
    if /I "%%~A"=="local" (
        set "TARGET=local"
    ) else if /I "%%~A"=="prod" (
        set "TARGET=prod"
    ) else (
        set "EXTRA=!EXTRA! %%A"
    )
)

rem --- 2. Python --------------------------------------------------------------
set "PY_CMD=python"
python --version >nul 2>&1
if not errorlevel 1 goto :python_ready
py -3 --version >nul 2>&1
if not errorlevel 1 (
    set "PY_CMD=py -3"
    goto :python_ready
)
goto :no_python

:python_ready
rem --- 3. Portable PHP (нужен только локальному прогону с --serve) ------------
if /I not "%TARGET%"=="local" goto :run

if exist "%REPO_DIR%\.tools\php\php.exe" goto :run
where php >nul 2>&1 && goto :run
if not exist "%REPO_DIR%\setup_portable_php.ps1" goto :run

echo [i] Portable PHP не найден: "%REPO_DIR%\.tools\php\php.exe"
echo [i] Запускаю установку setup_portable_php.ps1 ...
echo.
set "PS_EXE=powershell"
where pwsh >nul 2>&1 && set "PS_EXE=pwsh"
"%PS_EXE%" -NoProfile -ExecutionPolicy Bypass -File "%REPO_DIR%\setup_portable_php.ps1"
echo.

:run
rem --- 4. Прогон тестов -------------------------------------------------------
set "SERVE_ARG="
if /I "%TARGET%"=="local" set "SERVE_ARG=--serve"

echo ==========================================================
echo   Сценарные тесты: %TARGET%  (extras:%EXTRA%)
echo ==========================================================
echo.
%PY_CMD% "%TEST_SCRIPT%" --target %TARGET% %SERVE_ARG% %EXTRA%
set "RC=%ERRORLEVEL%"
echo.
if "%RC%"=="0" (
    echo [OK] Все проверки пройдены.
) else (
    echo [FAIL] Есть ошибки - подробности в отчёте выше.
)
echo.

if "%~1"=="" pause
exit /b %RC%

:no_python
echo [ERROR] Python не найден в PATH.
echo         Установите Python 3.10+ (python.org) и повторите запуск:
echo             run_tests.bat %TARGET%
echo.
exit /b 1

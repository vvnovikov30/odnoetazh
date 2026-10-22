@echo off
rem ============================================================================
rem  preview_both.bat - одновременный локальный предпросмотр ДВУХ версий сайта
rem  Сервер 1 (8000): текущий репозиторий
rem  Сервер 2 (8001): слепок сайта с хостинга
rem  PHP берётся portable из .tools\php (глобальная установка не требуется).
rem ============================================================================
setlocal EnableExtensions
chcp 65001 >nul

rem --- Каталоги ---------------------------------------------------------------
set "REPO_DIR=%~dp0"
if "%REPO_DIR:~-1%"=="\" set "REPO_DIR=%REPO_DIR:~0,-1%"

if not defined SNAPSHOT_DIR set "SNAPSHOT_DIR=D:\odnoetazhniki\2026-10-22_13_33_52"
if not defined REPO_PORT set "REPO_PORT=8000"
if not defined SNAPSHOT_PORT set "SNAPSHOT_PORT=8001"

set "PHP_EXE=%REPO_DIR%\.tools\php\php.exe"
set "SETUP_PS1=%REPO_DIR%\setup_portable_php.ps1"

rem --- 1. Portable PHP --------------------------------------------------------
set "PS_EXE=powershell"
where pwsh >nul 2>&1 && set "PS_EXE=pwsh"

if exist "%PHP_EXE%" goto :php_ready
if not exist "%SETUP_PS1%" goto :no_setup

echo [i] Portable PHP не найден: "%PHP_EXE%"
echo [i] Запускаю установку setup_portable_php.ps1 ...
echo.
"%PS_EXE%" -NoProfile -ExecutionPolicy Bypass -File "%SETUP_PS1%"
echo.

:php_ready
if not exist "%PHP_EXE%" goto :php_failed

rem --- 2. Проверка каталога слепка -------------------------------------------
if not exist "%SNAPSHOT_DIR%\" (
    echo [ERROR] Не найдена папка слепка с хостинга: "%SNAPSHOT_DIR%"
    echo         Задайте её переменной окружения SNAPSHOT_DIR перед запуском, например:
    echo             set "SNAPSHOT_DIR=D:\path\to\snapshot"
    echo             preview_both.bat
    echo.
    pause
    exit /b 1
)

rem --- 3. Проверка занятости портов (предупреждение) --------------------------
netstat -ano -p TCP | findstr /C:":%REPO_PORT% " >nul && (
    echo [WARN] Порт %REPO_PORT% уже кем-то занят - сервер 1 может не запуститься.
)
netstat -ano -p TCP | findstr /C:":%SNAPSHOT_PORT% " >nul && (
    echo [WARN] Порт %SNAPSHOT_PORT% уже кем-то занят - сервер 2 может не запуститься.
)

rem --- 4. Запуск двух встроенных PHP-серверов в отдельных окнах консоли -------
echo [i] Запускаю сервер 1: репозиторий "%REPO_DIR%" на порту %REPO_PORT%
start "PHP %REPO_PORT% - odnoetazh (repo)" /D "%REPO_DIR%" "%PHP_EXE%" -S 127.0.0.1:%REPO_PORT% -t "%REPO_DIR%"

echo [i] Запускаю сервер 2: слепок "%SNAPSHOT_DIR%" на порту %SNAPSHOT_PORT%
start "PHP %SNAPSHOT_PORT% - snapshot (hosting)" /D "%SNAPSHOT_DIR%" "%PHP_EXE%" -S 127.0.0.1:%SNAPSHOT_PORT% -t "%SNAPSHOT_DIR%"

rem --- 5. Информация для пользователя ----------------------------------------
echo.
echo ==========================================================
echo   Локальный предпросмотр двух версий сайта запущен
echo ==========================================================
echo   Версия из репозитория : http://127.0.0.1:%REPO_PORT%
echo   Слепок с хостинга    : http://127.0.0.1:%SNAPSHOT_PORT%
echo ----------------------------------------------------------
echo   Каждый сервер работает в своём окне консоли:
echo     "PHP %REPO_PORT% - odnoetazh (repo)"
echo     "PHP %SNAPSHOT_PORT% - snapshot (hosting)"
echo.
echo   Как остановить:
echo     - закройте окно консоли нужного сервера
echo       (или нажмите Ctrl+C в этом окне сервера);
echo     - это главное окно можно закрыть в любой момент,
echo       серверы продолжат работу.
echo ==========================================================
echo.
pause
exit /b 0

:no_setup
echo [ERROR] Не найден скрипт установки: "%SETUP_PS1%"
echo         Файл setup_portable_php.ps1 должен лежать рядом с preview_both.bat.
echo.
pause
exit /b 1

:php_failed
echo [ERROR] Portable PHP недоступен: "%PHP_EXE%"
echo         Установите его вручную:
echo             powershell -NoProfile -ExecutionPolicy Bypass -File "%SETUP_PS1%"
echo.
pause
exit /b 1

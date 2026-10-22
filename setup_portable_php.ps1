<#
.SYNOPSIS
    Разворачивает portable-сборку PHP для Windows x64 (Non-Thread-Safe) в .tools\php.

.DESCRIPTION
    Скрипт не требует прав администратора и не трогает системный PATH:
    всё складывается в служебную папку .tools\php внутри репозитория
    (она исключена из Git через .gitignore).

    Логика:
      1. Если .tools\php\php.exe уже существует - загрузка пропускается.
      2. Иначе скачивается официальный portable-архив PHP 8.2.* NTS x64
         (php-<version>-nts-Win32-vs16-x64.zip): сначала с официального
         зеркала windows.php.net, затем - с резервного зеркала на GitHub.
      3. Архив распаковывается в .tools\php\.
      4. php.ini-development копируется в php.ini.
      5. Временный zip-файл удаляется.

.PARAMETER InstallDir
    Каталог установки. По умолчанию: <корень репозитория>\.tools\php.

.PARAMETER PhpVersion
    Версия PHP для загрузки. По умолчанию: 8.2.35.

.PARAMETER TimeoutSec
    Таймаут одной попытки загрузки, секунды. По умолчанию: 600.

.PARAMETER ArchivePath
    Путь к уже скачанному вручную архиву php-*-nts-Win32-vs16-x64.zip.
    Если задан, загрузка из сети не выполняется (offline-режим).

.PARAMETER Force
    Переустановить PHP, даже если .tools\php\php.exe уже существует.

.EXAMPLE
    powershell -NoProfile -ExecutionPolicy Bypass -File .\setup_portable_php.ps1

.EXAMPLE
    .\setup_portable_php.ps1 -PhpVersion 8.2.35 -TimeoutSec 900 -Force
#>
[CmdletBinding()]
param(
    [string]$InstallDir,
    [string]$PhpVersion = '8.2.35',
    [int]$TimeoutSec = 600,
    [string]$ArchivePath,
    [switch]$Force
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'

# System.Net.Http (HttpClient) не загружается автоматически в Windows PowerShell 5.1
try {
    Add-Type -AssemblyName System.Net.Http -ErrorAction Stop
} catch {
    Write-Verbose "System.Net.Http недоступен: $($_.Exception.Message)"
}

if ([string]::IsNullOrWhiteSpace($InstallDir)) {
    $InstallDir = Join-Path $PSScriptRoot '.tools\php'
}
$InstallDir = [System.IO.Path]::GetFullPath($InstallDir)
$phpExe = Join-Path $InstallDir 'php.exe'

function Write-Step {
    param([string]$Message)
    Write-Host "==> $Message" -ForegroundColor Cyan
}

function Write-Note {
    param([string]$Message)
    Write-Host "    $Message" -ForegroundColor Gray
}

function Write-Warn {
    param([string]$Message)
    Write-Host "[WARN] $Message" -ForegroundColor Yellow
}

function Write-Fail {
    param([string]$Message)
    Write-Host "[ERROR] $Message" -ForegroundColor Red
}

function Show-PhpVersion {
    param([string]$PhpPath)
    Write-Step 'Проверка версии PHP'
    & $PhpPath -v
    if ($LASTEXITCODE -ne 0) {
        throw "php.exe не запускается (exit code $LASTEXITCODE)."
    }
}

function Test-ZipSignature {
    param([string]$Path)
    if (-not (Test-Path -LiteralPath $Path)) { return $false }
    if ((Get-Item -LiteralPath $Path).Length -lt 1024) { return $false }
    $stream = [System.IO.File]::OpenRead($Path)
    try {
        $first = $stream.ReadByte()
        $second = $stream.ReadByte()
        return ($first -eq 0x50 -and $second -eq 0x4B)  # 'PK'
    } finally {
        $stream.Dispose()
    }
}

function Start-FileDownload {
    param(
        [Parameter(Mandatory = $true)][string[]]$Urls,
        [Parameter(Mandatory = $true)][string]$Destination,
        [Parameter(Mandatory = $true)][int]$TimeoutSec
    )

    try {
        [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.SecurityProtocolType]::Tls12
    } catch {
        Write-Verbose "Не удалось принудительно включить TLS 1.2: $($_.Exception.Message)"
    }

    foreach ($url in $Urls) {
        $client = $null
        $response = $null
        try {
            Write-Step "Загрузка: $url"
            $handler = New-Object System.Net.Http.HttpClientHandler
            $client = New-Object System.Net.Http.HttpClient($handler)
            $client.Timeout = [TimeSpan]::FromSeconds($TimeoutSec)

            $response = $client.GetAsync(
                $url,
                [System.Net.Http.HttpCompletionOption]::ResponseHeadersRead
            ).GetAwaiter().GetResult()

            if (-not $response.IsSuccessStatusCode) {
                throw "сервер вернул HTTP $([int]$response.StatusCode) $($response.ReasonPhrase)"
            }

            $expected = $null
            if ($null -ne $response.Content.Headers.ContentLength) {
                $expected = [long]$response.Content.Headers.ContentLength
            }

            $inputStream = $response.Content.ReadAsStreamAsync().GetAwaiter().GetResult()
            $outputStream = [System.IO.File]::Create($Destination)
            try {
                $buffer = New-Object byte[] 81920
                $written = [long]0
                while ($true) {
                    $read = $inputStream.Read($buffer, 0, $buffer.Length)
                    if ($read -le 0) { break }
                    $outputStream.Write($buffer, 0, $read)
                    $written += $read
                }
            } finally {
                $outputStream.Dispose()
                $inputStream.Dispose()
            }

            if ($expected -and $written -ne $expected) {
                throw "размер файла не совпадает с Content-Length ($written из $expected байт)"
            }
            Write-Note ("получено {0:N1} МБ" -f ($written / 1MB))
            return
        } catch {
            Write-Warn "источник недоступен: $url"
            Write-Note "причина: $($_.Exception.Message)"
            if (Test-Path -LiteralPath $Destination) {
                Remove-Item -LiteralPath $Destination -Force -ErrorAction SilentlyContinue
            }
        } finally {
            if ($null -ne $response) { $response.Dispose() }
            if ($null -ne $client) { $client.Dispose() }
        }
    }

    throw 'Не удалось скачать архив PHP ни с одного источника (windows.php.net / GitHub). Проверьте доступ в интернет и повторите запуск.'
}

# --- 1. Уже установлено? -----------------------------------------------------
if ((Test-Path -LiteralPath $phpExe) -and -not $Force) {
    Write-Step "PHP уже установлен: $phpExe"
    Write-Note 'Загрузка не требуется. Для переустановки используйте -Force.'
    Show-PhpVersion -PhpPath $phpExe
    exit 0
}

# --- 2. Подготовка каталога --------------------------------------------------
Write-Step "Каталог установки: $InstallDir"
if (-not (Test-Path -LiteralPath $InstallDir)) {
    New-Item -ItemType Directory -Path $InstallDir -Force | Out-Null
}

$archiveName = "php-$PhpVersion-nts-Win32-vs16-x64.zip"
$branch = 'php' + ((($PhpVersion -split '\.')[0..1]) -join '.')
$urls = @(
    "https://windows.php.net/downloads/releases/archives/$archiveName",
    "https://windows.php.net/downloads/releases/$archiveName",
    "https://github.com/shivammathur/php-builder-windows/releases/download/$branch/$archiveName"
)

$tempZip = Join-Path ([System.IO.Path]::GetTempPath()) ("php-portable-{0}.zip" -f [guid]::NewGuid().ToString('N'))

$exitCode = 0
try {
    # --- 3. Получение архива -------------------------------------------------
    if ([string]::IsNullOrWhiteSpace($ArchivePath)) {
        Start-FileDownload -Urls $urls -Destination $tempZip -TimeoutSec $TimeoutSec
    } else {
        $ArchivePath = [System.IO.Path]::GetFullPath($ArchivePath)
        Write-Step "Используется локальный архив: $ArchivePath"
        if (-not (Test-Path -LiteralPath $ArchivePath)) {
            throw "Архив не найден: $ArchivePath"
        }
        Copy-Item -LiteralPath $ArchivePath -Destination $tempZip -Force
    }

    if (-not (Test-ZipSignature -Path $tempZip)) {
        throw "Скачанный файл не является ZIP-архивом: $tempZip"
    }

    # --- 4. Распаковка -------------------------------------------------------
    Write-Step "Распаковка в $InstallDir"
    Expand-Archive -LiteralPath $tempZip -DestinationPath $InstallDir -Force

    if (-not (Test-Path -LiteralPath $phpExe)) {
        throw "После распаковки не найден $phpExe"
    }

    # --- 5. php.ini ----------------------------------------------------------
    $iniDevelopment = Join-Path $InstallDir 'php.ini-development'
    $iniPath = Join-Path $InstallDir 'php.ini'
    if (Test-Path -LiteralPath $iniDevelopment) {
        Copy-Item -LiteralPath $iniDevelopment -Destination $iniPath -Force
        Write-Note 'php.ini-development -> php.ini'
    } else {
        Write-Warn 'php.ini-development не найден в архиве; php.ini не создан.'
    }

    Show-PhpVersion -PhpPath $phpExe

    Write-Step 'Готово. Portable PHP установлен.'
    Write-Note "php.exe : $phpExe"
    Write-Note "php.ini : $iniPath"
    Write-Note 'Запуск двух серверов предпросмотра: preview_both.bat'
} catch {
    Write-Fail $_.Exception.Message
    Write-Note 'Подсказка: скачайте архив php-*-nts-Win32-vs16-x64.zip вручную и запустите'
    Write-Note '           .\setup_portable_php.ps1 -ArchivePath <путь к архиву>'
    $exitCode = 1
} finally {
    # --- 6. Удаление временного zip -----------------------------------------
    if (Test-Path -LiteralPath $tempZip) {
        Remove-Item -LiteralPath $tempZip -Force -ErrorAction SilentlyContinue
        Write-Note 'Временный zip-файл удалён.'
    }
}

exit $exitCode

# PayU Payment Log Viewer
# Run this script to watch PayU-related logs in real-time
# Works for both local development and remote servers (via SSH)

param(
    [string]$LogPath = "storage\logs\laravel.log",
    [switch]$AllLogs,
    [switch]$ErrorsOnly,
    [switch]$Remote
)

Write-Host "=== PayU Payment Log Viewer ===" -ForegroundColor Green
Write-Host "Press Ctrl+C to stop" -ForegroundColor Yellow
Write-Host ""

if ($Remote) {
    Write-Host "For remote server, use SSH and run:" -ForegroundColor Cyan
    Write-Host "  tail -f storage/logs/laravel.log | grep -i 'PayU\|payment'" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Or connect via SSH first, then run this script on the server." -ForegroundColor Yellow
    exit
}

if (-not (Test-Path $LogPath)) {
    Write-Host "Log file not found: $LogPath" -ForegroundColor Red
    Write-Host ""
    Write-Host "Trying alternative paths..." -ForegroundColor Yellow
    
    $alternatives = @(
        "..\storage\logs\laravel.log",
        "..\..\storage\logs\laravel.log",
        "public\..\storage\logs\laravel.log"
    )
    
    $found = $false
    foreach ($alt in $alternatives) {
        if (Test-Path $alt) {
            $LogPath = $alt
            $found = $true
            Write-Host "Found log file at: $LogPath" -ForegroundColor Green
            break
        }
    }
    
    if (-not $found) {
        Write-Host "Could not find log file. Please specify the path:" -ForegroundColor Red
        Write-Host "  .\view-logs.ps1 -LogPath 'path\to\laravel.log'" -ForegroundColor Yellow
        exit
    }
}

Write-Host "Watching log file: $LogPath" -ForegroundColor Cyan
Write-Host ""

if ($ErrorsOnly) {
    Write-Host "Showing ERRORS only..." -ForegroundColor Red
    Get-Content $LogPath -Wait -Tail 50 | ForEach-Object {
        if ($_ -match "ERROR|Exception|Failed|failed") {
            Write-Host $_ -ForegroundColor Red
        }
    }
} elseif ($AllLogs) {
    Write-Host "Showing ALL logs..." -ForegroundColor Cyan
    Get-Content $LogPath -Wait -Tail 50 | ForEach-Object {
        if ($_ -match "ERROR|Exception|Failed|failed") {
            Write-Host $_ -ForegroundColor Red
        } elseif ($_ -match "Success|success|SUCCESS") {
            Write-Host $_ -ForegroundColor Green
        } elseif ($_ -match "Warning|WARNING") {
            Write-Host $_ -ForegroundColor Yellow
        } else {
            Write-Host $_ -ForegroundColor White
        }
    }
} else {
    Write-Host "Showing PayU/payment related logs only..." -ForegroundColor Cyan
    Write-Host "Use -AllLogs to see all logs, or -ErrorsOnly for errors only" -ForegroundColor Gray
    Write-Host ""
    
    # Tail the log file and filter for PayU entries
    Get-Content $LogPath -Wait -Tail 50 | ForEach-Object {
        if ($_ -match "PayU|payment|Payment|PAYU") {
            if ($_ -match "ERROR|Exception|Failed|failed") {
                Write-Host $_ -ForegroundColor Red
            } elseif ($_ -match "Success|success|SUCCESS") {
                Write-Host $_ -ForegroundColor Green
            } elseif ($_ -match "Warning|WARNING") {
                Write-Host $_ -ForegroundColor Yellow
            } else {
                Write-Host $_ -ForegroundColor White
            }
        }
    }
}


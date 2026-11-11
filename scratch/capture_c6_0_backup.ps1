# scratch/capture_c6_0_backup.ps1
# TopUpers Gate 2B — Phase C6.0 Fresh Backup Capture Script
$ErrorActionPreference = "Stop"

$workspaceRoot = "c:\xampp\htdocs\RechaX Official"
$timestamp     = Get-Date -Format "yyyyMMdd_HHmmss"
$backupSubdir  = "backup_phase_c6_0_$timestamp"
$backupDirPath = Join-Path (Join-Path $workspaceRoot "backups") $backupSubdir
$backupFileName = "db_rechax_official_c6_0_$timestamp.sql"
$backupFilePath = Join-Path $backupDirPath $backupFileName

$mysqldumpPath = "C:\xampp\mysql\bin\mysqldump.exe"
$targetDb      = "rechax_official"

Write-Host "======================================================================"
Write-Host "TopUpers Gate 2B - Phase C6.0: Fresh Database Backup Capture"
Write-Host "======================================================================"
Write-Host ("Timestamp:          " + $timestamp)
Write-Host ("Target Database:    " + $targetDb)
Write-Host ("Backup Destination: " + $backupFilePath)

# 1. Verify Destination Directory & Disk Space
if (-not (Test-Path $backupDirPath)) {
    New-Item -ItemType Directory -Path $backupDirPath -Force | Out-Null
    Write-Host "CHECK 1: Created backup directory."
} else {
    Write-Host "CHECK 1: Backup directory exists."
}

$driveLetter = (Get-Item $backupDirPath).PSDrive.Name
$freeSpaceBytes = (Get-PSDrive $driveLetter).Free
$freeSpaceGB = [math]::Round($freeSpaceBytes / 1GB, 2)
Write-Host ("CHECK 2: Available disk space on drive " + $driveLetter + ": " + $freeSpaceGB + " GB")
if ($freeSpaceGB -lt 1.0) {
    throw "CRITICAL: Insufficient disk space."
}

# 2. Verify mysqldump binary exists
if (-not (Test-Path $mysqldumpPath)) {
    throw "CRITICAL: mysqldump binary not found at $mysqldumpPath"
}
Write-Host ("CHECK 3: mysqldump binary verified: " + $mysqldumpPath)

# 3. Capture Backup using --result-file
Write-Host ""
Write-Host "EXECUTION: Running mysqldump..."
& $mysqldumpPath --host=localhost --user=root `
    --result-file="$backupFilePath" `
    --single-transaction `
    --quick `
    --routines `
    --triggers `
    --hex-blob `
    $targetDb

$dumpExitCode = $LASTEXITCODE
Write-Host ("CHECK 4: mysqldump native exit code: " + $dumpExitCode)
if ($dumpExitCode -ne 0) {
    throw ("mysqldump failed with exit code: " + $dumpExitCode)
}

# 4. Verify File Existence & Non-Zero Size
if (-not (Test-Path $backupFilePath)) {
    throw "CRITICAL: Backup file was not created on disk."
}

$fileItem = Get-Item $backupFilePath
$fileSize = $fileItem.Length
Write-Host ("CHECK 5: Backup file verified on disk. Size: " + $fileSize + " bytes")
if ($fileSize -le 0) {
    throw "CRITICAL: Backup file is empty (0 bytes)!"
}

# 5. Calculate SHA-256 Hash
$sha256 = (Get-FileHash -Path $backupFilePath -Algorithm SHA256).Hash
Write-Host ("CHECK 6: SHA-256 Calculated: " + $sha256)

# 6. Read-Only Validation of Dump Content
Write-Host ""
Write-Host "VALIDATION: Inspecting Dump Content Header and Tables..."
$headerLines = Get-Content -Path $backupFilePath -TotalCount 25
$isMariaDump = $false
foreach ($line in $headerLines) {
    if ($line -like "*MariaDB dump*") {
        $isMariaDump = $true
        Write-Host ("  Header signature: " + $line)
    }
}
if (-not $isMariaDump) {
    Write-Warning "MariaDB dump header signature not found in initial 25 lines."
}

# Search for CREATE TABLE orders, CREATE VIEW, and pending Order #32
$hasOrdersTable = $false
$hasOrder32 = $false
$baseTableCount = 0
$viewCount = 0

$lines = Get-Content -Path $backupFilePath
foreach ($line in $lines) {
    if ($line.Contains("CREATE TABLE ``orders``")) {
        $hasOrdersTable = $true
    }
    if ($line.StartsWith("CREATE TABLE ``")) {
        $baseTableCount++
    }
    if ($line.Contains("VIEW ``product_packages``")) {
        $viewCount++
    }
    if ($line.Contains("RX6ACE242D8221B13")) {
        $hasOrder32 = $true
    }
}

$ordersStr = if ($hasOrdersTable) { "YES" } else { "NO" }
$order32Str = if ($hasOrder32) { "VERIFIED PRESENT" } else { "MISSING" }

Write-Host ("  Total Base Tables in dump:              " + $baseTableCount + " (Expected: 42)")
Write-Host ("  Total Views in dump:                    " + $viewCount + " (Expected: 1)")
Write-Host ("  Table 'orders' present in dump:         " + $ordersStr)
Write-Host ("  Pending Order #32 (RX6ACE242D8221B13):  " + $order32Str)

if (-not $hasOrdersTable) {
    throw "Validation Error: Table 'orders' not found in dump!"
}
if (-not $hasOrder32) {
    throw "Validation Error: Pending Order #32 not found in dump!"
}

Write-Host ""
Write-Host "======================================================================"
Write-Host "SUCCESS: Fresh Database Backup Captured & Validated Cleanly!"
Write-Host "======================================================================"
Write-Host ("Database Name:   " + $targetDb)
Write-Host ("Timestamp:       " + $timestamp)
Write-Host ("Full File Path:  " + $backupFilePath)
Write-Host ("File Size:       " + $fileSize + " bytes")
Write-Host ("SHA-256 Hash:    " + $sha256)
Write-Host "======================================================================"

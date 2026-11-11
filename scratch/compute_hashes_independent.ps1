$path = "c:\xampp\htdocs\RechaX Official\backups\backup_gate2b_20261012_035539\db_gate2b_preflight_20261012_035539.sql"

Write-Host "File: $path"
Write-Host "File Exists: $(Test-Path $path)"

$item = Get-Item $path
Write-Host "Size: $($item.Length) bytes"
Write-Host "LastWriteTime: $($item.LastWriteTime)"
Write-Host "CreationTime: $($item.CreationTime)"

# Independent Calculation 1: PowerShell Get-FileHash cmdlet
$hash1 = (Get-FileHash -Path $path -Algorithm SHA256).Hash
Write-Host "Calculation 1 (Get-FileHash cmdlet): $hash1"

# Independent Calculation 2: certutil.exe
$certutilOutput = certutil -hashfile "$path" SHA256
$hash2 = ($certutilOutput | Where-Object { $_ -match '^[0-9a-fA-F]{64}$' }).Trim().ToUpper()
Write-Host "Calculation 2 (certutil.exe binary): $hash2"

# Independent Calculation 3: .NET Cryptography Stream
$stream = [System.IO.File]::OpenRead($path)
$sha = [System.Security.Cryptography.SHA256]::Create()
$hash3 = [BitConverter]::ToString($sha.ComputeHash($stream)).Replace("-", "").ToUpper()
$stream.Close()
Write-Host "Calculation 3 (.NET SHA256 Stream):   $hash3"

Write-Host "--------------------------------------------------"
Write-Host "Comparison between independent methods:"
Write-Host "Calc 1 == Calc 2: $($hash1 -eq $hash2)"
Write-Host "Calc 2 == Calc 3: $($hash2 -eq $hash3)"

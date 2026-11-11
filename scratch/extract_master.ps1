$id = '6972ff23-4464-4c9f-a59d-b0a694898c80'
$f = "C:\Users\TalhaX\.gemini\antigravity-ide\brain\$id\.system_generated\logs\transcript.jsonl"
$firstLine = (Get-Content $f -TotalCount 1) | ConvertFrom-Json
[System.IO.File]::WriteAllText("c:\xampp\htdocs\RechaX Official\scratch\master_prompt.txt", $firstLine.content)
Write-Host "Saved master prompt to scratch\master_prompt.txt. Length: $($firstLine.content.Length)"

# Also list artifacts in both 6972ff23 and 4f6b5f1c
$dirs = @('6972ff23-4464-4c9f-a59d-b0a694898c80', '4f6b5f1c-214b-490a-8405-2411942abe16')
foreach ($d in $dirs) {
    Write-Host "=== Artifacts in $d ==="
    Get-ChildItem -Path "C:\Users\TalhaX\.gemini\antigravity-ide\brain\$d" -Recurse | Where-Object { -not $_.PSIsContainer -and $_.FullName -notmatch '\.system_generated' } | Select-Object FullName, Length, LastWriteTime | Format-Table
}

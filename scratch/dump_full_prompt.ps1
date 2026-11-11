$id = '6972ff23-4464-4c9f-a59d-b0a694898c80'
$f = "C:\Users\TalhaX\.gemini\antigravity-ide\brain\$id\.system_generated\logs\transcript_full.jsonl"
if (-not (Test-Path $f)) {
    $f = "C:\Users\TalhaX\.gemini\antigravity-ide\brain\$id\.system_generated\logs\transcript.jsonl"
}
$firstLine = (Get-Content $f -TotalCount 1) | ConvertFrom-Json
[System.IO.File]::WriteAllText("c:\xampp\htdocs\RechaX Official\scratch\full_master_prompt.md", $firstLine.content, [System.Text.Encoding]::UTF8)
Write-Host "Written full prompt. Length: $($firstLine.content.Length)"

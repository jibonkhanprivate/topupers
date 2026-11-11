$transcriptPath = "C:\Users\TalhaX\.gemini\antigravity-ide\brain\4f6b5f1c-214b-490a-8405-2411942abe16\.system_generated\logs\transcript_full.jsonl"
$lines = Get-Content $transcriptPath

Write-Host "=== LINE 242 (STEP 245) ==="
$obj242 = $lines[242] | ConvertFrom-Json
Write-Host $obj242.content

Write-Host "=== LINE 243 (STEP 246) ==="
$obj243 = $lines[243] | ConvertFrom-Json
Write-Host $obj243.content

$id = '4f6b5f1c-214b-490a-8405-2411942abe16'
$f = "C:\Users\TalhaX\.gemini\antigravity-ide\brain\$id\.system_generated\logs\transcript_full.jsonl"
if (-not (Test-Path $f)) {
    $f = "C:\Users\TalhaX\.gemini\antigravity-ide\brain\$id\.system_generated\logs\transcript.jsonl"
}
$lines = Get-Content $f
$line244 = $lines[244] # Index 244 is step 247
$line248 = $lines[248] # Index 248 is step 250

[System.IO.File]::WriteAllText("c:\xampp\htdocs\RechaX Official\scratch\step247_user.txt", $line244, [System.Text.Encoding]::UTF8)
[System.IO.File]::WriteAllText("c:\xampp\htdocs\RechaX Official\scratch\step250_model.txt", $line248, [System.Text.Encoding]::UTF8)

Write-Host "Saved step 247 and 250 to scratch."

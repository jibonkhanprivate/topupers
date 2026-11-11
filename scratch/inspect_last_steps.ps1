$id = '4f6b5f1c-214b-490a-8405-2411942abe16'
$f = "C:\Users\TalhaX\.gemini\antigravity-ide\brain\$id\.system_generated\logs\transcript_full.jsonl"
if (-not (Test-Path $f)) {
    $f = "C:\Users\TalhaX\.gemini\antigravity-ide\brain\$id\.system_generated\logs\transcript.jsonl"
}

$lines = Get-Content $f
Write-Host "Total lines: $($lines.Count)"

# Let's inspect the last 10 lines
for ($i = [Math]::Max(0, $lines.Count - 10); $i -lt $lines.Count; $i++) {
    $obj = $lines[$i] | ConvertFrom-Json
    Write-Host "----------------------------------------------------"
    Write-Host "Index: $i | Step: $($obj.step_index) | Type: $($obj.type) | Source: $($obj.source)"
    if ($obj.content) {
        $preview = $obj.content
        if ($preview.Length -gt 400) { $preview = $preview.Substring(0, 400) + "... [TRUNCATED]" }
        Write-Host "Content preview: $preview"
    }
}

$transcriptPath = "C:\Users\TalhaX\.gemini\antigravity-ide\brain\4f6b5f1c-214b-490a-8405-2411942abe16\.system_generated\logs\transcript_full.jsonl"
$lines = Get-Content $transcriptPath

Write-Host "Searching transcript for 372A771..."
for ($idx = 0; $idx -lt $lines.Count; $idx++) {
    if ($lines[$idx] -match '372A771') {
        Write-Host "Match at line index: $idx"
        $sub = $lines[$idx]
        $matchPos = $sub.IndexOf('372A771')
        $start = [Math]::Max(0, $matchPos - 50)
        $len = [Math]::Min(250, $sub.Length - $start)
        Write-Host ($sub.Substring($start, $len))
    }
}

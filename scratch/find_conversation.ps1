$brainDir = "C:\Users\TalhaX\.gemini\antigravity-ide\brain"
$dirs = Get-ChildItem -Path $brainDir -Directory | Sort-Object LastWriteTime -Descending | Select-Object -First 6

foreach ($dir in $dirs) {
    $transcript = Join-Path $dir.FullName ".system_generated\logs\transcript.jsonl"
    if (Test-Path $transcript) {
        $lines = Get-Content $transcript
        Write-Host "=========================================="
        Write-Host "ID: $($dir.Name) | LastWriteTime: $($dir.LastWriteTime) | Lines: $($lines.Count)"
        $userInputs = $lines | Where-Object { $_ -match '"type":"USER_INPUT"' }
        Write-Host "Total user inputs: $($userInputs.Count)"
        if ($userInputs.Count -gt 0) {
            $first = $userInputs[0]
            Write-Host "FIRST USER INPUT:"
            Write-Host $first.Substring(0, [Math]::Min(500, $first.Length))
            if ($userInputs.Count -gt 1) {
                $last = $userInputs[-1]
                Write-Host "LAST USER INPUT:"
                Write-Host $last.Substring(0, [Math]::Min(500, $last.Length))
            }
        }
    }
}

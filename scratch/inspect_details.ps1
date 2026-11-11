$targetIds = @(
    '6972ff23-4464-4c9f-a59d-b0a694898c80',
    '4f6b5f1c-214b-490a-8405-2411942abe16',
    '0f6c4d97-3d0a-436d-a026-fac3079e8da6',
    '7daf241b-027b-4624-835a-2e50ae1f5b4c'
)

foreach ($id in $targetIds) {
    $f = "C:\Users\TalhaX\.gemini\antigravity-ide\brain\$id\.system_generated\logs\transcript.jsonl"
    Write-Host "================================================================"
    Write-Host "CONVERSATION ID: $id"
    if (Test-Path $f) {
        $lines = Get-Content $f
        Write-Host "Total lines: $($lines.Count)"
        $userInputs = @()
        foreach ($line in $lines) {
            if ($line -match '"type":"USER_INPUT"') {
                $userInputs += $line
            }
        }
        Write-Host "Total user messages: $($userInputs.Count)"
        for ($i = 0; $i -lt $userInputs.Count; $i++) {
            $parsed = $userInputs[$i] | ConvertFrom-Json
            Write-Host "--- User Message #$($i+1) (Step $($parsed.step_index)) ---"
            $content = $parsed.content
            if ($content.Length -gt 300) {
                Write-Host ($content.Substring(0, 300) + "... [TRUNCATED]")
            } else {
                Write-Host $content
            }
        }
        # Also let's inspect the very last line of transcript to see what the agent/system was doing
        if ($lines.Count -gt 0) {
            $lastLine = $lines[-1] | ConvertFrom-Json
            Write-Host "--- Last Step in Conversation: Type = $($lastLine.type), Status = $($lastLine.status) ---"
        }
    } else {
        Write-Host "File not found: $f"
    }
}

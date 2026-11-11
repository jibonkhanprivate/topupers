$brainDir = "C:\Users\TalhaX\.gemini\antigravity-ide\brain"
$dirs = Get-ChildItem -Path $brainDir -Directory | Sort-Object LastWriteTime -Descending | Select-Object -First 25

foreach ($d in $dirs) {
    $f = Join-Path $d.FullName ".system_generated\logs\transcript.jsonl"
    if (Test-Path $f) {
        $lines = Get-Content $f
        foreach ($line in $lines) {
            try {
                $obj = $line | ConvertFrom-Json
                if ($obj.type -eq "USER_INPUT") {
                    $c = $obj.content
                    if ($c.Length -gt 200) { $c = $c.Substring(0, 200) }
                    Write-Host "[$($d.Name)] USER: $c"
                }
            } catch {}
        }
    }
}

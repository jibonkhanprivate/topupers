$brainDir = "C:\Users\TalhaX\.gemini\antigravity-ide\brain"
$files = Get-ChildItem -Path $brainDir -Recurse -Filter "transcript*.jsonl"

Write-Host "Searching all transcripts for 143D3F1..."
foreach ($f in $files) {
    $matches = Select-String -Path $f.FullName -Pattern "143D3F1"
    if ($matches) {
        Write-Host "Found in: $($f.FullName)"
        foreach ($m in $matches) {
            Write-Host "Line $($m.LineNumber): $($m.Line.Substring(0, [Math]::Min(300, $m.Line.Length)))"
        }
    }
}
Write-Host "Done search."

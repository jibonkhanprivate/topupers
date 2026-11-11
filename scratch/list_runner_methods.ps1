$lines = Get-Content 'database\migrate_v4_gate2b_currency_binance.php'
for ($i = 0; $i -lt $lines.Count; $i++) {
    if ($lines[$i] -match 'function ') {
        Write-Host "Line $($i+1): $($lines[$i].Trim())"
    }
}

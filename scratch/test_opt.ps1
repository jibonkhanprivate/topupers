Add-Type -AssemblyName System.Drawing
$bmp = [System.Drawing.Bitmap]::FromFile("$PSScriptRoot/../assets/img/banners/1788179704_Honor of Kings2.png")
$codec = [System.Drawing.Imaging.ImageCodecInfo]::GetImageEncoders() | Where-Object { $_.MimeType -eq 'image/jpeg' }
$encoderParams = New-Object System.Drawing.Imaging.EncoderParameters(1)
$encoderParams.Param[0] = New-Object System.Drawing.Imaging.EncoderParameter([System.Drawing.Imaging.Encoder]::Quality, [long]85)
$bmp.Save("$PSScriptRoot/test_opt.jpg", $codec, $encoderParams)
$bmp.Dispose()
$orig = (Get-Item "$PSScriptRoot/../assets/img/banners/1788179704_Honor of Kings2.png").Length
$opt = (Get-Item "$PSScriptRoot/test_opt.jpg").Length
Write-Host "Original: $([math]::Round($orig/1KB, 1)) KB"
Write-Host "Optimized: $([math]::Round($opt/1KB, 1)) KB"
Write-Host "Reduction: $([math]::Round((1 - $opt/$orig)*100, 1))%"

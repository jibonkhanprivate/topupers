Add-Type -AssemblyName System.Drawing

function Resize-And-Save($srcPath, $dstPath, $maxWidth, $quality) {
    $img = [System.Drawing.Image]::FromFile($srcPath)
    $origWidth = $img.Width
    $origHeight = $img.Height

    if ($origWidth -gt $maxWidth) {
        $newWidth = $maxWidth
        $newHeight = [int][math]::Round(($origHeight * $maxWidth) / $origWidth)
    } else {
        $newWidth = $origWidth
        $newHeight = $origHeight
    }

    $destRect = New-Object System.Drawing.Rectangle(0, 0, $newWidth, $newHeight)
    $destImage = New-Object System.Drawing.Bitmap($newWidth, $newHeight, [System.Drawing.Imaging.PixelFormat]::Format24bppRgb)
    $destImage.SetResolution($img.HorizontalResolution, $img.VerticalResolution)

    $graphics = [System.Drawing.Graphics]::FromImage($destImage)
    $graphics.CompositingMode = [System.Drawing.Drawing2D.CompositingMode]::SourceCopy
    $graphics.CompositingQuality = [System.Drawing.Drawing2D.CompositingQuality]::HighQuality
    $graphics.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
    $graphics.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality
    $graphics.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality

    $graphics.Clear([System.Drawing.Color]::White)
    $graphics.DrawImage($img, $destRect, 0, 0, $origWidth, $origHeight, [System.Drawing.GraphicsUnit]::Pixel)
    $graphics.Dispose()
    $img.Dispose()

    $codec = [System.Drawing.Imaging.ImageCodecInfo]::GetImageEncoders() | Where-Object { $_.MimeType -eq 'image/jpeg' }
    $encoderParams = New-Object System.Drawing.Imaging.EncoderParameters(1)
    $encoderParams.Param[0] = New-Object System.Drawing.Imaging.EncoderParameter([System.Drawing.Imaging.Encoder]::Quality, [long]$quality)

    $destImage.Save($dstPath, $codec, $encoderParams)
    $destImage.Dispose()

    $finalSize = (Get-Item $dstPath).Length
    Write-Host "  -> Saved: $(Split-Path $dstPath -Leaf) (${newWidth}x${newHeight}, $([math]::Round($finalSize/1KB, 1)) KB)"
}

$bannerDir = "$PSScriptRoot/../assets/img/banners"
$banners = Get-ChildItem -Path $bannerDir -File | Where-Object { $_.Name -notmatch "-opt|-tablet|-mobile" }

foreach ($b in $banners) {
    Write-Host "Optimizing: $($b.Name) ($([math]::Round($b.Length/1KB, 1)) KB)"
    $baseName = [System.IO.Path]::GetFileNameWithoutExtension($b.FullName)
    
    # 1. Desktop optimized
    $optPath = Join-Path $bannerDir "${baseName}-opt.jpg"
    Resize-And-Save $b.FullName $optPath 1600 85

    # 2. Tablet optimized
    $tabletPath = Join-Path $bannerDir "${baseName}-tablet.jpg"
    Resize-And-Save $b.FullName $tabletPath 960 85

    # 3. Mobile optimized
    $mobilePath = Join-Path $bannerDir "${baseName}-mobile.jpg"
    Resize-And-Save $b.FullName $mobilePath 540 85
}
Write-Host "All responsive banner variants generated successfully."

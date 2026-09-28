param(
    [string]$ModelsPath = (Join-Path $PSScriptRoot '..\..\storage\app\private\ocr-models'),
    [string]$SourcePath = ''
)
$ErrorActionPreference = 'Stop'    
$models = @{
    vie = '79DF64CAF7BCFB2A27DF5042ECB6121E196EADA34DA774956995747636D5BFA1'
    eng = '7D4322BD2A7749724879683FC3912CB542F19906C83BCC1A52132556427170B2'
}

if ($SourcePath) {
    foreach ($lang in $models.Keys) {
        $source = Join-Path $SourcePath "$lang.traineddata"
        if (-not (Test-Path -LiteralPath $source -PathType Leaf)) {
            throw "Missing OCR model in source folder: $source"
        }
        if ((Get-FileHash -LiteralPath $source -Algorithm SHA256).Hash -ne $models[$lang]) {
            throw "Checksum mismatch: $lang. Source file was not copied."
        }
    }
}

New-Item -ItemType Directory -Force -Path $ModelsPath | Out-Null
foreach ($lang in $models.Keys) {
    $destination = Join-Path $ModelsPath "$lang.traineddata"
    if ((Test-Path -LiteralPath $destination) -and (Get-FileHash -LiteralPath $destination -Algorithm SHA256).Hash -eq $models[$lang]) { continue }
    $staging = "$destination.download"
    if ($SourcePath) {
        Copy-Item -LiteralPath (Join-Path $SourcePath "$lang.traineddata") -Destination $staging -Force
    } else {
        Invoke-WebRequest -Uri "https://raw.githubusercontent.com/tesseract-ocr/tessdata_fast/main/$lang.traineddata" -OutFile $staging
    }
    if ((Get-FileHash -LiteralPath $staging -Algorithm SHA256).Hash -ne $models[$lang]) {
        Remove-Item -LiteralPath $staging -Force -ErrorAction SilentlyContinue
        throw "Checksum mismatch: $lang. Model was not installed."
    }
    Move-Item -LiteralPath $staging -Destination $destination -Force
}
Write-Host "OCR language models ready: $ModelsPath"
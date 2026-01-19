param(
    [Parameter(Position=0)]
    [string]$QuickSelect = "",
    [string]$PluginsPath = "C:\Users\David\Local Sites\unity-dev\app\public\wp-content\plugins",
    [string[]]$PluginNames = @("unity", "integrity", "tsml-for-unity", "amber", "trumpet"),
    [string]$OutputPath = (Get-Location).Path,
    [string[]]$ExcludeFolders = @("obj", "bin", ".git", "vendor", "node_modules")
)

# Handle quick select shortcuts
if (-not [string]::IsNullOrWhiteSpace($QuickSelect)) {
    switch ($QuickSelect.ToLower()) {
        "ui" { $PluginNames = @("unity", "integrity") }
        "u" { $PluginNames = @("unity") }
        "i" { $PluginNames = @("integrity") }
        "t" { $PluginNames = @("tsml-for-unity") }
        "a" { $PluginNames = @("amber") }
        "tr" { $PluginNames = @("trumpet") }
        "uit" { $PluginNames = @("unity", "integrity", "tsml-for-unity") }
        "all" { $PluginNames = @("unity", "integrity", "tsml-for-unity", "amber", "trumpet") }
        default { 
            Write-Host "Unknown quick select: $QuickSelect" -ForegroundColor Red
            Write-Host "Valid options: ui, u, i, t, a, tr, uit, all" -ForegroundColor Yellow
            exit
        }
    }
}

# Ensure output directory exists
if (-not (Test-Path $OutputPath)) {
    New-Item -ItemType Directory -Path $OutputPath -Force | Out-Null
}

Write-Host "Plugin Zipper Script" -ForegroundColor Cyan
Write-Host "===================" -ForegroundColor Cyan
Write-Host "Plugins Path: $PluginsPath" -ForegroundColor Yellow
Write-Host "Output Path: $OutputPath" -ForegroundColor Yellow
Write-Host "Plugins to zip: $($PluginNames -join ', ')" -ForegroundColor Green
Write-Host "Excluding folders: $($ExcludeFolders -join ', ')" -ForegroundColor Yellow
Write-Host ""

foreach ($pluginName in $PluginNames) {
    $pluginPath = Join-Path $PluginsPath $pluginName
    
    # Check if plugin folder exists
    if (-not (Test-Path $pluginPath)) {
        Write-Host "WARNING: Plugin '$pluginName' not found at $pluginPath" -ForegroundColor Red
        continue
    }
    
    # Create zip file name (same as plugin name)
    $zipFileName = "$pluginName.zip"
    $zipFilePath = Join-Path $OutputPath $zipFileName
    
    # Delete existing zip file if it exists
    if (Test-Path $zipFilePath) {
        Write-Host "Deleting existing: $zipFileName" -ForegroundColor Yellow
        Remove-Item -Path $zipFilePath -Force
    }
    
    Write-Host "Processing: $pluginName" -ForegroundColor Green
    
    try {
        # Get all items recursively, excluding specified folders
        $itemsToZip = Get-ChildItem -Path $pluginPath -Recurse -File | Where-Object {
            $filePath = $_.FullName
            $exclude = $false
            
            foreach ($excludeFolder in $ExcludeFolders) {
                # Check if the file path contains the excluded folder
                if ($filePath -match "\\$excludeFolder\\") {
                    $exclude = $true
                    break
                }
            }
            
            -not $exclude
        }
        
        # Create a temporary directory to stage files
        $tempDir = Join-Path $env:TEMP "PluginZip_$pluginName"
        if (Test-Path $tempDir) {
            Remove-Item -Path $tempDir -Recurse -Force
        }
        New-Item -ItemType Directory -Path $tempDir -Force | Out-Null
        
        $pluginTempDir = Join-Path $tempDir $pluginName
        New-Item -ItemType Directory -Path $pluginTempDir -Force | Out-Null
        
        # Copy files to temp directory maintaining structure
        foreach ($item in $itemsToZip) {
            $relativePath = $item.FullName.Substring($pluginPath.Length + 1)
            $destPath = Join-Path $pluginTempDir $relativePath
            $destDir = Split-Path $destPath -Parent
            
            if (-not (Test-Path $destDir)) {
                New-Item -ItemType Directory -Path $destDir -Force | Out-Null
            }
            
            Copy-Item -Path $item.FullName -Destination $destPath -Force
        }
        
        # Create the zip file
        Compress-Archive -Path $pluginTempDir -DestinationPath $zipFilePath -Force
        
        # Clean up temp directory
        Remove-Item -Path $tempDir -Recurse -Force
        
        $zipSize = (Get-Item $zipFilePath).Length / 1MB
        Write-Host "  [OK] Created: $zipFileName ($([math]::Round($zipSize, 2)) MB)" -ForegroundColor Green
        Write-Host "  [OK] Files included: $($itemsToZip.Count)" -ForegroundColor Gray
        
    }
    catch {
        Write-Host "  [ERROR] Error creating zip for '$pluginName': $($_.Exception.Message)" -ForegroundColor Red
    }
    
    Write-Host ""
}

Write-Host "Zipping complete!" -ForegroundColor Cyan
Write-Host "Zip files saved to: $OutputPath" -ForegroundColor Yellow
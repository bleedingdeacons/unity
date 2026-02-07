param(
    [Parameter(Position=0)]
    [string]$QuickSelect = "",
    [string]$PluginsPath = "C:\Users\David\Local Sites\unity-dev\app\public\wp-content\plugins",
    [string[]]$PluginNames = @("unity", "integrity", "tsml-for-unity", "amber", "trumpet", "reconcile", "scrutiny"),
    [string]$OutputPath = (Get-Location).Path,
    [string[]]$ExcludeFolders = @("obj", "bin", ".git", ".idea", "vendor", "node_modules", "build", "utils")
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
        "sc" { $PluginNames = @("scrutiny") }
		"rc" { $PluginNames = @("reconcile") }
        "uta" { $PluginNames = @("unity", "tsml-for-unity", "amber") }
        "uit" { $PluginNames = @("unity", "integrity", "tsml-for-unity") }
        "all" { $PluginNames = @("unity", "integrity", "tsml-for-unity", "amber", "trumpet", "reconcile", "scrutiny") }
        default {
            Write-Host "Unknown quick select: $QuickSelect" -ForegroundColor Red
            Write-Host "Valid options: ui, u, i, t, a, tr, se, sc, uit, all" -ForegroundColor Yellow
            exit
        }
    }
}

# Ensure output directory exists
if (-not (Test-Path $OutputPath)) {
    New-Item -ItemType Directory -Path $OutputPath -Force | Out-Null
}

Write-Host "Plugin Zipper Script (Cross-Platform Compatible)" -ForegroundColor Cyan
Write-Host "=================================================" -ForegroundColor Cyan
Write-Host "Plugins Path: $PluginsPath" -ForegroundColor Yellow
Write-Host "Output Path: $OutputPath" -ForegroundColor Yellow
Write-Host "Plugins to zip: $($PluginNames -join ', ')" -ForegroundColor Green
Write-Host "Excluding folders: $($ExcludeFolders -join ', ')" -ForegroundColor Yellow
Write-Host ""

# Load System.IO.Compression for cross-platform zip creation
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

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
                # Use cross-platform path separator check
                $separator = [System.IO.Path]::DirectorySeparatorChar
                if ($filePath -match "$([regex]::Escape($separator))$excludeFolder$([regex]::Escape($separator))") {
                    $exclude = $true
                    break
                }
            }

            -not $exclude
        }

        # CROSS-PLATFORM ZIP CREATION
        # Create zip archive using .NET ZipFile class for proper path handling
        $zipArchive = [System.IO.Compression.ZipFile]::Open($zipFilePath, [System.IO.Compression.ZipArchiveMode]::Create)

        $fileCount = 0
        foreach ($item in $itemsToZip) {
            # Calculate relative path from plugin root
            $relativePath = $item.FullName.Substring($pluginPath.Length + 1)

            # CRITICAL FIX: Convert Windows backslashes to forward slashes for cross-platform compatibility
            # This ensures the zip works on both Windows and Linux
            $zipEntryName = $relativePath.Replace('\', '/')

            # Add file to zip with forward-slash path
            $zipEntry = $zipArchive.CreateEntry($zipEntryName, [System.IO.Compression.CompressionLevel]::Optimal)

            # Copy file content
            $entryStream = $zipEntry.Open()
            $fileStream = [System.IO.File]::OpenRead($item.FullName)
            $fileStream.CopyTo($entryStream)
            $fileStream.Close()
            $entryStream.Close()

            $fileCount++
        }

        # Close the archive
        $zipArchive.Dispose()

        $zipSize = (Get-Item $zipFilePath).Length / 1MB
        Write-Host "  [OK] Created: $zipFileName ($([math]::Round($zipSize, 2)) MB)" -ForegroundColor Green
        Write-Host "  [OK] Files included: $fileCount" -ForegroundColor Gray
        Write-Host "  [OK] Cross-platform compatible (forward slashes)" -ForegroundColor Cyan

    }
    catch {
        Write-Host "  [ERROR] Error creating zip for '$pluginName': $($_.Exception.Message)" -ForegroundColor Red

        # Clean up partial zip if it exists
        if (Test-Path $zipFilePath) {
            Remove-Item -Path $zipFilePath -Force -ErrorAction SilentlyContinue
        }
    }

    Write-Host ""
}

Write-Host "Zipping complete!" -ForegroundColor Cyan
Write-Host "Zip files saved to: $OutputPath" -ForegroundColor Yellow
Write-Host ""
Write-Host "These zip files will work on:" -ForegroundColor Green
Write-Host "  - Windows WordPress installations" -ForegroundColor Gray
Write-Host "  - Linux WordPress installations" -ForegroundColor Gray
Write-Host "  - WordPress.org plugin directory" -ForegroundColor Gray
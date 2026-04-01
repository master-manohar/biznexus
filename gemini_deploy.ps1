$FTP_URL = "ftp://46.28.45.161"
$FTP_USER = "u175452495.biznexus.in:REPLACE_WITH_FTP_PASSWORD"
$CURL = "C:\Windows\System32\curl.exe"

# Folders to upload recursively
$FOLDERS = @(
    "admin", "agent", "api", "assets", "auth", "business", 
    "dashboard", "groups", "includes", "kyc", "leads", 
    "marketplace", "meetings", "membership", "pages", 
    "profile", "referrals", "settings", "trust"
)

# Root files to upload
$ROOT_FILES = Get-ChildItem -Path . -File | Where-Object { $_.Extension -match "php|txt|xml|html|css" }

function Upload-File {
    param($LocalPath, $RemoteDir)
    
    $RemoteDir = $RemoteDir.Replace("\", "/")
    if ($RemoteDir -eq ".") { $RemoteDir = "" }
    
    Write-Host "Uploading $LocalPath to /$RemoteDir..."
    
    $Args = @("-T", $LocalPath, "-u", $FTP_USER, "$FTP_URL/$RemoteDir/", "--ftp-create-dirs")
    & $CURL @Args
}

# 1. Upload root files
foreach ($file in $ROOT_FILES) {
    Upload-File -LocalPath $file.Name -RemoteDir "."
}

# 2. Upload folders recursively
foreach ($folder in $FOLDERS) {
    if (Test-Path $folder) {
        $files = Get-ChildItem -Path $folder -Recurse -File
        foreach ($file in $files) {
            $RelativePath = $file.FullName.Replace((Get-Location).Path + "\", "")
            $RemoteDir = Split-Path -Path $RelativePath -Parent
            Upload-File -LocalPath $RelativePath -RemoteDir $RemoteDir
        }
    }
}

Write-Host "Deployment complete! 🚀"

$FTP_URL = "ftp://46.28.45.161"
$FTP_USER = "u175452495.biznexus.in:REPLACE_WITH_FTP_PASSWORD"
$CURL = "C:\Windows\System32\curl.exe"

$FILES = @(
    "includes\ai_helper_v3.php",
    "includes\ai_helper.php",
    "api\support_bot_chat.php",
    "api\public_bot_chat.php",
    "api\generate_website.php",
    "agent\deploy_agents.php",
    "agent\qa_agent.php",
    "find.php"
)

function Upload-File {
    param($LocalPath)
    
    $RemotePath = $LocalPath.Replace("\", "/")
    $RemoteDir = Split-Path -Path $RemotePath -Parent
    
    Write-Host "Patching $RemotePath..."
    
    $Args = @("-T", $LocalPath, "-u", $FTP_USER, "$FTP_URL/$RemoteDir/", "--ftp-create-dirs")
    & $CURL @Args
}

foreach ($file in $FILES) {
    if (Test-Path $file) {
        Upload-File -LocalPath $file
    } else {
        Write-Warning "File not found: $file"
    }
}

Write-Host "Patch applied! Gemini model should be live now. 🚀"

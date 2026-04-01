<?php
// deploy.php - Pushes all new changes to the live Hostinger server via FTP

$ftp_server = "46.28.45.161";
$ftp_username = "u175452495.biznexus.in";
$ftp_userpass = "REPLACE_WITH_FTP_PASSWORD";

// Establish FTP Connection
$ftp_conn = ftp_connect($ftp_server) or die("Could not connect to $ftp_server");
$login = ftp_login($ftp_conn, $ftp_username, $ftp_userpass);
ftp_pasv($ftp_conn, true); // Important for Hostinger

if($login) {
    echo "Successfully connected via FTP.\n";
    
    // Set base paths
    $local_base = __DIR__;
    $remote_base = "/public_html";
    
    // Arrays to ignore
    $ignores = ['.git', 'deploy.php', 'temp_out.txt', 'temp_out2.txt'];

    // Recursive function to parse and upload
    function upload_dir($conn, $local_dir, $remote_dir, $ignores) {
        $files = scandir($local_dir);
        
        // Ensure remote directory exists
        @ftp_mkdir($conn, $remote_dir);
        
        foreach($files as $file) {
            if($file == "." || $file == ".." || in_array($file, $ignores)) continue;
            
            $local_path = $local_dir . '/' . $file;
            $remote_path = $remote_dir . '/' . $file;
            
            if(is_dir($local_path)) {
                // Ignore nested recursive sync locks if any
                upload_dir($conn, $local_path, $remote_path, $ignores);
            } else {
                echo "Uploading: $file to $remote_path ... ";
                if(ftp_put($conn, $remote_path, $local_path, FTP_BINARY)) {
                    echo "[OK]\n";
                } else {
                    echo "[FAILED]\n";
                }
            }
        }
    }
    
    // Start Recursive Upload
    upload_dir($ftp_conn, $local_base, $remote_base, $ignores);
    
    echo "\nDeployment Complete!\n";
} else {
    echo "FTP Login Failed.\n";
}

ftp_close($ftp_conn);
?>

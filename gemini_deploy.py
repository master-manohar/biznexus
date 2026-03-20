import os
import subprocess

FTP_URL = "ftp://46.28.45.161"
FTP_USER = "u175452495.biznexus.in:Skn@123nch"

# Folders to upload (recursive)
FOLDERS = [
    "auth", "marketplace", "groups", "membership", "settings", 
    "trust", "kyc", "leads", "includes", "agent", "assets", "pages"
]

# Root files (non-recursive)
ROOT_FILES = [
    f for f in os.listdir('.') 
    if os.path.isfile(f) and f.endswith(('.php', '.txt', '.xml', '.html'))
]

def upload_file(local_path, remote_dir):
    print(f"Uploading {local_path} to {remote_dir}...")
    cmd = [
        "curl", "-T", local_path, 
        "-u", FTP_USER, 
        f"{FTP_URL}/{remote_dir}/", 
        "--ftp-create-dirs"
    ]
    try:
        subprocess.run(cmd, check=True)
    except subprocess.CalledProcessError as e:
        print(f"Failed to upload {local_path}: {e}")

# Upload root files
for file in ROOT_FILES:
    upload_file(file, "")

# Upload folders
for folder in FOLDERS:
    for root, dirs, files in os.walk(folder):
        # Convert to forward slashes for FTP
        remote_path = root.replace(os.sep, '/')
        for file in files:
            local_path = os.path.join(root, file)
            upload_file(local_path, remote_path)

print("Deployment complete!")

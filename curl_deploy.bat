@echo off
setlocal enabledelayedexpansion
set "FTP_URL=ftp://46.28.45.161"
set "FTP_USER=u175452495.biznexus.in:REPLACE_WITH_FTP_PASSWORD"

echo Starting deployment to 46.28.45.161...

curl.exe -T index.php -u "%FTP_USER%" "%FTP_URL%/"
curl.exe -T help.php -u "%FTP_USER%" "%FTP_URL%/"
curl.exe -T admin_setup.php -u "%FTP_USER%" "%FTP_URL%/"

curl.exe -T admin/users.php -u "%FTP_USER%" "%FTP_URL%/admin/" --ftp-create-dirs
curl.exe -T admin/superadmin.php -u "%FTP_USER%" "%FTP_URL%/admin/" --ftp-create-dirs

curl.exe -T dashboard/index.php -u "%FTP_USER%" "%FTP_URL%/dashboard/" --ftp-create-dirs
curl.exe -T dashboard/leads.php -u "%FTP_USER%" "%FTP_URL%/dashboard/" --ftp-create-dirs

curl.exe -T agent/seo_agent.php -u "%FTP_USER%" "%FTP_URL%/agent/" --ftp-create-dirs
curl.exe -T agent/social_agent.php -u "%FTP_USER%" "%FTP_URL%/agent/" --ftp-create-dirs
curl.exe -T agent/marketing_agent.php -u "%FTP_USER%" "%FTP_URL%/agent/" --ftp-create-dirs
curl.exe -T agent/fix3_trust_badges_db.php -u "%FTP_USER%" "%FTP_URL%/agent/" --ftp-create-dirs

curl.exe -T includes/email_config.php -u "%FTP_USER%" "%FTP_URL%/includes/" --ftp-create-dirs

curl.exe -T pages/about.php -u "%FTP_USER%" "%FTP_URL%/pages/" --ftp-create-dirs
curl.exe -T pages/privacy.php -u "%FTP_USER%" "%FTP_URL%/pages/" --ftp-create-dirs
curl.exe -T pages/contact.php -u "%FTP_USER%" "%FTP_URL%/pages/" --ftp-create-dirs
curl.exe -T pages/terms.php -u "%FTP_USER%" "%FTP_URL%/pages/" --ftp-create-dirs

echo Deployment configured via cURL!

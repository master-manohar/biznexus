@echo off
set "FTP_URL=ftp://46.28.45.161"
set "FTP_USER=u175452495.biznexus.in:REPLACE_WITH_FTP_PASSWORD"
set "CURL=curl.exe"

echo 🚀 Starting Targeted Deployment to BizNexus Live...

echo [1/10] Uploading AI Helpers...
%CURL% -T includes/ai_helper_v3.php -u "%FTP_USER%" "%FTP_URL%/includes/" --ftp-create-dirs
%CURL% -T includes/ai_helper.php -u "%FTP_USER%" "%FTP_URL%/includes/" --ftp-create-dirs

echo [2/10] Uploading Profile Pages...
%CURL% -T profile/edit.php -u "%FTP_USER%" "%FTP_URL%/profile/" --ftp-create-dirs
%CURL% -T ftp_profile_edit.php -u "%FTP_USER%" "%FTP_URL%/"

echo [3/10] Uploading Auth Handlers...
%CURL% -T auth/send_verification.php -u "%FTP_USER%" "%FTP_URL%/auth/" --ftp-create-dirs
%CURL% -T auth/verify.php -u "%FTP_USER%" "%FTP_URL%/auth/" --ftp-create-dirs

echo [4/10] Uploading Layout/Global Files...
%CURL% -T find.php -u "%FTP_USER%" "%FTP_URL%/"
%CURL% -T seo_viewer.php -u "%FTP_USER%" "%FTP_URL%/"
%CURL% -T includes/layout_start.php -u "%FTP_USER%" "%FTP_URL%/includes/" --ftp-create-dirs
%CURL% -T includes/email_config.php -u "%FTP_USER%" "%FTP_URL%/includes/" --ftp-create-dirs
%CURL% -T sitemap.php -u "%FTP_USER%" "%FTP_URL%/"

echo [5/10] Uploading Admin Tools...
%CURL% -T admin/users.php -u "%FTP_USER%" "%FTP_URL%/admin/" --ftp-create-dirs
%CURL% -T admin/seo.php -u "%FTP_USER%" "%FTP_URL%/admin/" --ftp-create-dirs
%CURL% -T admin/seo_dashboard.php -u "%FTP_USER%" "%FTP_URL%/admin/" --ftp-create-dirs
%CURL% -T admin/visitor_intel.php -u "%FTP_USER%" "%FTP_URL%/admin/" --ftp-create-dirs

echo [6/10] Uploading Lead Engines & APIs...
%CURL% -T includes/visitor_logger.php -u "%FTP_USER%" "%FTP_URL%/includes/" --ftp-create-dirs
%CURL% -T includes/turbo_lead_bar.php -u "%FTP_USER%" "%FTP_URL%/includes/" --ftp-create-dirs
%CURL% -T api/capture_public_lead.php -u "%FTP_USER%" "%FTP_URL%/api/" --ftp-create-dirs
%CURL% -T agent/db_visitor_init.php -u "%FTP_USER%" "%FTP_URL%/agent/" --ftp-create-dirs

%CURL% -T agent/diag_v6.php -u "%FTP_USER%" "%FTP_URL%/agent/" --ftp-create-dirs
echo [7/10] Uploading SEO Engines...
%CURL% -T agent/seo_agent.php -u "%FTP_USER%" "%FTP_URL%/agent/" --ftp-create-dirs
%CURL% -T agent/seo_power_agent.php -u "%FTP_USER%" "%FTP_URL%/agent/" --ftp-create-dirs
%CURL% -T agent/bulk_seo_agent.php -u "%FTP_USER%" "%FTP_URL%/agent/" --ftp-create-dirs
%CURL% -T agent/categories_v2.php -u "%FTP_USER%" "%FTP_URL%/agent/" --ftp-create-dirs

%CURL% -T agent/read_error_log.php -u "%FTP_USER%" "%FTP_URL%/agent/" --ftp-create-dirs
echo ✅ Targeted Deployment Complete! 🚀

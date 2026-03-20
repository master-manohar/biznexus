<?php
$files = [
    'diag.php', 'syntax_check.php', 'test_include.php', 'test_func.php', 
    'test_deps.php', 'test_mock.php', 'check_tables.php', 
    'check_notif_schema.php', 'check_meetings_real.php', 
    'test_error.php', 'test_dash.php', 'check_community_schema.php', 
    'check_analytics_deps.php', 'check_community_schema.php'
];
foreach($files as $f) {
    if(file_exists($f)) {
        unlink($f);
        echo "Deleted $f\n";
    }
}
unlink(__FILE__);
?>

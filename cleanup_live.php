<?php
unlink(__DIR__ . '/debug_auth.php');
unlink(__DIR__ . '/sync_to_live.bat');
unlink(__DIR__ . '/deploy.php');
unlink(__DIR__ . '/cleanup_live.php');
echo "Cleanup complete.";
?>

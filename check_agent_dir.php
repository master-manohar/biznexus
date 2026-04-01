<?php
echo "IS_DIR_AGENT: " . (is_dir('agent') ? 'YES' : 'NO') . "\n";
echo "IS_FILE_AGENT_SCRIPT: " . (file_exists('agent/social_media_agent.php') ? 'YES' : 'NO') . "\n";

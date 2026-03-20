<?php
// /profile/index.php
// Fix #7: Redirect old links to edit.php
header("Location: /profile/edit.php", true, 301);
exit;
?>

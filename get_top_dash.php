<?php
$lines = file('dashboard/index.php');
echo "<pre>";
for($i=0; $i<min(50, count($lines)); $i++){
    echo ($i+1) . ": " . htmlspecialchars($lines[$i]);
}
echo "</pre>";
?>

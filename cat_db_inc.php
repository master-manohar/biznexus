<?php
if(file_exists('includes/db.php')){
    echo "EXISTS<br>";
    echo "<pre>";
    echo htmlspecialchars(file_get_contents('includes/db.php'));
    echo "</pre>";
} else {
    echo "NOT FOUND";
}
?>

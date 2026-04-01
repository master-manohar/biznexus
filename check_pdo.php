<?php
if (class_exists('PDO')) {
    echo "PDO_EXISTS\n";
    print_r(PDO::getAvailableDrivers());
} else {
    echo "PDO_MISSING";
}
助

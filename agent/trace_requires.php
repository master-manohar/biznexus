<?php
$files = [
    'find.php',
    'includes_functions.php',
    'includes/email_config.php',
    'includes/emails/lead_notify.php',
    'includes/db.php'
];

foreach ($files as $f) {
    if (file_exists($f)) {
        $content = file_get_contents($f);
        if (stripos($content, 'functions.php') !== false) {
            echo "FILE: $f -> CONTAINS 'functions.php'\n";
            preg_match_all('/(require|include)(_once)?\s+[\'"](.*functions\.php)[\'"]/', $content, $matches);
            print_r($matches[0]);
        }
    }
}

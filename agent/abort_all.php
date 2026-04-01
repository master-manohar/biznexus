<?php
// agent/abort_all.php
// Force stop long running agents by setting a flag in DB or Session

require_once __DIR__ . '/../includes/db.php';
session_start();
$_SESSION['stop_agent'] = true;

// Also try to kill PHP processes if possible (unlikely on shared hosting)
// But we can at least log what's happening.

echo "STOP FLAG SET. Any agent checking for \$_SESSION['stop_agent'] will terminate.\n";

<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
register_shutdown_function(function() {
    $err = error_get_last();
    echo "SHUTDOWN\n";
    var_dump($err);
});
echo "PRE\n";
require __DIR__ . '/backend/config/database.php';
echo "POST\n";

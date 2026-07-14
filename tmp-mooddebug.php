<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

header('Content-Type: text/plain; charset=utf-8');

echo "BEGIN\n";
echo "CWD=" . getcwd() . "\n";
echo "DIR=" . __DIR__ . "\n";
echo "INCLUDE_FILE=" . realpath(__DIR__ . '/backend/api/mood-meter.php') . "\n";
echo "DBFILE=" . realpath(__DIR__ . '/backend/config/database.php') . "\n";
echo "DBEXISTS=" . (file_exists(__DIR__ . '/backend/config/database.php') ? 'YES' : 'NO') . "\n";
echo "DBUSER=" . getenv('DB_USER') . "\n";
echo "DBPASSWORD=" . getenv('DB_PASSWORD') . "\n";

echo "-- INCLUDE mood-meter --\n";
require __DIR__ . '/backend/api/mood-meter.php';

echo "AFTER INCLUDE\n";
?>
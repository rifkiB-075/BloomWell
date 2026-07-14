<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
echo "BEGIN\n";
echo "CWD=" . getcwd() . "\n";
echo "DIR=" . __DIR__ . "\n";
echo "FILE=" . __FILE__ . "\n";
echo "REALDBFILE=" . realpath(__DIR__ . '/backend/config/database.php') . "\n";
echo "DBFILEEXISTS=" . (file_exists(__DIR__ . '/backend/config/database.php') ? 'YES' : 'NO') . "\n";
$contents = file(__DIR__ . '/backend/config/database.php', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
echo "FIRST5=" . implode(' | ', array_slice($contents, 0, 5)) . "\n";
?>

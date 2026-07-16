<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
echo "PRE\n";
var_dump(function_exists('mysqli_connect'));
var_dump(getenv('DB_USER'));
var_dump(getenv('DB_PASSWORD'));
require __DIR__ . '/backend/config/database.php';
echo "POST\n";

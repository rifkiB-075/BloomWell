<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

header('Content-Type: text/plain; charset=utf-8');

echo "BEGIN\n";

echo "CWD=" . getcwd() . "\n";
echo "DIR=" . __DIR__ . "\n";
echo "FILE=" . __FILE__ . "\n";

echo "REQUIRING DATABASE...\n";
require __DIR__ . '/backend/config/database.php';

echo "DB_CONN=" . (isset($conn) && $conn ? 'YES' : 'NO') . "\n";
if (!isset($conn) || !$conn) {
    echo "DB_ERR=" . (function_exists('mysqli_connect_error') ? mysqli_connect_error() : 'NOFUNC') . "\n";
}

echo "RUN QUERY...\n";
$result = mysqli_query($conn, "SELECT 1");
var_dump($result);

echo "DONE\n";
?>
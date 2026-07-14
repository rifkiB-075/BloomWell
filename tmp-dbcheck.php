<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

header('Content-Type: text/plain; charset=utf-8');

echo "BEGIN
";
echo "CWD=" . getcwd() . "
";
echo "DIR=" . __DIR__ . "
";
echo "FILE=" . __FILE__ . "
";
echo "INCLUDE_FILE=" . realpath(__DIR__ . '/backend/config/database.php') . "
";

try {
    require __DIR__ . '/backend/config/database.php';
    echo "REQUIRE OK
";
    echo "CONN=" . (isset($conn) && $conn ? 'YES' : 'NO') . "
";
    if (!$conn) {
        echo "CONNECT ERROR=" . (function_exists('mysqli_connect_error') ? mysqli_connect_error() : 'NOFUNC') . "
";
    }
} catch (Throwable $e) {
    echo "EXC=" . get_class($e) . "
";
    echo "MSG=" . $e->getMessage() . "
";
}

?>
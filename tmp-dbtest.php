<?php
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "test root/root\n";
$conn1 = @mysqli_connect('localhost', 'root', 'root', 'bloomwell_db');
var_dump($conn1 !== false);
echo "err1=" . mysqli_connect_error() . "\n";
if ($conn1) mysqli_close($conn1);

echo "test root/empty\n";
$conn2 = @mysqli_connect('localhost', 'root', '', 'bloomwell_db');
var_dump($conn2 !== false);
echo "err2=" . mysqli_connect_error() . "\n";
if ($conn2) mysqli_close($conn2);
?>
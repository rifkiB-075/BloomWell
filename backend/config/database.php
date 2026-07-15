<?php
$host = 'localhost';
$dbname = 'bloomwell_db';
$dbUser = 'root';
$dbPassword = 'root';

mysqli_report(MYSQLI_REPORT_OFF);
// jangan suppress error pakai @, agar error koneksi masuk ke mood-meter log
$conn = mysqli_connect($host, $dbUser, $dbPassword, $dbname);
if ($conn instanceof mysqli) {
    mysqli_set_charset($conn, 'utf8mb4');
}
?>
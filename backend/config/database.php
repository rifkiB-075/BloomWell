<?php
$host = 'localhost';
$user = 'root';
$password = '';  // biarkan kosong atau  password default Laragon "root"
$dbname = 'bloomwell_db';

// Pakai mysqli dengan cara ini
$conn = mysqli_connect($host, $user, $password, $dbname);

// Cek koneksi
if (!$conn) {
    die(json_encode(["error" => "Koneksi gagal: " . mysqli_connect_error()]));
}

// Setting CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
?>
<?php
// C:\laragon\www\BloomWell\backend\api\register.php

// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database config
include '../config/database.php';

// Cek koneksi database
if (!$conn) {
    echo json_encode([
        "success" => false,
        "message" => "Koneksi database gagal"
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method tidak diizinkan."]);
    exit();
}

// Ambil raw input data
$raw = file_get_contents("php://input");

// Jika raw input kosong, coba dari $_POST
if (empty($raw) && !empty($_POST)) {
    $data = $_POST;
} else {
    // Parse JSON
    $data = json_decode($raw, true);
    
    // Jika gagal parse JSON, coba parse sebagai form-data
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
        parse_str($raw, $data);
    }
    
    // Jika masih gagal, gunakan $_POST
    if (!is_array($data) || empty($data)) {
        $data = $_POST;
    }
}

// Ambil data - name bisa diabaikan atau disimpan di field lain
$name     = trim($data['name'] ?? $data['full_name'] ?? '');
$username = trim($data['username'] ?? '');
$email    = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

// Validasi
if (!$username || !$email || !$password) {
    http_response_code(400);
    echo json_encode([
        "success" => false, 
        "message" => "Username, email, dan password wajib diisi."
    ]);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Format email tidak valid."]);
    exit();
}

if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Kata sandi minimal 8 karakter."]);
    exit();
}

// Cek email duplikat
$stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
    http_response_code(409);
    echo json_encode(["success" => false, "message" => "Email sudah terdaftar."]);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    exit();
}
mysqli_stmt_close($stmt);

// Cek username duplikat
$stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
    http_response_code(409);
    echo json_encode(["success" => false, "message" => "Username sudah digunakan."]);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    exit();
}
mysqli_stmt_close($stmt);

// Hash password & simpan
$hashed = password_hash($password, PASSWORD_DEFAULT);

// INSERT tanpa kolom name/full_name
$stmt = mysqli_prepare($conn, "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
mysqli_stmt_bind_param($stmt, "sss", $username, $email, $hashed);

if (mysqli_stmt_execute($stmt)) {
    $new_id = mysqli_insert_id($conn);
    echo json_encode([
        "success" => true,
        "message" => "Akun berhasil dibuat!",
        "user"    => [
            "id"       => $new_id,
            "username" => $username,
            "email"    => $email,
            "role"     => "user"
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "message" => "Gagal menyimpan data: " . mysqli_error($conn)
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
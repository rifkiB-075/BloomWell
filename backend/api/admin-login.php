<?php
include '../config/database.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method tidak diizinkan."]);
    exit();
}

// Ambil data input dengan aman
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// Jika JSON tidak valid atau kosong
if ($data === null && !empty($input)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Format JSON tidak valid."]);
    exit();
}

$data = $data ?: [];
$email = isset($data['email']) ? trim($data['email']) : '';
$username = isset($data['username']) ? trim($data['username']) : '';
$password = isset($data['password']) ? $data['password'] : '';

// Validasi input
if ((empty($email) && empty($username)) || empty($password)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Username/email dan kata sandi wajib diisi."]);
    exit();
}

// Jika login pakai username, ubah ke email (asumsi email = username@bloomwell.local)
if (empty($email) && !empty($username)) {
    $email = $username . '@bloomwell.local';
}

// Validasi format email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Format email tidak valid."]);
    exit();
}

// Cek koneksi database
if (!$conn) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Koneksi database gagal."]);
    exit();
}

// Query untuk cek admin
$query = "SELECT id, full_name, username, email, password, role, is_active 
          FROM users 
          WHERE email = ? AND role = 'admin' AND is_active = 1 
          LIMIT 1";

$stmt = mysqli_prepare($conn, $query);
if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "message" => "Gagal prepare query admin login."
    ]);
    mysqli_close($conn);
    exit();
}

mysqli_stmt_bind_param($stmt, "s", $email);
$execute = mysqli_stmt_execute($stmt);

if (!$execute) {
    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "message" => "Gagal mengeksekusi query."
    ]);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    exit();
}

$result = mysqli_stmt_get_result($stmt);
$admin = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Cek admin dan verifikasi password
if (!$admin) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Email admin tidak ditemukan atau akun tidak aktif."]);
    mysqli_close($conn);
    exit();
}

// Verifikasi password
if (!password_verify($password, $admin['password'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Kata sandi salah."]);
    mysqli_close($conn);
    exit();
}

// Update last_login_at
$updateQuery = "UPDATE users SET last_login_at = NOW() WHERE id = ?";
$updateStmt = mysqli_prepare($conn, $updateQuery);

if ($updateStmt) {
    mysqli_stmt_bind_param($updateStmt, "i", $admin['id']);
    mysqli_stmt_execute($updateStmt);
    mysqli_stmt_close($updateStmt);
}

mysqli_close($conn);

// Response sukses
echo json_encode([
    "success" => true,
    "message" => "Login admin berhasil!",
    "admin" => [
        "id" => (int)$admin['id'],
        "name" => !empty($admin['full_name']) ? $admin['full_name'] : $admin['username'],
        "username" => $admin['username'],
        "email" => $admin['email'],
        "role" => $admin['role']
    ]
]);
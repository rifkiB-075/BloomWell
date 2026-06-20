<?php
include '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method tidak diizinkan."]);
    exit();
}

$data     = json_decode(file_get_contents("php://input"), true);
$email    = trim($data['email']    ?? '');
$password =      $data['password'] ?? '';

// Validasi input
if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Email dan kata sandi wajib diisi."]);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Format email tidak valid."]);
    exit();
}

// Cari user berdasarkan email — prepared statement (aman dari SQL injection)
$stmt = mysqli_prepare($conn, "SELECT id, full_name, username, email, password FROM users WHERE email = ? AND is_active = 1 LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user   = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Cek apakah user ditemukan & password cocok
if (!$user || !password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Email atau kata sandi salah."]);
    exit();
}

// Update last_login_at
$stmt = mysqli_prepare($conn, "UPDATE users SET last_login_at = NOW() WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user['id']);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// Kembalikan data user (tanpa password)
echo json_encode([
    "success" => true,
    "message" => "Login berhasil!",
    "user"    => [
        "id"       => $user['id'],
        "name"     => $user['full_name'],
        "username" => $user['username'],
        "email"    => $user['email']
    ]
]);

mysqli_close($conn);
?>
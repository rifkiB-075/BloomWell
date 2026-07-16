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

$data = json_decode(file_get_contents("php://input"), true) ?: [];
$email = trim($data['email'] ?? '');
$username = trim($data['username'] ?? '');
$password = $data['password'] ?? '';


if ((!$email || !$password) && (!($username ?? '') || !$password)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Username/email dan kata sandi wajib diisi."]);
    exit();
}

if (!$password) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Kata sandi wajib diisi."]);
    exit();
}

// Ambil email dari username jika email tidak dikirim
if (!$email && $username) {
    // asumsi seed demo: email = username + '@bloomwell.local'
    $email = $username . '@bloomwell.local';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Format email tidak valid."]);
    exit();
}

// Hanya admin: users.role = 'admin'
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, full_name, username, email, password, role, is_active FROM users WHERE email = ? AND role = 'admin' AND is_active = 1 LIMIT 1"
);
if (!$stmt) {

    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Gagal prepare query admin login.", "db_error" => mysqli_error($conn)]);
    mysqli_close($conn);
    exit();
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$admin = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($stmt);

if (!$admin || !password_verify($password, $admin['password'])) {

    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Email admin atau kata sandi salah."]);
    exit();
}

// Update last_login_at
$stmt = mysqli_prepare($conn, "UPDATE users SET last_login_at = NOW() WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $admin['id']);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

echo json_encode([
    "success" => true,
    "message" => "Login admin berhasil!",
    "admin" => [
        "id" => (int)$admin['id'],
        "name" => $admin['full_name'] ?? $admin['username'],
        "username" => $admin['username'],
        "email" => $admin['email'],
        "role" => $admin['role']
    ]
]);

mysqli_close($conn);
?>


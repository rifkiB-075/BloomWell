<?php
include '../config/database.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
<<<<<<< HEAD
header('Access-Control-Allow-Methods: POST, OPTIONS');
=======
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
>>>>>>> 07b9f5ffa7a3dcbc56e93d9b9e566221a1ab64f3
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

<<<<<<< HEAD
// Validasi input
if ((!$email && !$username) || !$password) {
=======

if ((!$email || !$password) && (!($username ?? '') || !$password)) {
>>>>>>> 07b9f5ffa7a3dcbc56e93d9b9e566221a1ab64f3
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Username/email dan kata sandi wajib diisi."]);
    exit();
}

<<<<<<< HEAD
// Jika login pakai username, ubah ke email (asumsi email = username@bloomwell.local)
if (!$email && $username) {
=======
if (!$password) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Kata sandi wajib diisi."]);
    exit();
}

// Ambil email dari username jika email tidak dikirim
if (!$email && $username) {
    // asumsi seed demo: email = username + '@bloomwell.local'
>>>>>>> 07b9f5ffa7a3dcbc56e93d9b9e566221a1ab64f3
    $email = $username . '@bloomwell.local';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Format email tidak valid."]);
    exit();
}

<<<<<<< HEAD
// ✅ QUERY YANG DIPERBAIKI (tanpa full_name, tanpa is_active)
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, username, email, password, role FROM users WHERE email = ? AND role = 'admin' LIMIT 1"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Gagal prepare query admin login.",
        "db_error" => mysqli_error($conn)
    ]);
=======
// Hanya admin: users.role = 'admin'
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, full_name, username, email, password, role, is_active FROM users WHERE email = ? AND role = 'admin' AND is_active = 1 LIMIT 1"
);
if (!$stmt) {

    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Gagal prepare query admin login.", "db_error" => mysqli_error($conn)]);
>>>>>>> 07b9f5ffa7a3dcbc56e93d9b9e566221a1ab64f3
    mysqli_close($conn);
    exit();
}

<<<<<<< HEAD
mysqli_stmt_bind_param($stmt, "s", $email);
=======
>>>>>>> 07b9f5ffa7a3dcbc56e93d9b9e566221a1ab64f3
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$admin = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($stmt);

<<<<<<< HEAD
// Cek admin dan password
if (!$admin || !password_verify($password, $admin['password'])) {
=======
if (!$admin || !password_verify($password, $admin['password'])) {

>>>>>>> 07b9f5ffa7a3dcbc56e93d9b9e566221a1ab64f3
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Email admin atau kata sandi salah."]);
    exit();
}

<<<<<<< HEAD
// ✅ UPDATE last_login_at (TAPI kolom ini juga belum ada!)
// Sementara di-comment dulu atau tambahkan kolom last_login_at
// $stmt = mysqli_prepare($conn, "UPDATE users SET last_login_at = NOW() WHERE id = ?");
// mysqli_stmt_bind_param($stmt, "i", $admin['id']);
// mysqli_stmt_execute($stmt);
// mysqli_stmt_close($stmt);
=======
// Update last_login_at
$stmt = mysqli_prepare($conn, "UPDATE users SET last_login_at = NOW() WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $admin['id']);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
>>>>>>> 07b9f5ffa7a3dcbc56e93d9b9e566221a1ab64f3

echo json_encode([
    "success" => true,
    "message" => "Login admin berhasil!",
    "admin" => [
        "id" => (int)$admin['id'],
<<<<<<< HEAD
        "name" => $admin['username'],  // Pakai username karena full_name tidak ada
=======
        "name" => $admin['full_name'] ?? $admin['username'],
>>>>>>> 07b9f5ffa7a3dcbc56e93d9b9e566221a1ab64f3
        "username" => $admin['username'],
        "email" => $admin['email'],
        "role" => $admin['role']
    ]
]);

mysqli_close($conn);
<<<<<<< HEAD
?>
=======
?>

>>>>>>> 07b9f5ffa7a3dcbc56e93d9b9e566221a1ab64f3

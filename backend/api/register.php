<?php
include '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method tidak diizinkan."]);
    exit();
}

$data     = json_decode(file_get_contents("php://input"), true);
$name     = trim($data['name']     ?? '');
$username = trim($data['username'] ?? '');
$email    = trim($data['email']    ?? '');
$password =      $data['password'] ?? '';

// Validasi
if (!$name || !$username || !$email || !$password) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Semua field wajib diisi."]);
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

// Cek email duplikat — pakai prepared statement (aman dari SQL injection)
$stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
    http_response_code(409);
    echo json_encode(["success" => false, "message" => "Email sudah terdaftar."]);
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
    exit();
}
mysqli_stmt_close($stmt);

// Hash password & simpan
$hashed = password_hash($password, PASSWORD_DEFAULT);

$stmt = mysqli_prepare($conn, "INSERT INTO users (full_name, username, email, password) VALUES (?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt, "ssss", $name, $username, $email, $hashed);

if (mysqli_stmt_execute($stmt)) {
    $new_id = mysqli_insert_id($conn);
    echo json_encode([
        "success" => true,
        "message" => "Akun berhasil dibuat!",
        "user"    => [
            "id"       => $new_id,
            "name"     => $name,
            "username" => $username,
            "email"    => $email
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Gagal menyimpan data: " . mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
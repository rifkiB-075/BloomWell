<?php
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

include '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method tidak diizinkan."]);
    exit();
}

// Ambil raw input data - gunakan metode yang reliable
$raw = file_get_contents("php://input");

// Jika raw input kosong, coba dari global $HTTP_RAW_POST_DATA (untuk mod_fcgid)
if (empty($raw) && isset($HTTP_RAW_POST_DATA)) {
    $raw = $HTTP_RAW_POST_DATA;
}

// Jika masih kosong, coba dari $_POST (untuk form-data)
if (empty($raw) && !empty($_POST)) {
    $data = $_POST;
} else {
    // Coba parse sebagai JSON
    $data = json_decode($raw, true);
    
    // Jika gagal parse JSON (error 4 = syntax error), coba perbaiki format
    if (json_last_error() === JSON_ERROR_SYNTAX) {
        // Handle case: JavaScript object literal tanpa quotes (mod_fcgid issue)
        $fixed = preg_replace_callback(
            '/(\w+)\s*:\s*([^,}\s]+)/',
            function($matches) {
                $key = $matches[1];
                $value = trim($matches[2]);
                // Jika value mengandung spasi atau karakter khusus, wrap dengan quotes
                if (preg_match('/[\s,@#$%^&*()\/\\]/', $value) || is_numeric($value)) {
                    return '"' . $key . '":"' . addslashes($value) . '"';
                }
                return '"' . $key . '":"' . $value . '"';
            },
            $raw
        );
        $data = json_decode($fixed, true);
    }
    
    // Jika masih gagal, coba parse sebagai form URL-encoded
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
        parse_str($raw, $data);
    }
    
    // Jika masih gagal, gunakan $_POST sebagai fallback
    if (!is_array($data) || empty($data)) {
        $data = $_POST;
    }
}

$email    = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

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
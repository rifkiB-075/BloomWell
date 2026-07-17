<?php
<<<<<<< HEAD
/**
 * Seed Admin Demo - Buat akun admin default
 * Akses: http://bloomwell.test/backend/api/seed_admin_demo.php
 */

// Koneksi ke database
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'bloomwell_db';

$conn = mysqli_connect($host, $user, $pass, $dbname);

// Cek koneksi
if (!$conn) {
    die(json_encode([
        'success' => false,
        'message' => 'Koneksi database GAGAL: ' . mysqli_connect_error()
    ]));
}

// Set charset
mysqli_set_charset($conn, 'utf8mb4');

// 1. CEK APAKAH KOLOM role SUDAH ADA
$checkRole = "SHOW COLUMNS FROM users LIKE 'role'";
$result = mysqli_query($conn, $checkRole);

if (mysqli_num_rows($result) == 0) {
    // Tambah kolom role jika belum ada
    $alter = "ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'user'";
    if (!mysqli_query($conn, $alter)) {
        die(json_encode([
            'success' => false,
            'message' => 'Gagal tambah kolom role: ' . mysqli_error($conn)
        ]));
    }
    echo "✅ Kolom 'role' berhasil ditambahkan.<br>";
}

// 2. CEK APAKAH ADMIN SUDAH ADA
$checkAdmin = "SELECT * FROM users WHERE username = 'admin'";
$result = mysqli_query($conn, $checkAdmin);

if (mysqli_num_rows($result) > 0) {
    // Update role admin jika belum
    $update = "UPDATE users SET role = 'admin' WHERE username = 'admin'";
    mysqli_query($conn, $update);
    
    $admin = mysqli_fetch_assoc($result);
    echo json_encode([
        'success' => true,
        'message' => '✅ Admin sudah ada, role sudah diupdate',
        'admin' => [
            'id' => $admin['id'],
            'username' => $admin['username'],
            'email' => $admin['email'],
            'role' => 'admin'
        ],
        'login' => 'Gunakan username: admin, password: admin123'
    ]);
    exit;
}

// 3. BUAT ADMIN BARU
$username = 'admin';
$email = 'admin@bloomwell.local';
$password = password_hash('admin123', PASSWORD_DEFAULT);
$role = 'admin';

$insert = "INSERT INTO users (username, email, password, role) 
           VALUES ('$username', '$email', '$password', '$role')";

if (mysqli_query($conn, $insert)) {
    echo json_encode([
        'success' => true,
        'message' => '✅ Admin berhasil dibuat!',
        'admin' => [
            'username' => 'admin',
            'password' => 'admin123'
        ],
        'login_url' => 'http://bloomwell.test/admin-login.html'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal membuat admin: ' . mysqli_error($conn)
    ]);
}

mysqli_close($conn);
?>
=======
// Seed admin demo: buat user admin dengan role='admin' dan password admin123
// Jalankan sekali dari root project ini.
// Contoh: http://localhost/BloomWell/seed_admin_demo.php

require __DIR__ . '/../config/database.php';

header('Content-Type: text/plain; charset=utf-8');

function fail($msg, $conn=null) {
  if ($conn) mysqli_close($conn);
  http_response_code(500);
  echo $msg . "\n";
  exit();
}

$username = 'admin';
$email = 'admin@bloomwell.local';
$passwordPlain = 'admin123';
$fullName = 'Admin';
$role = 'admin';
$isActive = 1;

if (!isset($conn) || !($conn instanceof mysqli)) {
  fail('Koneksi database gagal.');
}

mysqli_report(MYSQLI_REPORT_OFF);

// Cek apakah username/email sudah ada
$stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'ss', $username, $email);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$existing = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if ($existing) {
  echo "Admin demo sudah ada. id=" . (int)$existing['id'] . "\n";
  mysqli_close($conn);
  exit();
}

$hashed = password_hash($passwordPlain, PASSWORD_DEFAULT);

$stmt = mysqli_prepare(
  $conn,
  'INSERT INTO users (username, email, password, full_name, role, is_active) VALUES (?, ?, ?, ?, ?, ?)'
);
mysqli_stmt_bind_param($stmt, 'sssssi', $username, $email, $hashed, $fullName, $role, $isActive);
$ok = mysqli_stmt_execute($stmt);

if (!$ok) {
  fail('Gagal membuat admin demo: ' . mysqli_error($conn), $conn);
}

$newId = mysqli_insert_id($conn);
mysqli_stmt_close($stmt);
mysqli_close($conn);

echo "Berhasil membuat admin demo. id=" . (int)$newId . "\n";

echo "Kredensial: username={$username}, password={$passwordPlain}\n";

>>>>>>> 07b9f5ffa7a3dcbc56e93d9b9e566221a1ab64f3

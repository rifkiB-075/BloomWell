<?php
/**
 * Seed Admin Demo - Buat akun admin default
 * Akses: http://bloomwell.test/backend/api/seed_admin_demo.php
 */

// Koneksi ke database
$host = 'localhost';
$user = 'root';
$pass = 'root';
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

$stmt = mysqli_prepare($conn, "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt, "ssss", $username, $email, $password, $role);

if (mysqli_stmt_execute($stmt)) {
    $new_id = mysqli_insert_id($conn);
    echo json_encode([
        'success' => true,
        'message' => '✅ Admin berhasil dibuat!',
        'admin' => [
            'id' => $new_id,
            'username' => 'admin',
            'password' => 'admin123'
        ],
        'login_url' => 'http://bloomwell.test/admin-login.html'
    ]);
    mysqli_stmt_close($stmt);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal membuat admin: ' . mysqli_error($conn)
    ]);
    mysqli_stmt_close($stmt);
}

mysqli_close($conn);

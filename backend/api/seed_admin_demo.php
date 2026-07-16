<?php
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


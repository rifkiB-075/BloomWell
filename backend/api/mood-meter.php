<?php
// C:\laragon\www\BloomWell\backend\api\mood-meter.php

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

// Enable error reporting
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Include database config
$configPath = __DIR__ . '/../config/database.php';
if (!file_exists($configPath)) {
    echo json_encode([
        'success' => false,
        'message' => 'Database config not found: ' . $configPath
    ]);
    exit();
}

include $configPath;

// Cek koneksi database
if (!isset($conn) || !$conn) {
    echo json_encode([
        'success' => false,
        'message' => 'Koneksi database gagal: ' . mysqli_connect_error()
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method tidak diizinkan. Gunakan POST.'
    ]);
    exit();
}

// Ambil raw input data
$raw = file_get_contents("php://input");
if (empty($raw)) {
    echo json_encode([
        'success' => false,
        'message' => 'Data tidak diterima. Pastikan Content-Type: application/json'
    ]);
    exit();
}

// Parse JSON
$data = json_decode($raw, true);
if ($data === null) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON: ' . json_last_error_msg()
    ]);
    exit();
}

// Ambil data
$user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;
$mood = isset($data['mood']) ? trim($data['mood']) : '';
$note = isset($data['note']) ? trim($data['note']) : '';

// Parse date: handle ISO format (2026-08-15T12:30:00) or use current time
$date = isset($data['date']) ? $data['date'] : date('Y-m-d H:i:s');
if (!empty($date)) {
    // Convert ISO 8601 (2026-08-15T12:30:00) to MySQL format (2026-08-15 12:30:00)
    $date = str_replace('T', ' ', $date);
    // Validate date format
    $dateObj = DateTime::createFromFormat('Y-m-d H:i:s', $date);
    if (!$dateObj || $dateObj->format('Y-m-d H:i:s') !== $date) {
        $date = date('Y-m-d H:i:s'); // Fallback to current time
    }
}

// Konversi format tanggal ISO (2026-08-15T12:00:00) ke MySQL (2026-08-15 12:00:00)
if (!empty($date)) {
    // Ganti 'T' dengan spasi untuk format MySQL
    $date = str_replace('T', ' ', $date);
    // Pastikan format valid (YYYY-MM-DD HH:MM:SS)
    if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $date)) {
        $date = date('Y-m-d H:i:s');
    }
}

// Validasi
if (!$user_id) {
    echo json_encode([
        'success' => false,
        'message' => 'User ID tidak valid. Silakan login kembali.'
    ]);
    exit();
}

if (empty($mood)) {
    echo json_encode([
        'success' => false,
        'message' => 'Mood tidak boleh kosong'
    ]);
    exit();
}

// Pastikan tabel mood_entries sudah ada
$createTable = "
CREATE TABLE IF NOT EXISTS mood_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    mood VARCHAR(50) NOT NULL,
    mood_score INT DEFAULT 3,
    note TEXT,
    ai_analysis TEXT,
    entry_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_entry (user_id, entry_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";

if (!mysqli_query($conn, $createTable)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Gagal membuat tabel mood_entries: ' . mysqli_error($conn)
    ]);
    mysqli_close($conn);
    exit();
}

// Mapping mood ke mood_score
$moodMap = [
    'very-sad' => 1,
    'sad' => 2,
    'neutral' => 3,
    'happy' => 4,
    'very-happy' => 5
];

$mood_score = $moodMap[$mood] ?? 3;
$ai_analysis = ''; // Default kosong

// Simpan mood ke mood_entries
$stmt = mysqli_prepare($conn, "INSERT INTO mood_entries (user_id, mood, mood_score, note, ai_analysis, entry_date) VALUES (?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Gagal prepare statement: ' . mysqli_error($conn)
    ]);
    mysqli_close($conn);
    exit();
}

mysqli_stmt_bind_param($stmt, "iissss", $user_id, $mood, $mood_score, $note, $ai_analysis, $date);

if (mysqli_stmt_execute($stmt)) {
    $mood_id = mysqli_insert_id($conn);
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Mood berhasil disimpan! 🌟',
        'data' => [
            'id' => $mood_id,
            'mood' => $mood,
            'mood_score' => $mood_score,
            'note' => $note,
            'date' => $date
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Gagal menyimpan mood: ' . mysqli_error($conn)
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
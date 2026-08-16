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
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan.']);
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
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

// Handle JSON parsing error
if ($data === null && !empty(file_get_contents('php://input'))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Format JSON tidak valid.']);
    mysqli_close($conn);
    exit();
}

$userId = isset($data['user_id']) ? (int)$data['user_id'] : 0;
$mood = trim($data['mood'] ?? '');
$note = trim($data['note'] ?? '');
$analysis = trim($data['analysis'] ?? '');
$entryDate = isset($data['entry_date']) ? $data['entry_date'] : date('Y-m-d H:i:s');

// Konversi format tanggal ISO (2026-08-15T12:00:00) ke MySQL (2026-08-15 12:00:00)
if (!empty($entryDate)) {
    $entryDate = str_replace('T', ' ', $entryDate);
    if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $entryDate)) {
        $entryDate = date('Y-m-d H:i:s');
    }
}

if (!$userId || !$mood || !$note) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'user_id, mood, dan note wajib diisi.']);
    mysqli_close($conn);
    exit();
}

$moodScore = 3;
$moodMap = [
    'very-sad' => 1,
    'sad' => 2,
    'neutral' => 3,
    'happy' => 4,
    'very-happy' => 5
];

if (isset($moodMap[$mood])) {
    $moodScore = $moodMap[$mood];
}

$stmt = mysqli_prepare($conn, "INSERT INTO mood_entries (user_id, mood, mood_score, note, ai_analysis, entry_date) VALUES (?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal prepare statement: ' . mysqli_error($conn)]);
    mysqli_close($conn);
    exit();
}

mysqli_stmt_bind_param($stmt, 'iisss', $userId, $mood, $moodScore, $note, $analysis);

if (mysqli_stmt_execute($stmt)) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Catatan mood berhasil disimpan.',
        'entry_id' => mysqli_insert_id($conn)
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan mood: ' . mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>

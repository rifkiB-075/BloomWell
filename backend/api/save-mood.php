<?php
include '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan.']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$userId = isset($data['user_id']) ? (int)$data['user_id'] : 0;
$mood = trim($data['mood'] ?? '');
$note = trim($data['note'] ?? '');
$analysis = trim($data['analysis'] ?? '');

if (!$userId || !$mood || !$note) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'user_id, mood, dan note wajib diisi.']);
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

$stmt = mysqli_prepare($conn, "INSERT INTO mood_entries (user_id, mood, mood_score, note, ai_analysis, entry_date) VALUES (?, ?, ?, ?, ?, CURDATE())");
mysqli_stmt_bind_param($stmt, 'iisss', $userId, $mood, $moodScore, $note, $analysis);

if (mysqli_stmt_execute($stmt)) {
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

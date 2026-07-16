<?php
include '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan.']);
    exit();
}

$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if (!$userId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'user_id wajib diisi.']);
    exit();
}

$stmt = mysqli_prepare($conn, "SELECT id, mood, mood_score, note, ai_analysis, entry_date, created_at FROM mood_entries WHERE user_id = ? ORDER BY entry_date DESC, created_at DESC");
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$entries = [];
while ($row = mysqli_fetch_assoc($result)) {
    $entries[] = $row;
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

echo json_encode(['success' => true, 'entries' => $entries]);
?>

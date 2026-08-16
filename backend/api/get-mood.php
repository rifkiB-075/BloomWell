<?php
// C:\laragon\www\BloomWell\backend\api\get-mood.php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 0);

$configPath = __DIR__ . '/../config/database.php';
if (!file_exists($configPath)) {
    echo json_encode([
        'success' => false,
        'message' => 'Database config not found'
    ]);
    exit();
}

include $configPath;

if (!isset($conn) || !$conn) {
    echo json_encode([
        'success' => false,
        'message' => 'Koneksi database gagal'
    ]);
    exit();
}

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;

if (!$user_id) {
    echo json_encode([
        'success' => false,
        'message' => 'User ID tidak valid'
    ]);
    exit();
}

// Ambil data mood.
// entry_date diubah ke format YYYY-MM-DD agar konsisten dipakai
// kalender (key tanggal harian), mood meter, dan analisis.
$query = "SELECT id, mood, mood_score as mood_value, note, DATE(entry_date) as date 
          FROM mood_entries 
          WHERE user_id = ? 
          ORDER BY entry_date DESC 
          LIMIT ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $user_id, $limit);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$moods = [];
while ($row = mysqli_fetch_assoc($result)) {
    $moods[] = [
        'id' => $row['id'],
        'mood' => $row['mood'],
        'mood_value' => $row['mood_value'] ?? 50,
        'note' => $row['note'] ?? '',
        'date' => $row['date']
    ];
}

echo json_encode([
    'success' => true,
    'data' => $moods
]);

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>

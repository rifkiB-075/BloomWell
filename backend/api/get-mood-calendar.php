<?php
// C:\laragon\www\BloomWell\backend\api\get-mood-calendar.php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

$configPath = __DIR__ . '/../config/database.php';
include $configPath;

if (!isset($conn) || !$conn) {
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal']);
    exit();
}

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User ID tidak valid']);
    exit();
}

$query = "SELECT DATE(date) as mood_date, mood, mood_value, note 
          FROM mood_logs 
          WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ?
          ORDER BY date DESC";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "iii", $user_id, $month, $year);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$moods = [];
while ($row = mysqli_fetch_assoc($result)) {
    $moods[] = $row;
}

echo json_encode([
    'success' => true,
    'data' => $moods
]);

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
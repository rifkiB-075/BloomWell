<?php
include '../config/database.php';

header('Content-Type: application/json');

function ensureUserId($conn)
{
    $stmt = mysqli_prepare($conn, 'SELECT id FROM users ORDER BY id LIMIT 1');
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($row && !empty($row['id'])) {
        return (int)$row['id'];
    }

    $username = 'guest_user';
    $email = 'guest@bloomwell.local';
    $password = password_hash('guest1234', PASSWORD_DEFAULT);
    $fullName = 'Guest User';

    $stmt = mysqli_prepare($conn, 'INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'ssss', $username, $email, $password, $fullName);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return (int)mysqli_insert_id($conn);
}

function getMoodLabelFromValue($value)
{
    if ($value >= 80) {
        return 'Senang';
    }
    if ($value >= 60) {
        return 'Tenang';
    }
    if ($value >= 40) {
        return 'Netral';
    }
    if ($value >= 25) {
        return 'Sedih';
    }
    if ($value >= 10) {
        return 'Cemas';
    }
    return 'Lelah';
}

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS mood_meter_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    mood_value TINYINT UNSIGNED NOT NULL COMMENT '0–100 skala mood meter',
    logged_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_time (user_id, logged_at)
) ENGINE=InnoDB");

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    if (!$userId) {
        $userId = ensureUserId($conn);
    }

    $stmt = mysqli_prepare($conn, 'SELECT id, mood_value, logged_at FROM mood_meter_logs WHERE user_id = ? ORDER BY logged_at DESC');
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $entries = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $entries[] = [
            'id' => (int)$row['id'],
            'mood_value' => (int)$row['mood_value'],
            'mood_label' => getMoodLabelFromValue((int)$row['mood_value']),
            'logged_at' => $row['logged_at'],
            'entry_date' => date('Y-m-d', strtotime($row['logged_at']))
        ];
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    echo json_encode(['success' => true, 'entries' => $entries]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = isset($data['user_id']) ? (int)$data['user_id'] : 0;
    if (!$userId) {
        $userId = ensureUserId($conn);
    }

    $moodValue = isset($data['mood_value']) ? (int)$data['mood_value'] : 0;
    $moodLabel = trim($data['mood_label'] ?? '');

    if ($moodValue < 0 || $moodValue > 100) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'mood_value harus di antara 0 dan 100.']);
        mysqli_close($conn);
        exit();
    }

    if (!$moodLabel) {
        $moodLabel = getMoodLabelFromValue($moodValue);
    }

    $stmt = mysqli_prepare($conn, 'INSERT INTO mood_meter_logs (user_id, mood_value) VALUES (?, ?)');
    mysqli_stmt_bind_param($stmt, 'ii', $userId, $moodValue);

    if (!mysqli_stmt_execute($stmt)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan mood: ' . mysqli_error($conn)]);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        exit();
    }

    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, 'SELECT id, mood_value, logged_at FROM mood_meter_logs WHERE user_id = ? ORDER BY logged_at DESC');
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $entries = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $entries[] = [
            'id' => (int)$row['id'],
            'mood_value' => (int)$row['mood_value'],
            'mood_label' => getMoodLabelFromValue((int)$row['mood_value']),
            'logged_at' => $row['logged_at'],
            'entry_date' => date('Y-m-d', strtotime($row['logged_at']))
        ];
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    echo json_encode([
        'success' => true,
        'message' => 'Mood meter tersimpan ke database.',
        'mood_label' => $moodLabel,
        'entries' => $entries
    ]);
    exit();
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan.']);
?>

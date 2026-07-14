<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null) {
        $logFile = __DIR__ . '/../logs/mood-meter-errors-debug.log';
        $message = sprintf(
            "[%s] SHUTDOWN ERROR: %s in %s on line %d\n",
            date('Y-m-d H:i:s'),
            $error['message'],
            $error['file'],
            $error['line']
        );
        @file_put_contents($logFile, $message, FILE_APPEND);
    }
});

if (function_exists('opcache_invalidate')) {
    @opcache_invalidate(__FILE__, true);
    @opcache_invalidate(__DIR__ . '/../config/database.php', true);
}

$originalErrorHandler = set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
restore_error_handler();

function sendJsonResponse(array $data, int $status = 200)
{
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    http_response_code($status);
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        $json = json_encode([
            'success' => false,
            'message' => 'JSON encoding failed',
            'json_error' => json_last_error_msg(),
        ], JSON_UNESCAPED_UNICODE);
    }
    echo $json;
    exit();
}

function logMoodMeterError(Throwable $e)
{
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $message = sprintf(
        "[%s] %s in %s:%d\nStack trace:\n%s\n",
        date('Y-m-d H:i:s'),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    );
    @file_put_contents($logDir . '/mood-meter-error.log', $message, FILE_APPEND);
}

try {
    require __DIR__ . '/../config/database.php';
    if (empty($conn) || !($conn instanceof mysqli)) {
        sendJsonResponse(['success' => false, 'message' => 'Koneksi database gagal.'], 500);
    }
} catch (Throwable $e) {
    logMoodMeterError($e);
    sendJsonResponse(['success' => false, 'message' => 'Kesalahan server internal.', 'error' => $e->getMessage()], 500);
}

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
    note TEXT NULL,
    logged_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_time (user_id, logged_at)
) ENGINE=InnoDB");

mysqli_query($conn, "ALTER TABLE mood_meter_logs ADD COLUMN IF NOT EXISTS note TEXT NULL");

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    if (!$userId) {
        $userId = ensureUserId($conn);
    }

    $stmt = mysqli_prepare($conn, 'SELECT id, mood_value, note, logged_at FROM mood_meter_logs WHERE user_id = ? ORDER BY logged_at DESC');
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $entries = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $entries[] = [
            'id' => (int)$row['id'],
            'mood_value' => (int)$row['mood_value'],
            'mood_label' => getMoodLabelFromValue((int)$row['mood_value']),
            'note' => $row['note'] ?? '',
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
    $note = trim($data['note'] ?? '');

    if ($moodValue < 0 || $moodValue > 100) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'mood_value harus di antara 0 dan 100.']);
        mysqli_close($conn);
        exit();
    }

    if (!$moodLabel) {
        $moodLabel = getMoodLabelFromValue($moodValue);
    }

    $stmt = mysqli_prepare($conn, 'INSERT INTO mood_meter_logs (user_id, mood_value, note) VALUES (?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'iis', $userId, $moodValue, $note);

    if (!mysqli_stmt_execute($stmt)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan mood: ' . mysqli_error($conn)]);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        exit();
    }

    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, 'SELECT id, mood_value, note, logged_at FROM mood_meter_logs WHERE user_id = ? ORDER BY logged_at DESC');
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $entries = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $entries[] = [
            'id' => (int)$row['id'],
            'mood_value' => (int)$row['mood_value'],
            'mood_label' => getMoodLabelFromValue((int)$row['mood_value']),
            'note' => $row['note'] ?? '',
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

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = isset($data['user_id']) ? (int)$data['user_id'] : 0;
    $entryId = isset($data['id']) ? (int)$data['id'] : 0;
    $moodValue = isset($data['mood_value']) ? (int)$data['mood_value'] : 0;
    $note = trim($data['note'] ?? '');

    if (!$userId || !$entryId || $moodValue < 0 || $moodValue > 100) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'user_id, id, dan mood_value wajib valid.']);
        mysqli_close($conn);
        exit();
    }

    $stmt = mysqli_prepare($conn, 'UPDATE mood_meter_logs SET mood_value = ?, note = ? WHERE id = ? AND user_id = ?');
    mysqli_stmt_bind_param($stmt, 'isii', $moodValue, $note, $entryId, $userId);

    if (!mysqli_stmt_execute($stmt)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui mood: ' . mysqli_error($conn)]);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        exit();
    }

    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, 'SELECT id, mood_value, note, logged_at FROM mood_meter_logs WHERE user_id = ? ORDER BY logged_at DESC');
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $entries = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $entries[] = [
            'id' => (int)$row['id'],
            'mood_value' => (int)$row['mood_value'],
            'mood_label' => getMoodLabelFromValue((int)$row['mood_value']),
            'note' => $row['note'] ?? '',
            'logged_at' => $row['logged_at'],
            'entry_date' => date('Y-m-d', strtotime($row['logged_at']))
        ];
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    echo json_encode(['success' => true, 'message' => 'Mood berhasil diperbarui.', 'entries' => $entries]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = isset($data['user_id']) ? (int)$data['user_id'] : 0;
    $entryId = isset($data['id']) ? (int)$data['id'] : 0;

    if (!$userId || !$entryId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'user_id dan id wajib valid.']);
        mysqli_close($conn);
        exit();
    }

    $stmt = mysqli_prepare($conn, 'DELETE FROM mood_meter_logs WHERE id = ? AND user_id = ?');
    mysqli_stmt_bind_param($stmt, 'ii', $entryId, $userId);

    if (!mysqli_stmt_execute($stmt)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus mood: ' . mysqli_error($conn)]);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        exit();
    }

    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, 'SELECT id, mood_value, note, logged_at FROM mood_meter_logs WHERE user_id = ? ORDER BY logged_at DESC');
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $entries = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $entries[] = [
            'id' => (int)$row['id'],
            'mood_value' => (int)$row['mood_value'],
            'mood_label' => getMoodLabelFromValue((int)$row['mood_value']),
            'note' => $row['note'] ?? '',
            'logged_at' => $row['logged_at'],
            'entry_date' => date('Y-m-d', strtotime($row['logged_at']))
        ];
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    echo json_encode(['success' => true, 'message' => 'Mood berhasil dihapus.', 'entries' => $entries]);
    exit();
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan.']);
?>

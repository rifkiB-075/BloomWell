<?php
// Test sederhana
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TEST MOOD METER ===\n\n";

// Test database
$conn = mysqli_connect('localhost', 'root', 'root', 'bloomwell_db');
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
echo "✅ Database connected\n";

// Cek user
$result = mysqli_query($conn, "SELECT id, username FROM users LIMIT 5");
echo "Users in database:\n";
while ($row = mysqli_fetch_assoc($result)) {
    echo "  - ID: {$row['id']}, Username: {$row['username']}\n";
}

// Test insert mood
$user_id = 1; // Ganti dengan ID user yang valid
$mood = "happy"; // Gunakan nilai yang ada di moodMap
$note = "Test from script";
$date = date('Y-m-d H:i:s');

// Mapping mood ke mood_score
$moodMap = [
    'very-sad' => 1,
    'sad' => 2,
    'neutral' => 3,
    'happy' => 4,
    'very-happy' => 5
];

$mood_score = $moodMap[$mood] ?? 3;
$ai_analysis = "Test analysis from script";

// Pastikan tabel mood_entries sudah ada
$createTable = "CREATE TABLE IF NOT EXISTS mood_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    mood VARCHAR(50) NOT NULL,
    mood_score INT DEFAULT 3,
    note TEXT,
    ai_analysis TEXT,
    entry_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_entry (user_id, entry_date)
)";

if (!mysqli_query($conn, $createTable)) {
    die("❌ Failed to create table: " . mysqli_error($conn) . "\n");
}

// Insert
$stmt = mysqli_prepare($conn, "INSERT INTO mood_entries (user_id, mood, mood_score, note, ai_analysis, entry_date) VALUES (?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    die("❌ Failed to prepare statement: " . mysqli_error($conn) . "\n");
}
mysqli_stmt_bind_param($stmt, "iissss", $user_id, $mood, $mood_score, $note, $ai_analysis, $date);

if (mysqli_stmt_execute($stmt)) {
    echo "✅ Mood inserted successfully! ID: " . mysqli_insert_id($conn) . "\n";
} else {
    echo "❌ Insert failed: " . mysqli_error($conn) . "\n";
}

mysqli_close($conn);
echo "\n=== TEST DONE ===\n";
?>
<?php
/**
 * Hapus tabel mood_logs (opsional)
 * Jalankan ini setelah memastikan semua data sudah dimigrasi ke mood_entries
 */

header('Content-Type: application/json');

$host = 'localhost';
$user = 'root';
$pass = 'root';
$dbname = 'bloomwell_db';

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die(json_encode([
        'success' => false,
        'message' => 'Koneksi database gagal: ' . mysqli_connect_error()
    ]));
}

// Cek apakah mood_entries sudah punya data
$checkMoodEntries = mysqli_query($conn, "SELECT COUNT(*) as count FROM mood_entries");
$moodEntriesCount = mysqli_fetch_assoc($checkMoodEntries)['count'];

// Cek apakah mood_logs masih punya data
$checkMoodLogs = mysqli_query($conn, "SELECT COUNT(*) as count FROM mood_logs");
$moodLogsCount = 0;
if ($checkMoodLogs) {
    $moodLogsCount = mysqli_fetch_assoc($checkMoodLogs)['count'];
}

if ($moodLogsCount > 0 && $moodEntriesCount == 0) {
    echo json_encode([
        'success' => false,
        'message' => '⚠️ Tabel mood_logs masih punya data (' . $moodLogsCount . '), tapi mood_entries kosong. Migrasikan data terlebih dahulu!',
        'mood_logs_count' => $moodLogsCount,
        'mood_entries_count' => $moodEntriesCount
    ]);
    mysqli_close($conn);
    exit();
}

// Hapus tabel mood_logs
$dropQuery = "DROP TABLE IF EXISTS mood_logs";
if (mysqli_query($conn, $dropQuery)) {
    echo json_encode([
        'success' => true,
        'message' => '✅ Tabel mood_logs berhasil dihapus.',
        'mood_logs_count' => $moodLogsCount,
        'mood_entries_count' => $moodEntriesCount
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal menghapus tabel mood_logs: ' . mysqli_error($conn)
    ]);
}

mysqli_close($conn);
?>

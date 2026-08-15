<?php
/**
 * Setup tabel mood_entries dengan struktur optimal
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

mysqli_set_charset($conn, 'utf8mb4');

// 1. Buat tabel mood_entries dengan struktur lengkap
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
    echo json_encode([
        'success' => false,
        'message' => 'Gagal membuat tabel mood_entries: ' . mysqli_error($conn)
    ]);
    mysqli_close($conn);
    exit();
}

// 2. Tambahkan kolom is_active ke users jika belum ada
$checkIsActive = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'is_active'");
if (mysqli_num_rows($checkIsActive) == 0) {
    $alterIsActive = "ALTER TABLE users ADD COLUMN is_active TINYINT(1) DEFAULT 1";
    if (!mysqli_query($conn, $alterIsActive)) {
        echo json_encode([
            'success' => false,
            'message' => 'Gagal menambahkan kolom is_active: ' . mysqli_error($conn)
        ]);
        mysqli_close($conn);
        exit();
    }
}

// 3. Tambahkan kolom last_login_at ke users jika belum ada
$checkLastLogin = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'last_login_at'");
if (mysqli_num_rows($checkLastLogin) == 0) {
    $alterLastLogin = "ALTER TABLE users ADD COLUMN last_login_at DATETIME NULL DEFAULT NULL";
    if (!mysqli_query($conn, $alterLastLogin)) {
        echo json_encode([
            'success' => false,
            'message' => 'Gagal menambahkan kolom last_login_at: ' . mysqli_error($conn)
        ]);
        mysqli_close($conn);
        exit();
    }
}

// 4. Tambahkan kolom full_name ke users jika belum ada
$checkFullName = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'full_name'");
if (mysqli_num_rows($checkFullName) == 0) {
    $alterFullName = "ALTER TABLE users ADD COLUMN full_name VARCHAR(100) NULL DEFAULT NULL AFTER id";
    if (!mysqli_query($conn, $alterFullName)) {
        echo json_encode([
            'success' => false,
            'message' => 'Gagal menambahkan kolom full_name: ' . mysqli_error($conn)
        ]);
        mysqli_close($conn);
        exit();
    }
}

echo json_encode([
    'success' => true,
    'message' => '✅ Setup tabel selesai! Tabel mood_entries dan kolom pendukung sudah siap.',
    'tables' => ['mood_entries'],
    'columns_added' => ['is_active', 'last_login_at', 'full_name']
]);

mysqli_close($conn);
?>

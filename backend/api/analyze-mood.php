<?php
include '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method tidak diizinkan.']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$mood = trim($data['mood'] ?? '');
$note = trim($data['note'] ?? '');

if (!$mood || !$note) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Mood dan catatan wajib diisi.']);
    exit();
}

$analysis = buildAnalysis($mood, $note);

$response = [
    'success' => true,
    'analysis' => $analysis,
    'mood' => $mood,
    'note' => $note
];

echo json_encode($response, JSON_PRETTY_PRINT);

function buildAnalysis($mood, $note) {
    $text = strtolower($note);
    $score = 0;

    $moodMap = [
        'very-sad' => 1,
        'sad' => 2,
        'neutral' => 3,
        'happy' => 4,
        'very-happy' => 5
    ];

    $score = $moodMap[$mood] ?? 3;

    $positiveWords = ['senang', 'bahagia', 'bersemangat', 'sukses', 'tenang', 'syukur', 'baik', 'puas', 'ceria', 'nyaman'];
    $negativeWords = ['sedih', 'stres', 'cemas', 'khawatir', 'lelah', 'bingung', 'frustrasi', 'depresi', 'takut', 'susah', 'marah'];

    $positiveHits = 0;
    $negativeHits = 0;
    foreach ($positiveWords as $word) {
        if (strpos($text, $word) !== false) $positiveHits++;
    }
    foreach ($negativeWords as $word) {
        if (strpos($text, $word) !== false) $negativeHits++;
    }

    if ($negativeHits > $positiveHits) {
        $summary = 'Catatan Anda menunjukkan adanya beban emosional yang cukup kuat. Cobalah untuk beristirahat, berbicara dengan orang terdekat, atau luangkan waktu untuk aktivitas yang menenangkan.';
    } elseif ($positiveHits > 0) {
        $summary = 'Catatan Anda menunjukkan suasana yang cukup positif. Pertahankan kebiasaan baik ini dan lanjutkan aktivitas yang memberi Anda energi.';
    } else {
        $summary = 'Catatan Anda cukup seimbang. Tetap jaga rutinitas dan perhatikan pola emosi Anda dari waktu ke waktu.';
    }

    if ($score >= 4) {
        $recommendation = 'Kebiasaan Anda saat ini baik. Pertahankan rutinitas yang membuat Anda merasa nyaman.';
    } elseif ($score <= 2) {
        $recommendation = 'Anda mungkin sedang membutuhkan dukungan. Pertimbangkan istirahat, kontak dengan orang yang Anda percaya, atau bantuan profesional jika diperlukan.';
    } else {
        $recommendation = 'Hari Anda sedang cukup seimbang. Luangkan waktu untuk aktivitas yang membantu mengurangi stres.';
    }

    return "Ringkasan emosi: $summary\n\nRekomendasi: $recommendation";
}
?>

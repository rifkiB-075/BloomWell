<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$rootDir = dirname(__DIR__, 2);
$storageDir = $rootDir . '/backend/uploads/pdfs';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0777, true);
}

$resourcesFile = $rootDir . '/backend/data/resources.json';
$resources = [];
if (file_exists($resourcesFile)) {
    $resources = json_decode(file_get_contents($resourcesFile), true) ?: [];
}

function loadResources() {
    global $resourcesFile;
    if (!file_exists($resourcesFile)) {
        return [];
    }
    return json_decode(file_get_contents($resourcesFile), true) ?: [];
}

function saveResources($items) {
    global $resourcesFile;
    file_put_contents($resourcesFile, json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function resolveFilePath($relativePath) {
    global $rootDir;
    return $rootDir . '/' . ltrim($relativePath, '/');
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $published = array_values(array_filter($resources, function ($item) {
        return ($item['status'] ?? 'draft') === 'published';
    }));
    echo json_encode(['success' => true, 'resources' => $published]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'upload';
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $categoryLabel = trim($_POST['category_label'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $readTime = trim($_POST['read_time'] ?? '');

    if (!$title || !$category || !$description) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Judul, kategori, dan deskripsi wajib diisi.']);
        exit();
    }

    if ($action === 'edit') {
        $id = (int)($_POST['resource_id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID buku tidak valid.']);
            exit();
        }

        foreach ($resources as &$item) {
            if ((int)($item['id'] ?? 0) === $id) {
                $item['title'] = $title;
                $item['category'] = $category;
                $item['category_label'] = $categoryLabel ?: ucfirst($category);
                $item['description'] = $description;
                $item['read_time'] = $readTime ?: '5 menit baca';
                break;
            }
        }
        unset($item);

        saveResources($resources);
        echo json_encode(['success' => true, 'message' => 'Data buku berhasil diperbarui.']);
        exit();
    }

    if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'File PDF wajib diunggah.']);
        exit();
    }

    $ext = strtolower(pathinfo($_FILES['pdf_file']['name'], PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'File harus berformat PDF.']);
        exit();
    }

    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '-', strtolower(pathinfo($_FILES['pdf_file']['name'], PATHINFO_FILENAME)));
    $fileName = $safeName . '-' . time() . '.pdf';
    $targetPath = $storageDir . '/' . $fileName;

    if (!move_uploaded_file($_FILES['pdf_file']['tmp_name'], $targetPath)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan file PDF.']);
        exit();
    }

    $newItem = [
        'id' => time(),
        'title' => $title,
        'category' => $category,
        'category_label' => $categoryLabel ?: ucfirst($category),
        'description' => $description,
        'read_time' => $readTime ?: '5 menit baca',
        'type' => 'pdf',
        'status' => 'published',
        'file_path' => 'backend/uploads/pdfs/' . $fileName
    ];

    $resources[] = $newItem;
    saveResources($resources);

    echo json_encode(['success' => true, 'message' => 'Buku PDF berhasil ditambahkan.', 'resource' => $newItem]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true) ?: [];
    $id = (int)($data['id'] ?? 0);

    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID buku tidak valid.']);
        exit();
    }

    $updated = [];
    foreach ($resources as $item) {
        if ((int)($item['id'] ?? 0) === $id) {
            $filePath = $item['file_path'] ?? '';
            if ($filePath) {
                $fullPath = resolveFilePath($filePath);
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }
            continue;
        }
        $updated[] = $item;
    }

    saveResources($updated);
    echo json_encode(['success' => true, 'message' => 'Buku berhasil dihapus.']);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan.']);
?>
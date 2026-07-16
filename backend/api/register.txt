<?php
include '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

$username = $data['username'] ?? '';
$email = $data['email'] ?? '';
$password = password_hash($data['password'] ?? '', PASSWORD_DEFAULT);

$check = $conn->query("SELECT id FROM users WHERE email = '$email'");
if ($check->num_rows > 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Email sudah terdaftar"]);
    exit();
}

$sql = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$password')";

if ($conn->query($sql)) {
    echo json_encode([
        "success" => true, 
        "message" => "Register berhasil! Silakan login.",
        "user_id" => $conn->insert_id
    ]);
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Register gagal: " . $conn->error]);
}
?>
<?php
header("Content-Type: application/json");
echo json_encode([
    "status" => "success",
    "message" => "Backend Bloomwell siap digunakan!",
    "endpoints" => [
        "/api/register",
        "/api/login",
        "/api/save-mood",
        "/api/get-moods"
    ]
]);
?>
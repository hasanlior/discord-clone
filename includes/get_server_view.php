<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Unauthorized']));
}

$server_id = isset($_GET['server_id']) ? (int)$_GET['server_id'] : 0;

// Sunucu bilgilerini veritabanından al
$stmt = $conn->prepare("SELECT * FROM servers WHERE id = ?");
$stmt->bind_param("i", $server_id);
$stmt->execute();
$server = $stmt->get_result()->fetch_assoc();

if (!$server) {
    die(json_encode(['error' => 'Server not found']));
}

// Sunucu görünümü HTML'ini döndür (chat.php'deki sunucu görünümü)
include '../templates/server_view.php'; 
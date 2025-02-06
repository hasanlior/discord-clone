<?php
error_reporting(0);
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Unauthorized']));
}

if (!isset($_GET['server_id'])) {
    die(json_encode(['error' => 'Server ID required']));
}

$server_id = $_GET['server_id'];
$user_id = $_SESSION['user_id'];

// Kullanıcının bu sunucuya erişim yetkisi var mı kontrol et
$sql = "SELECT s.* FROM servers s 
        LEFT JOIN server_members sm ON s.id = sm.server_id 
        WHERE (sm.user_id = ? OR s.owner_id = ?) 
        AND s.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user_id, $user_id, $server_id);
$stmt->execute();
$result = $stmt->get_result();

if ($server = $result->fetch_assoc()) {
    echo json_encode($server);
} else {
    echo json_encode(['error' => 'Server not found']);
} 
<?php
require_once 'config.php';
header('Content-Type: application/json');

$user_id = checkAuth();
$server_id = isset($_GET['server_id']) ? (int)$_GET['server_id'] : 0;

if (!$server_id) {
    die(json_encode(['error' => 'Server ID required']));
}

try {
    $sql = "SELECT * FROM channel_categories 
            WHERE server_id = ? 
            ORDER BY position ASC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $server_id);
    $stmt->execute();
    $categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode($categories);

} catch (Exception $e) {
    die(json_encode(['error' => $e->getMessage()]));
} 
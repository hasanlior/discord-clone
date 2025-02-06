<?php
require_once 'config.php';
header('Content-Type: application/json');

$user_id = checkAuth();
$channel_id = isset($_GET['channel_id']) ? (int)$_GET['channel_id'] : 0;
$server_id = isset($_GET['server_id']) ? (int)$_GET['server_id'] : 0;

if (!$channel_id || !$server_id) {
    die(json_encode(['error' => 'Channel ID and Server ID required']));
}

try {
    $sql = "SELECT id, name, type 
            FROM channels 
            WHERE id = ? AND server_id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $channel_id, $server_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Channel not found');
    }
    
    $channel = $result->fetch_assoc();
    echo json_encode($channel);

} catch (Exception $e) {
    die(json_encode(['error' => $e->getMessage()]));
} 
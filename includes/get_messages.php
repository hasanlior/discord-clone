<?php
require_once 'config.php';
header('Content-Type: application/json');

$user_id = checkAuth();
$channel_id = isset($_GET['channel_id']) ? (int)$_GET['channel_id'] : 0;
$server_id = isset($_GET['server_id']) ? (int)$_GET['server_id'] : 0;
$last_message_id = isset($_GET['last_message_id']) ? (int)$_GET['last_message_id'] : 0;

if (!$channel_id || !$server_id) {
    die(json_encode(['error' => 'Channel ID and Server ID required']));
}

try {
    // Debug için
    error_log("Getting messages for channel: $channel_id, server: $server_id");

    // Önce kanalın doğru sunucuya ait olduğunu kontrol et
    $sql = "SELECT id FROM channels WHERE id = ? AND server_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $channel_id, $server_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Invalid channel or server');
    }

    // Sadece bu kanala ait mesajları getir
    $sql = "SELECT m.*, u.username, u.avatar 
            FROM messages m 
            INNER JOIN users u ON m.user_id = u.id 
            WHERE m.channel_id = ? 
            AND m.id > ?
            ORDER BY m.created_at ASC 
            LIMIT 50";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $channel_id, $last_message_id);
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Debug için
    error_log("Found " . count($messages) . " messages");

    echo json_encode($messages);

} catch (Exception $e) {
    error_log("Error getting messages: " . $e->getMessage());
    die(json_encode(['error' => $e->getMessage()]));
} 
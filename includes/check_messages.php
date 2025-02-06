<?php
require_once 'config.php';
header('Content-Type: application/json');

$user_id = checkAuth();
$channel_id = isset($_GET['channel_id']) ? (int)$_GET['channel_id'] : 0;
$last_message_id = isset($_GET['last_message_id']) ? (int)$_GET['last_message_id'] : 0;

if (!$channel_id) {
    die(json_encode(['error' => 'Channel ID required']));
}

try {
    // Önce kanalın sunucusunu ve kullanıcının yetkisini kontrol et
    $sql = "SELECT COUNT(*) as access
            FROM channels c 
            INNER JOIN servers s ON c.server_id = s.id 
            LEFT JOIN server_members sm ON s.id = sm.server_id 
            WHERE c.id = ? AND (sm.user_id = ? OR s.owner_id = ?)";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $channel_id, $user_id, $user_id);
    $stmt->execute();
    $access = $stmt->get_result()->fetch_assoc()['access'];

    if (!$access) {
        die(json_encode(['error' => 'Unauthorized']));
    }

    // Yeni mesaj var mı kontrol et
    $sql = "SELECT COUNT(*) as new_messages 
            FROM messages 
            WHERE channel_id = ? AND id > ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $channel_id, $last_message_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    echo json_encode([
        'hasNew' => $result['new_messages'] > 0,
        'count' => $result['new_messages']
    ]);

} catch (Exception $e) {
    die(json_encode(['error' => $e->getMessage()]));
} 
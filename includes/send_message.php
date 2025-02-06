<?php
require_once 'config.php';
header('Content-Type: application/json');

$user_id = checkAuth();
$channel_id = isset($_POST['channel_id']) ? (int)$_POST['channel_id'] : 0;
$server_id = isset($_POST['server_id']) ? (int)$_POST['server_id'] : 0;
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if (!$channel_id || !$server_id || empty($message)) {
    die(json_encode(['error' => 'Invalid input']));
}

try {
    // Önce kanalın doğru sunucuya ait olduğunu kontrol et
    $sql = "SELECT id FROM channels WHERE id = ? AND server_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $channel_id, $server_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Invalid channel or server');
    }

    // Mesajı kaydet
    $sql = "INSERT INTO messages (channel_id, user_id, content) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $channel_id, $user_id, $message);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message_id' => $conn->insert_id
        ]);
    } else {
        throw new Exception('Message could not be sent');
    }

} catch (Exception $e) {
    die(json_encode(['error' => $e->getMessage()]));
} 
<?php
require_once 'config.php';
header('Content-Type: application/json');

// Hata ayıklama için
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Debug log
error_log("Request received: " . print_r($_POST, true));

$user_id = checkAuth();
$server_id = isset($_POST['server_id']) ? (int)$_POST['server_id'] : 0;
$category_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;
$name = isset($_POST['name']) ? strtolower(trim($_POST['name'])) : '';
$type = isset($_POST['type']) ? trim($_POST['type']) : 'text';

// Debug log
error_log("Processed inputs: server_id=$server_id, category_id=$category_id, name=$name, type=$type");

if (!$server_id || empty($name)) {
    error_log("Invalid input detected");
    die(json_encode(['error' => 'Invalid input']));
}

try {
    // Kullanıcının sunucuda yetkisi olduğunu kontrol et
    $sql = "SELECT sm.role 
            FROM server_members sm
            INNER JOIN servers s ON s.id = sm.server_id
            WHERE sm.server_id = ? AND (sm.user_id = ? OR s.owner_id = ?)";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $server_id, $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    error_log("Permission check result: " . $result->num_rows);
    
    if ($result->num_rows === 0) {
        throw new Exception('Permission denied');
    }
    
    // Son pozisyonu bul
    $sql = "SELECT COALESCE(MAX(position), -1) + 1 as next_pos 
            FROM channels 
            WHERE server_id = ? 
            AND (category_id = ? OR (category_id IS NULL AND ? IS NULL))";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $server_id, $category_id, $category_id);
    $stmt->execute();
    $position = $stmt->get_result()->fetch_assoc()['next_pos'];
    
    error_log("Calculated position: $position");
    
    // Kanalı oluştur
    $sql = "INSERT INTO channels (server_id, category_id, name, type, position) 
            VALUES (?, ?, ?, ?, ?)";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissi", $server_id, $category_id, $name, $type, $position);
    
    if (!$stmt->execute()) {
        error_log("MySQL Error: " . $conn->error);
        throw new Exception($conn->error);
    }
    
    $channel_id = $conn->insert_id;
    error_log("Channel created with ID: $channel_id");
    
    $response = [
        'success' => true,
        'channel' => [
            'id' => $channel_id,
            'name' => $name,
            'type' => $type,
            'category_id' => $category_id,
            'position' => $position
        ]
    ];
    
    error_log("Sending response: " . json_encode($response));
    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error caught: " . $e->getMessage());
    http_response_code(500);
    die(json_encode(['error' => $e->getMessage()]));
} 
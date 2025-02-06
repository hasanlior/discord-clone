<?php
require_once 'config.php';
header('Content-Type: application/json');

// Hata raporlamayı devre dışı bırak
error_reporting(0);
ini_set('display_errors', 0);

$user_id = checkAuth();
$server_id = isset($_POST['server_id']) ? (int)$_POST['server_id'] : 0;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';

if (!$server_id || empty($name)) {
    die(json_encode(['error' => 'Invalid input']));
}

try {
    // Kullanıcının sunucuda yetkisi olduğunu kontrol et
    $sql = "SELECT role FROM server_members 
            WHERE server_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $server_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Permission denied');
    }
    
    // Son pozisyonu bul
    $sql = "SELECT COALESCE(MAX(position), -1) + 1 as next_pos 
            FROM channel_categories WHERE server_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $server_id);
    $stmt->execute();
    $position = $stmt->get_result()->fetch_assoc()['next_pos'];
    
    // Kategoriyi oluştur
    $sql = "INSERT INTO channel_categories (server_id, name, position) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isi", $server_id, $name, $position);
    
    if (!$stmt->execute()) {
        throw new Exception($conn->error);
    }
    
    $category_id = $conn->insert_id;
    
    echo json_encode([
        'success' => true,
        'category' => [
            'id' => $category_id,
            'name' => $name,
            'position' => $position
        ]
    ]);

} catch (Exception $e) {
    die(json_encode(['error' => $e->getMessage()]));
} 
<?php
require_once 'config.php';
header('Content-Type: application/json');

$user_id = checkAuth();
$server_id = isset($_GET['server_id']) ? (int)$_GET['server_id'] : 0;

if (!$server_id) {
    die(json_encode(['error' => 'Server ID required']));
}

try {
    // Kategorileri ve kanalları getir
    $sql = "SELECT c.*, cc.name as category_name, cc.id as category_id 
            FROM channels c 
            LEFT JOIN channel_categories cc ON c.category_id = cc.id 
            WHERE c.server_id = ? 
            ORDER BY cc.position, c.position";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $server_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $categories = [];
    $uncategorizedChannels = [];
    
    while ($row = $result->fetch_assoc()) {
        if ($row['category_id']) {
            if (!isset($categories[$row['category_id']])) {
                $categories[$row['category_id']] = [
                    'id' => $row['category_id'],
                    'name' => $row['category_name'],
                    'channels' => []
                ];
            }
            $categories[$row['category_id']]['channels'][] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'type' => $row['type']
            ];
        } else {
            $uncategorizedChannels[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'type' => $row['type']
            ];
        }
    }
    
    echo json_encode([
        'categories' => array_values($categories),
        'channels' => $uncategorizedChannels
    ]);

} catch (Exception $e) {
    die(json_encode(['error' => $e->getMessage()]));
} 
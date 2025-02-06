<?php
require_once 'config.php';
header('Content-Type: application/json');

$user_id = checkAuth();

try {
    // Sadece kullanıcının üye olduğu veya sahibi olduğu sunucuları getir
    $sql = "SELECT s.* 
            FROM servers s 
            LEFT JOIN server_members sm ON s.id = sm.server_id 
            WHERE sm.user_id = ? OR s.owner_id = ?
            GROUP BY s.id
            ORDER BY s.created_at DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $servers = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($servers);

} catch (Exception $e) {
    die(json_encode(['error' => $e->getMessage()]));
} 
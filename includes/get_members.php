<?php
require_once 'config.php';
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

$user_id = checkAuth();
$server_id = isset($_GET['server_id']) ? (int)$_GET['server_id'] : 0;

if (!$server_id) {
    die(json_encode(['error' => 'Server ID required']));
}

try {
    $sql = "SELECT u.id, u.username, u.avatar,
                   CASE 
                       WHEN s.owner_id = u.id THEN 'owner'
                       WHEN sm.role = 'admin' THEN 'admin'
                       WHEN sm.role = 'mod' THEN 'mod'
                       ELSE 'member'
                   END as role
            FROM users u 
            INNER JOIN server_members sm ON u.id = sm.user_id 
            INNER JOIN servers s ON sm.server_id = s.id 
            WHERE s.id = ?
            ORDER BY 
                CASE 
                    WHEN s.owner_id = u.id THEN 1
                    WHEN sm.role = 'admin' THEN 2
                    WHEN sm.role = 'mod' THEN 3
                    ELSE 4
                END,
                u.username ASC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    
    $stmt->bind_param("i", $server_id);
    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }
    
    $result = $stmt->get_result();
    $members = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode($members);

} catch (Exception $e) {
    error_log("Members Error: " . $e->getMessage());
    die(json_encode(['error' => $e->getMessage()]));
} 
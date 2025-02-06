<?php
require_once 'config.php';
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Oturum açmanız gerekiyor');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $invite_code = $data['invite_code'];

    // Davet kodunu kontrol et
    $stmt = $conn->prepare("SELECT * FROM invites WHERE code = ?");
    $stmt->bind_param("s", $invite_code);
    $stmt->execute();
    $invite = $stmt->get_result()->fetch_assoc();

    if (!$invite) {
        throw new Exception('Geçersiz davet linki');
    }

    // Kullanıcı zaten sunucuda mı kontrol et
    $stmt = $conn->prepare("SELECT * FROM server_members WHERE server_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $invite['server_id'], $_SESSION['user_id']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('Zaten bu sunucudasınız');
    }

    // Sunucuya ekle
    $stmt = $conn->prepare("INSERT INTO server_members (server_id, user_id, role) VALUES (?, ?, 'member')");
    $stmt->bind_param("ii", $invite['server_id'], $_SESSION['user_id']);
    $stmt->execute();

    // Davet kullanım sayısını artır
    $stmt = $conn->prepare("UPDATE invites SET uses = uses + 1 WHERE code = ?");
    $stmt->bind_param("s", $invite_code);
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'server_id' => $invite['server_id']
    ]);

} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage()
    ]);
} 
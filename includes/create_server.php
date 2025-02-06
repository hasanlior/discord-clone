<?php
require_once 'config.php';
header('Content-Type: application/json');

$user_id = checkAuth();
$server_name = isset($_POST['name']) ? trim($_POST['name']) : '';

if (empty($server_name)) {
    die(json_encode(['error' => 'Server name is required']));
}

try {
    $conn->begin_transaction();

    // Sunucu ikonu yükleme işlemi
    $icon = 'default_server.png'; // Varsayılan ikon
    
    if (isset($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['icon'];
        $fileName = uniqid() . '_' . basename($file['name']);
        $uploadPath = '../uploads/server_icons/' . $fileName;
        
        // Dizin kontrolü
        if (!file_exists('../uploads/server_icons/')) {
            mkdir('../uploads/server_icons/', 0777, true);
        }
        
        // Dosya türü kontrolü
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Invalid file type. Only JPG, PNG and GIF allowed.');
        }
        
        // Dosya boyutu kontrolü (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('File size too large. Maximum 5MB allowed.');
        }
        
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            $icon = $fileName;
        } else {
            throw new Exception('Failed to upload icon');
        }
    }

    // Sunucuyu oluştur
    $sql = "INSERT INTO servers (name, owner_id, icon) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sis", $server_name, $user_id, $icon);
    
    if (!$stmt->execute()) {
        throw new Exception("Server creation failed");
    }
    
    $server_id = $conn->insert_id;
    
    // Sunucu sahibini server_members tablosuna ekle
    $sql = "INSERT INTO server_members (server_id, user_id, role) VALUES (?, ?, 'admin')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $server_id, $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to add owner to server_members");
    }
    
    // Varsayılan kanalları oluştur
    $default_channels = [
        ['general', 'text'],
        ['announcements', 'text'],
        ['General', 'voice']
    ];
    
    $sql = "INSERT INTO channels (server_id, name, type) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    foreach ($default_channels as $channel) {
        $stmt->bind_param("iss", $server_id, $channel[0], $channel[1]);
        if (!$stmt->execute()) {
            throw new Exception("Failed to create default channel: " . $channel[0]);
        }
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'server_id' => $server_id,
        'icon' => $icon
    ]);

} catch (Exception $e) {
    $conn->rollback();
    // Yüklenen dosyayı sil
    if (isset($uploadPath) && file_exists($uploadPath)) {
        unlink($uploadPath);
    }
    die(json_encode(['error' => $e->getMessage()]));
} 
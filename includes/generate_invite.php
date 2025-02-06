<?php
require_once 'config.php';
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Oturum açmanız gerekiyor');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['server_id'])) {
        throw new Exception('Sunucu ID eksik');
    }

    $server_id = $data['server_id'];
    $expire = $data['expire'] ?? 'never';
    $max_uses = isset($data['max_uses']) ? (int)$data['max_uses'] : 0;

    // Sunucu kontrolü
    $stmt = $conn->prepare("SELECT owner_id FROM servers WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Veritabanı sorgusu hazırlanamadı: ' . $conn->error);
    }

    $stmt->bind_param("i", $server_id);
    if (!$stmt->execute()) {
        throw new Exception('Sorgu çalıştırılamadı: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    $server = $result->fetch_assoc();

    if (!$server) {
        throw new Exception('Sunucu bulunamadı');
    }

    // Davet kodu oluştur
    $code = bin2hex(random_bytes(5));

    // Son kullanma tarihi hesapla
    $expires_at = null;
    if ($expire !== 'never') {
        $expires_at = date('Y-m-d H:i:s', strtotime("+{$expire} days"));
    }

    // Davet linkini veritabanına kaydet
    $insert_sql = "INSERT INTO invites (code, server_id, created_by, created_at, expires_at, uses) 
                  VALUES (?, ?, ?, CURRENT_TIMESTAMP, ?, 0)";
    
    $stmt = $conn->prepare($insert_sql);
    if (!$stmt) {
        throw new Exception('Insert sorgusu hazırlanamadı: ' . $conn->error . ' SQL: ' . $insert_sql);
    }

    // Null değer için özel kontrol
    if ($expires_at === null) {
        $stmt->bind_param("siis", $code, $server_id, $_SESSION['user_id'], $expires_at);
    } else {
        $stmt->bind_param("siis", $code, $server_id, $_SESSION['user_id'], $expires_at);
    }

    if (!$stmt->execute()) {
        throw new Exception('Davet kaydedilemedi: ' . $stmt->error);
    }

    echo json_encode([
        'success' => true,
        'code' => $code
    ]);

} catch (Exception $e) {
    error_log("Davet oluşturma hatası: " . $e->getMessage());
    echo json_encode([
        'error' => $e->getMessage()
    ]);
} 
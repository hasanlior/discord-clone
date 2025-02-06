<?php
session_start();
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

require_once 'config.php';

// Oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Oturum açmanız gerekiyor']));
}

// Server ID kontrolü
if (!isset($_GET['server_id'])) {
    die(json_encode(['error' => 'Sunucu ID gerekli']));
}

$server_id = $_GET['server_id'];
$user_id = $_SESSION['user_id'];

try {
    // Kullanıcının sunucuya erişim yetkisi kontrolü
    $stmt = $conn->prepare("SELECT * FROM server_members WHERE server_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $server_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result->fetch_assoc()) {
        die(json_encode(['error' => 'Bu sunucuya erişim yetkiniz yok']));
    }

    // Sunucu bilgilerini getir
    $stmt = $conn->prepare("SELECT name, icon FROM servers WHERE id = ?");
    $stmt->bind_param("i", $server_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $server = $result->fetch_assoc();

    if (!$server) {
        die(json_encode(['error' => 'Sunucu bulunamadı']));
    }

    // Region'ı varsayılan olarak ekle
    $server['region'] = 'turkey';

    // Başarılı sonuç
    echo json_encode($server);
    exit;

} catch (Exception $e) {
    die(json_encode(['error' => 'Bir hata oluştu: ' . $e->getMessage()]));
} 
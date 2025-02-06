<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once 'config.php';

    // POST verilerini logla
    error_log("POST Verileri: " . print_r($_POST, true));
    error_log("FILES Verileri: " . print_r($_FILES, true));

    // Oturum kontrolü
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Oturum açmanız gerekiyor');
    }

    // POST verilerini kontrol et
    if (!isset($_POST['server_id']) || !isset($_POST['name'])) {
        throw new Exception('Gerekli alanlar eksik');
    }

    $server_id = intval($_POST['server_id']);
    $name = trim($_POST['name']);
    $region = isset($_POST['region']) ? $_POST['region'] : 'turkey';
    $user_id = $_SESSION['user_id'];

    // Sunucu sahibi kontrolü
    $stmt = $conn->prepare("SELECT owner_id FROM servers WHERE id = ?");
    $stmt->bind_param("i", $server_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $server = $result->fetch_assoc();

    if (!$server || $server['owner_id'] != $user_id) {
        throw new Exception('Bu sunucuyu düzenleme yetkiniz yok');
    }

    // Mevcut değerleri kontrol edelim
    $check_stmt = $conn->prepare("SELECT name, region FROM servers WHERE id = ?");
    $check_stmt->bind_param("i", $server_id);
    $check_stmt->execute();
    $current = $check_stmt->get_result()->fetch_assoc();
    
    error_log("Mevcut değerler: " . print_r($current, true));
    error_log("Yeni değerler: name=$name, region=$region");

    // SQL sorgusunu logla
    $sql = "UPDATE servers SET name = ?, region = ? WHERE id = ?";
    error_log("SQL Sorgusu: $sql");
    error_log("Parametreler: name=$name, region=$region, id=$server_id");

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('SQL hazırlama hatası: ' . $conn->error);
    }
    
    $stmt->bind_param("ssi", $name, $region, $server_id);
    $result = $stmt->execute();
    
    // Güncelleme sonucunu logla
    error_log("Güncelleme sonucu: " . ($result ? "Başarılı" : "Başarısız"));
    error_log("Etkilenen satır sayısı: " . $stmt->affected_rows);

    if (!$result) {
        throw new Exception('Güncelleme yapılamadı: ' . $stmt->error);
    }

    if ($stmt->affected_rows === 0) {
        error_log("Uyarı: Hiçbir satır güncellenmedi!");
    }

    // İkon yükleme işlemi
    if (isset($_FILES['icon']) && $_FILES['icon']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['icon']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            throw new Exception('Geçersiz dosya türü');
        }

        $newname = uniqid() . '.' . $ext;
        $upload_dir = '../uploads/server_icons/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        if (move_uploaded_file($_FILES['icon']['tmp_name'], $upload_dir . $newname)) {
            $stmt = $conn->prepare("UPDATE servers SET icon = ? WHERE id = ?");
            $stmt->bind_param("si", $newname, $server_id);
            $stmt->execute();
        } else {
            throw new Exception('Dosya yükleme hatası');
        }
    }

    echo json_encode([
        'success' => true, 
        'message' => 'Sunucu başarıyla güncellendi',
        'debug' => [
            'name' => $name,
            'region' => $region,
            'server_id' => $server_id,
            'affected_rows' => $stmt->affected_rows
        ]
    ]);

} catch (Exception $e) {
    error_log("Hata: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
} 
<?php
session_start();

// Oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        // AJAX isteği ise JSON yanıt döndür
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Oturum açmanız gerekiyor']);
        exit;
    } else {
        // Normal istek ise login sayfasına yönlendir
        header('Location: /login.php');
        exit;
    }
}

// Kullanıcı bilgilerini getir
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_destroy();
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Geçersiz oturum']);
            exit;
        } else {
            header('Location: /login.php');
            exit;
        }
    }
} catch (Exception $e) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Veritabanı hatası']);
        exit;
    } else {
        die('Bir hata oluştu');
    }
} 
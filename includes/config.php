<?php
session_start();

// Veritabanı bağlantı bilgileri
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'deneme_discord';

// Veritabanı bağlantısı
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Bağlantı hatası kontrolü
if ($conn->connect_error) {
    die("Veritabanı bağlantı hatası: " . $conn->connect_error);
}

// UTF-8 karakter seti
$conn->set_charset("utf8mb4");

// Hata raporlama
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Zaman dilimi
date_default_timezone_set('Europe/Istanbul');

// CORS ve güvenlik başlıkları
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// XSS koruması için fonksiyon
function clean($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Oturum kontrolü fonksiyonu
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        die(json_encode(['error' => 'Unauthorized']));
    }
    return $_SESSION['user_id'];
}

// Debug fonksiyonu
function debug($data) {
    header('Content-Type: application/json');
    die(json_encode($data));
}
?> 
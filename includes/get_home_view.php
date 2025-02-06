<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Unauthorized']));
}

// Ana sayfa HTML'ini döndür (chat.php'deki ana sayfa görünümü)
include '../templates/home_view.php'; 
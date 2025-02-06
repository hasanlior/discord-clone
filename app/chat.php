<?php
require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Kullanıcının üye olduğu sunucuları getir
$sql = "SELECT s.* FROM servers s 
        LEFT JOIN server_members sm ON s.id = sm.server_id 
        WHERE sm.user_id = ? OR s.owner_id = ?
        ORDER BY s.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$servers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Aktif sunucuyu al
$server_id = isset($_GET['server']) ? (int)$_GET['server'] : 0;
$server = [];

if ($server_id) {
    $stmt = $conn->prepare("SELECT * FROM servers WHERE id = ?");
    $stmt->bind_param("i", $server_id);
    $stmt->execute();
    $server = $stmt->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duygula</title>
    <!-- Önce jQuery'yi yükleyelim -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="../assets/css/chat.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        const currentUserId = <?php echo $_SESSION['user_id']; ?>;
        let currentServerId = <?php echo isset($server['id']) ? $server['id'] : 'null'; ?>;
    </script>
</head>
<body>
    <!-- Loading Screen -->
    <div id="loadingScreen" class="loading-screen">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <div class="loading-text">Duygula</div>
        </div>
    </div>

    <div class="app-container" style="display: none;">
        <!-- Sol Sidebar (Sunucu Listesi) -->
        <div class="servers-sidebar">
            <!-- Ana Sayfa Butonu -->
            <div class="server-item home active">
                <i class="fas fa-compass"></i>
            </div>
            <div class="servers-divider"></div>
            
            <!-- Sunucu Listesi -->
            <?php foreach ($servers as $server): ?>
                <div class="server-item" 
                     data-server-id="<?= $server['id'] ?>"
                     title="<?= htmlspecialchars($server['name']) ?>">
                    <?php if ($server['icon']): ?>
                        <img src="../uploads/server_icons/<?= $server['icon'] ?>" 
                             alt="<?= htmlspecialchars($server['name']) ?>"
                             onerror="this.textContent='<?= strtoupper(substr($server['name'], 0, 1)) ?>'">
                    <?php else: ?>
                        <?= strtoupper(substr($server['name'], 0, 1)) ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <!-- Sunucu Ekleme Butonu -->
            <div class="server-item add-server">
                <i class="fas fa-plus"></i>
            </div>
        </div>

        <!-- Ana Sayfa İçeriği -->
        <div class="main-content">
            <div class="friends-header">
                <div class="friends-nav">
                    <div class="friends-tab active">
                        <i class="fas fa-user-friends"></i>
                        Arkadaşlar
                    </div>
                    <div class="friends-tab">
                        <i class="fas fa-inbox"></i>
                        Gelen Kutusu
                    </div>
                    <div class="friends-tab">
                        <i class="fas fa-calendar-alt"></i>
                        Etkinlikler
                    </div>
                    <div class="friends-tab">
                        <span class="nitro-icon">
                            <i class="fas fa-rocket"></i>
                        </span>
                        Duygula Premium
                    </div>
                </div>
                <div class="friends-actions">
                    <button class="new-message-btn">
                        <i class="fas fa-plus"></i>
                    </button>
                    <div class="friends-search">
                        <input type="text" placeholder="Ara veya sohbet başlat">
                        <i class="fas fa-search"></i>
                    </div>
                </div>
            </div>

            <div class="friends-container">
                <div class="friends-sidebar">
                    <div class="direct-messages">
                        <div class="dm-header">
                            <span>ÖZEL MESAJLAR</span>
                            <i class="fas fa-plus"></i>
                        </div>
                        
                        <!-- Örnek DM'ler -->
                        <div class="dm-item active">
                            <div class="dm-avatar">
                                <img src="../assets/img/default-avatar.png" alt="User" style="width: 100%; height: 100%; border-radius: 100%;">
                                <span class="status-indicator online"></span>
                            </div>
                            <div class="dm-info">
                                <span class="dm-name">Ahmet Yılmaz</span>
                                <span class="dm-status">Çevrimiçi</span>
                            </div>
                        </div>

                        <div class="dm-item">
                            <div class="dm-avatar">
                                <img src="../assets/img/default-avatar.png" alt="User" style="width: 100%; height: 100%; border-radius: 100%;">
                                <span class="status-indicator idle"></span>
                            </div>
                            <div class="dm-info">
                                <span class="dm-name">Mehmet Demir</span>
                                <span class="dm-status">Boşta</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="friends-content">
                    <div class="friends-section active">
                        <div class="section-header">
                            <h2>Çevrimiçi — 2</h2>
                        </div>
                        
                        <!-- Çevrimiçi arkadaşlar -->
                        <div class="friend-list">
                            <div class="friend-item">
                                <div class="friend-avatar">
                                    <img src="../assets/img/default-avatar.png" alt="User" style="width: 100%; height: 100%; border-radius: 100%;">
                                    <span class="status-indicator online"></span>
                                </div>
                                <div class="friend-info">
                                    <span class="friend-name">Ahmet Yılmaz</span>
                                    <span class="friend-activity">Visual Studio Code'da çalışıyor</span>
                                </div>
                                <div class="friend-actions">
                                    <button class="action-btn" title="Mesaj">
                                        <i class="fas fa-comment"></i>
                                    </button>
                                    <button class="action-btn" title="Sesli Arama">
                                        <i class="fas fa-phone"></i>
                                    </button>
                                    <button class="action-btn" title="Görüntülü Arama">
                                        <i class="fas fa-video"></i>
                                    </button>
                                    <button class="action-btn more" title="Diğer">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="section-header">
                            <h2>Çevrimdışı — 1</h2>
                        </div>
                        
                        <!-- Çevrimdışı arkadaşlar -->
                        <div class="friend-list offline">
                            <div class="friend-item">
                                <div class="friend-avatar">
                                    <img src="../assets/img/default-avatar.png" alt="User" style="width: 100%; height: 100%; border-radius: 100%;">
                                    <span class="status-indicator offline"></span>
                                </div>
                                <div class="friend-info">
                                    <span class="friend-name">Ali Veli</span>
                                    <span class="friend-activity">Çevrimdışı</span>
                                </div>
                                <div class="friend-actions">
                                    <button class="action-btn" title="Mesaj">
                                        <i class="fas fa-comment"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Premium section -->
                    <div class="premium-section" style="display: none;">
                        <!-- Hero Section -->
                        <div class="premium-hero">
                            <div class="premium-hero-content">
                                <h1>Duygula Premium</h1>
                                <p>Sohbet deneyimini bir üst seviyeye taşı</p>
                            </div>
                            <div class="premium-hero-image">
                                <i class="fas fa-rocket"></i>
                            </div>
                        </div>

                        <!-- Plans Section -->
                        <div class="premium-plans">
                            <!-- Duygula+ Plan -->
                            <div class="premium-plan">
                                <div class="plan-content">
                                    <div class="plan-name">
                                        <i class="fas fa-plus"></i>
                                        <h3>Duygula+</h3>
                                    </div>
                                    <div class="plan-price">
                                        <span class="amount">₺99</span>
                                        <span class="period">/aylık</span>
                                    </div>
                                    <div class="plan-features">
                                        <div class="feature">
                                            <i class="fas fa-video"></i>
                                            <span>HD Görüntülü Görüşme</span>
                                        </div>
                                        <div class="feature">
                                            <i class="fas fa-smile"></i>
                                            <span>Özel Emojiler</span>
                                        </div>
                                        <div class="feature">
                                            <i class="fas fa-upload"></i>
                                            <span>50MB Dosya Paylaşımı</span>
                                        </div>
                                        <div class="feature">
                                            <i class="fas fa-star"></i>
                                            <span>Özel Profil Rozeti</span>
                                        </div>
                                    </div>
                                    <button class="plan-button">Hemen Başla</button>
                                </div>
                            </div>

                            <!-- Duygula Premium+ Plan -->
                            <div class="premium-plan featured">
                                <div class="plan-content">
                                    <div class="plan-name">
                                        <i class="fas fa-crown"></i>
                                        <h3>Duygula Premium+</h3>
                                    </div>
                                    <div class="plan-price">
                                        <span class="amount">₺249</span>
                                        <span class="period">/aylık</span>
                                    </div>
                                    <div class="plan-features">
                                        <div class="feature">
                                            <i class="fas fa-video"></i>
                                            <span>4K Görüntülü Görüşme</span>
                                        </div>
                                        <div class="feature">
                                            <i class="fas fa-smile"></i>
                                            <span>Tüm Özel Emojiler</span>
                                        </div>
                                        <div class="feature">
                                            <i class="fas fa-upload"></i>
                                            <span>500MB Dosya Paylaşımı</span>
                                        </div>
                                        <div class="feature">
                                            <i class="fas fa-crown"></i>
                                            <span>Animasyonlu Rozet</span>
                                        </div>
                                        <div class="feature">
                                            <i class="fas fa-palette"></i>
                                            <span>Özel Profil Tasarımı</span>
                                        </div>
                                        <div class="feature">
                                            <i class="fas fa-rocket"></i>
                                            <span>5x Sunucu Boost</span>
                                        </div>
                                    </div>
                                    <button class="plan-button">Premium'a Yükselt</button>
                                </div>
                            </div>
                        </div>

                        <!-- Features Grid -->
                        <div class="premium-features">
                            <h2>Premium Ayrıcalıkları</h2>
                            <div class="features-grid">
                                <div class="feature-card">
                                    <i class="fas fa-video"></i>
                                    <h3>HD Görüşme</h3>
                                    <p>4K kalitesinde görüntülü görüşme</p>
                                </div>
                                <div class="feature-card">
                                    <i class="fas fa-smile"></i>
                                    <h3>Özel Emojiler</h3>
                                    <p>Tüm sunucularda özel emoji kullanımı</p>
                                </div>
                                <div class="feature-card">
                                    <i class="fas fa-upload"></i>
                                    <h3>Büyük Dosyalar</h3>
                                    <p>100MB'a kadar dosya paylaşımı</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sunucu Paneli -->
        <div class="server-panel" data-server-id="<?php echo isset($server['id']) ? $server['id'] : ''; ?>" style="display: none;">
            <!-- Kanallar Sidebar -->
            <div class="channels-sidebar">
                <div class="server-header">
                    <h3>Sunucu Adı</h3>
                    <i class="fas fa-chevron-down"></i>
                    
                    <!-- Sunucu Ayarları Menüsü -->
                    <div class="server-settings-menu">
                        <?php if (isset($server['owner_id']) && $server['owner_id'] == $_SESSION['user_id']): ?>
                            <div class="menu-item" onclick="openServerSettings()">
                                <i class="fas fa-cog"></i>
                                <span>Sunucu Ayarları</span>
                            </div>
                            <div class="menu-item" onclick="createInviteLink()">
                                <i class="fas fa-user-plus"></i>
                                <span>Davet Linki Oluştur</span>
                            </div>
                            <div class="menu-divider"></div>
                        <?php endif; ?>
                        <div class="menu-item danger" onclick="leaveServer()">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Sunucudan Çık</span>
                        </div>
                    </div>
                </div>
                
                <div class="channels-container">
                    <?php
                    if (isset($server['id'])) {
                        // Kategorileri ve kanalları getir
                        $sql = "SELECT c.*, cc.name as category_name, cc.id as category_id 
                                FROM channels c 
                                LEFT JOIN channel_categories cc ON c.category_id = cc.id 
                                WHERE c.server_id = ? 
                                ORDER BY cc.position, c.position";
                                
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $server['id']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        $categories = [];
                        $uncategorizedChannels = [];
                        
                        while ($row = $result->fetch_assoc()) {
                            if ($row['category_id']) {
                                if (!isset($categories[$row['category_id']])) {
                                    $categories[$row['category_id']] = [
                                        'id' => $row['category_id'],
                                        'name' => $row['category_name'],
                                        'channels' => []
                                    ];
                                }
                                $categories[$row['category_id']]['channels'][] = $row;
                            } else {
                                $uncategorizedChannels[] = $row;
                            }
                        }

                        // Kategorili kanalları göster
                        foreach ($categories as $category) : ?>
                            <div class="channels-category category-<?= $category['id'] ?>">
                                <div class="category-header">
                                    <i class="fas fa-chevron-down"></i>
                                    <span><?= htmlspecialchars($category['name']) ?></span>
                                    <?php if (isset($server['owner_id']) && $server['owner_id'] == $_SESSION['user_id']): ?>
                                        <i class="fas fa-plus" onclick="showCreateChannelModal()"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="channel-list">
                                    <?php foreach ($category['channels'] as $channel): ?>
                                        <div class="channel-item" data-channel-id="<?= $channel['id'] ?>" onclick="showChannel(<?= $channel['id'] ?>)">
                                            <i class="fas <?= $channel['type'] === 'voice' ? 'fa-volume-up' : 'fa-hashtag' ?>"></i>
                                            <span><?= htmlspecialchars($channel['name']) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach;

                        // Kategorisiz kanalları göster
                        if (!empty($uncategorizedChannels)) : ?>
                            <div class="channels-category">
                                <div class="category-header">
                                    <i class="fas fa-chevron-down"></i>
                                    <span>Genel Kanallar</span>
                                    <?php if (isset($server['owner_id']) && $server['owner_id'] == $_SESSION['user_id']): ?>
                                        <i class="fas fa-plus" onclick="showCreateChannelModal()"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="channel-list">
                                    <?php foreach ($uncategorizedChannels as $channel): ?>
                                        <div class="channel-item" data-channel-id="<?= $channel['id'] ?>" onclick="showChannel(<?= $channel['id'] ?>)">
                                            <i class="fas <?= $channel['type'] === 'voice' ? 'fa-volume-up' : 'fa-hashtag' ?>"></i>
                                            <span><?= htmlspecialchars($channel['name']) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif;
                    }
                    ?>
                </div>

                <div class="user-controls">
                    <div class="user-info">
                        <div class="user-avatar">
                            <?php if (isset($_SESSION['user_avatar']) && $_SESSION['user_avatar']): ?>
                                <img src="../uploads/avatars/<?= htmlspecialchars($_SESSION['user_avatar']) ?>" alt="Avatar">
                            <?php else: ?>
                                <div class="default-avatar">
                                    <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            <span class="status-indicator online"></span>
                        </div>
                        <div class="user-details">
                            <div class="username"><?= htmlspecialchars($_SESSION['username']) ?></div>
                            <div class="user-id">#<?= str_pad($_SESSION['user_id'], 4, '0', STR_PAD_LEFT) ?></div>
                        </div>
                    </div>
                    <div class="audio-controls">
                        <button class="audio-btn mic-btn" title="Mikrofonu Aç/Kapat">
                            <i class="fas fa-microphone"></i>
                        </button>
                        <button class="audio-btn headphone-btn" title="Sesi Aç/Kapat">
                            <i class="fas fa-headphones"></i>
                        </button>
                        <button class="audio-btn settings-btn" title="Kullanıcı Ayarları">
                            <i class="fas fa-cog"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Sohbet Alanı -->
            <div class="chat-container">
                <div class="chat-header">
                    <div class="channel-info">
                        <i class="fas fa-hashtag"></i>
                        <span>genel</span>
                    </div>
                </div>
                
                <div class="chat-messages">
                    <!-- Mesajlar buraya gelecek -->
                </div>
                
                <div class="chat-input">
                    <form id="messageForm" onsubmit="return false;">
                        <div class="chat-input-buttons">
                            <button type="button" class="chat-input-button" title="Dosya Ekle">
                                <i class="fas fa-plus-circle"></i>
                            </button>
                        </div>
                        
                        <input type="text" placeholder="Mesajınızı yazın..." required>
                        
                        <div class="chat-input-actions">
                            <button type="button" class="chat-input-button gif-button" title="GIF">
                                GIF
                            </button>
                            <button type="button" class="chat-input-button" title="Dosya Ekle">
                                <i class="fas fa-gift"></i>
                            </button>
                            <button type="button" class="chat-input-button" title="Çıkartma">
                                <i class="far fa-sticky-note"></i>
                            </button>
                            <button type="button" class="chat-input-button emoji-button" title="Emoji">
                                <i class="far fa-smile"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Üye Listesi -->
            <div class="members-sidebar">
                <div class="members-header">
                    <h3>ÜYE LİSTESİ - <span class="member-count">0</span></h3>
                </div>
                <div class="members-list">
                    <!-- Üyeler buraya gelecek -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Container -->
    <div id="modalContainer">
        <!-- Modal içerikleri buraya gelecek -->
    </div>

    <!-- Sunucu Oluşturma Modal -->
    <div id="createServerModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Yeni Sunucu Oluştur</h2>
                <span class="close" onclick="closeModal('createServerModal')">&times;</span>
            </div>
            <form id="createServerForm" onsubmit="handleCreateServer(event)">
                <div class="form-group">
                    <label for="serverIcon">Sunucu İkonu</label>
                    <label for="serverIcon" class="icon-upload">
                        <img id="iconPreview" src="../assets/images/default_server.png" alt="Server Icon">
                        <input type="file" id="serverIcon" name="icon" accept="image/*" onchange="previewIcon(this)">
                        <div class="upload-overlay">
                            <i class="fas fa-camera"></i>
                            <span>Değiştir</span>
                        </div>
                    </label>
                </div>
                <div class="form-group">
                    <label for="serverName">SUNUCU ADI</label>
                    <input type="text" id="serverName" name="serverName" placeholder="Sunucu adını girin">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('createServerModal')">İptal</button>
                    <button type="submit" class="btn-primary">Oluştur</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Kana Oluşturma Modal -->
    <div id="createChannelModal" class="modal" style="display: none;">
        <div class="modal-content channel-modal">
            <div class="modal-header">
                <h2>Kanal Oluştur</h2>
                <span class="close" onclick="closeModal('createChannelModal')">&times;</span>
            </div>
            <form id="createChannelForm" onsubmit="handleCreateChannel(event)">
                <div class="modal-body">
                    <div class="form-group">
                        <label>KANAL TÜRÜ</label>
                        <div class="channel-type-selector">
                            <label class="type-option">
                                <input type="radio" name="type" value="text" checked>
                                <div class="type-content">
                                    <i class="fas fa-hashtag"></i>
                                    <div class="type-info">
                                        <span class="type-title">Metin Kanalı</span>
                                        <span class="type-desc">Mesajlaşma, paylaşım ve sohbet</span>
                                    </div>
                                </div>
                            </label>
                            <label class="type-option">
                                <input type="radio" name="type" value="voice">
                                <div class="type-content">
                                    <i class="fas fa-volume-up"></i>
                                    <div class="type-info">
                                        <span class="type-title">Ses Kanalı</span>
                                        <span class="type-desc">Sesli sohbet ve görüşme</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="channelCategory">KATEGORİ</label>
                        <div class="select-wrapper">
                            <select id="channelCategory" name="category_id">
                                <option value="">Kategori Seç</option>
                            </select>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <button type="button" class="btn-link" onclick="showCreateCategoryModal()">
                            <i class="fas fa-plus"></i> Yeni Kategori Oluştur
                        </button>
                    </div>

                    <div class="form-group">
                        <label for="channelName">KANAL ADI</label>
                        <div class="input-wrapper">
                            <i class="fas fa-hashtag"></i>
                            <input type="text" id="channelName" name="name" required 
                                   placeholder="yeni-kanal" maxlength="100"
                                   pattern="[a-zA-Z0-9\-]+" 
                                   title="Sadece harf, rakam ve tire kullanabilirsiniz">
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('createChannelModal')">İptal</button>
                    <button type="submit" class="btn-primary">Oluştur</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Kategori Oluşturma Modal -->
    <div id="createCategoryModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Kategori Oluştur</h2>
                <span class="close" onclick="closeModal('createCategoryModal')">&times;</span>
            </div>
            <form id="createCategoryForm" onsubmit="handleCreateCategory(event)">
                <div class="form-group">
                    <label for="categoryName">Kategori Adı</label>
                    <input type="text" id="categoryName" name="name" required 
                           placeholder="Yeni Kategori" maxlength="100"
                           pattern="[a-zA-Z0-9\s]+" 
                           title="Sadece harf, rakam ve boşluk kullanabilirsiniz">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('createCategoryModal')">İptal</button>
                    <button type="submit" class="btn-primary">Oluştur</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Sunucu Ayarları Modal -->
    <div id="serverSettingsModal" class="fullscreen-modal">
        <div class="settings-container">
            <!-- Sol Sidebar -->
            <div class="settings-sidebar">
                <div class="settings-header">
                    <h3>Sunucu Ayarları</h3>
                    <button class="close-settings" onclick="closeServerSettings()">
                        <i class="fas fa-times"></i>
                        <span>ESC</span>
                    </button>
                </div>
                <div class="settings-nav">
                    <div class="nav-item active" data-section="overview">
                        <i class="fas fa-info-circle"></i>
                        Genel Bakış
                    </div>
                    <div class="nav-item" data-section="roles">
                        <i class="fas fa-user-tag"></i>
                        Roller
                    </div>
                    <div class="nav-item" data-section="permissions">
                        <i class="fas fa-shield-alt"></i>
                        İzinler
                    </div>
                    <div class="nav-item danger" data-section="delete">
                        <i class="fas fa-trash"></i>
                        Sunucuyu Sil
                    </div>
                </div>
            </div>

            <!-- Sağ İçerik Alanı -->
            <div class="settings-content">
                <!-- Genel Bakış -->
                <div class="settings-section active" id="overview">
                    <h2>Sunucu Genel Bakış</h2>
                    <div class="description">
                        Sunucunuzun temel ayarlarını buradan yönetebilirsiniz.
                    </div>
                    
                    <form id="serverSettingsForm">
                        <!-- Server Icon Section -->
                        <div class="server-icon-section">
                            <div class="server-icon-upload">
                                <img id="serverIconPreview" src="../assets/images/default_server.png" alt="Server Icon">
                                <input type="file" id="serverIconInput" accept="image/*">
                                <div class="upload-overlay">
                                    <i class="fas fa-camera"></i>
                                    <span>RESİM YÜKLE</span>
                                </div>
                            </div>
                            <div class="server-icon-info">
                                <h3>Sunucu İkonu</h3>
                                <p>En az 128x128 piksel önerilir. PNG, JPG veya GIF desteklenir. Maksimum boyut: 10MB.</p>
                            </div>
                        </div>

                        <div class="settings-divider"></div>

                        <div class="form-group">
                            <label for="serverName">Sunucu Adı</label>
                            <input type="text" id="serverName" name="name" maxlength="100" required>
                        </div>

                        <div class="settings-divider"></div>

                        <!-- Server Region Section -->
                        <div class="form-group">
                            <label>SUNUCU BÖLGESİ</label>
                            <div class="description">
                                Sesli sohbet için sunucu bölgesini seçin.
                            </div>
                            <div class="custom-select">
                                <div class="select-selected">
                                    <i class="fas fa-flag" style="color: #e30a17;"></i> 
                                    <span>Türkiye</span>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="select-items">
                                    <div class="select-option" data-value="turkey">
                                        <i class="fas fa-flag" style="color: #e30a17;"></i> Türkiye
                                    </div>
                                    <div class="select-option" data-value="europe">
                                        <i class="fas fa-flag" style="color: #003399;"></i> Avrupa
                                    </div>
                                    <div class="select-option" data-value="us-east">
                                        <i class="fas fa-flag" style="color: #002868;"></i> ABD Doğu
                                    </div>
                                    <div class="select-option" data-value="us-west">
                                        <i class="fas fa-flag" style="color: #002868;"></i> ABD Batı
                                    </div>
                                    <div class="select-option" data-value="brazil">
                                        <i class="fas fa-flag" style="color: #009c3b;"></i> Brezilya
                                    </div>
                                    <div class="select-option" data-value="singapore">
                                        <i class="fas fa-flag" style="color: #ef3340;"></i> Singapur
                                    </div>
                                    <div class="select-option" data-value="japan">
                                        <i class="fas fa-flag" style="color: #bc002d;"></i> Japonya
                                    </div>
                                </div>
                                <input type="hidden" id="serverRegion" value="turkey">
                            </div>
                        </div>

                        <button type="submit" class="settings-save">Değişiklikleri Kaydet</button>

                        <div class="danger-zone">
                            <h3>Tehlikeli Bölge</h3>
                            <p>Bu sunucuyu silmek geri alınamaz. Lütfen emin olun.</p>
                            <button class="delete-button" onclick="deleteServer()">
                                Sunucuyu Sil
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Roller sekmesi için HTML -->
                <div class="settings-section" id="roles">
                    <div class="roles-header">
                        <h2>Roller</h2>
                        <button class="create-role-btn">
                            <i class="fas fa-plus"></i>
                            Yeni Rol
                        </button>
                    </div>

                    <div class="roles-list">
                        <!-- @everyone rolü -->
                        <div class="role-item default-role">
                            <div class="role-info">
                                <div class="role-name">@everyone</div>
                                <div class="role-members">2 üye</div>
                            </div>
                            <div class="role-actions">
                                <button class="role-view-btn" title="Rolü Görüntüle">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Örnek roller -->
                        <div class="role-item">
                            <div class="role-color" style="background-color: #5865f2;"></div>
                            <div class="role-info">
                                <div class="role-name">Admin</div>
                                <div class="role-members">1 üye</div>
                            </div>
                            <div class="role-actions">
                                <button class="role-edit-btn" title="Rolü Düzenle">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                                <button class="role-delete-btn" title="Rolü Sil">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stil eklentileri -->
                <style>
                /* Roller sekmesi stilleri */
                .roles-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 20px;
                    padding: 0 16px;
                }

                .create-role-btn {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    padding: 8px 16px;
                    background-color: #5865f2;
                    color: white;
                    border: none;
                    border-radius: 4px;
                    font-size: 14px;
                    cursor: pointer;
                    transition: background-color 0.2s;
                }

                .create-role-btn:hover {
                    background-color: #4752c4;
                }

                .roles-list {
                    display: flex;
                    flex-direction: column;
                    gap: 2px;
                }

                .role-item {
                    display: flex;
                    align-items: center;
                    padding: 8px 16px;
                    background-color: #2f3136;
                    border-radius: 4px;
                    cursor: pointer;
                    transition: background-color 0.2s;
                }

                .role-item:hover {
                    background-color: #36393f;
                }

                .role-color {
                    width: 12px;
                    height: 12px;
                    border-radius: 50%;
                    margin-right: 12px;
                }

                .role-info {
                    flex: 1;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }

                .role-name {
                    color: #dcddde;
                    font-weight: 500;
                }

                .role-members {
                    color: #96989d;
                    font-size: 12px;
                }

                .role-actions {
                    display: flex;
                    gap: 8px;
                    opacity: 0;
                    transition: opacity 0.2s;
                }

                .role-item:hover .role-actions {
                    opacity: 1;
                }

                .role-actions button {
                    background: none;
                    border: none;
                    color: #b9bbbe;
                    padding: 4px;
                    border-radius: 4px;
                    cursor: pointer;
                    transition: all 0.2s;
                }

                .role-actions button:hover {
                    color: #dcddde;
                    background-color: rgba(79, 84, 92, 0.32);
                }

                .role-delete-btn:hover {
                    color: #ed4245 !important;
                    background-color: rgba(237, 66, 69, 0.1) !important;
                }

                .default-role {
                    opacity: 0.8;
                }

                .default-role .role-actions {
                    opacity: 1;
                }

                /* Rol düzenleme modal stilleri için yer tutucu */
                .role-edit-modal {
                    max-width: 600px;
                }

                .permissions-list {
                    display: grid;
                    grid-template-columns: repeat(2, 1fr);
                    gap: 8px;
                    margin-top: 16px;
                }

                .permission-item {
                    display: flex;
                    align-items: center;
                    padding: 8px;
                    background-color: #2f3136;
                    border-radius: 4px;
                }

                .permission-item label {
                    margin-left: 8px;
                    color: #dcddde;
                    user-select: none;
                }
                </style>

                <!-- İzinler sekmesi için HTML -->
                <div class="settings-section" id="permissions">
                    <div class="permissions-header">
                        <h2>İzinler</h2>
                        <button class="add-permission-btn">
                            <i class="fas fa-plus"></i>
                            Yeni İzin
                        </button>
                    </div>

                    <div class="permissions-list">
                        <!-- Örnek izinler -->
                        <div class="permission-item">
                            <input type="checkbox" id="permission1">
                            <label for="permission1">Mesaj gönderme</label>
                        </div>
                        <div class="permission-item">
                            <input type="checkbox" id="permission2">
                            <label for="permission2">Sesli arama</label>
                        </div>
                        <div class="permission-item">
                            <input type="checkbox" id="permission3">
                            <label for="permission3">Görüntülü arama</label>
                        </div>
                        <div class="permission-item">
                            <input type="checkbox" id="permission4">
                            <label for="permission4">Dosya paylaşma</label>
                        </div>
                    </div>
                </div>

                <!-- Silme bölümü -->
                <div class="danger-zone">
                    <h3>Tehlikeli Bölge</h3>
                    <p>Bu sunucuyu silmek geri alınamaz. Lütfen emin olun.</p>
                    <button class="delete-button" onclick="deleteServer()">
                        Sunucuyu Sil
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../assets/js/ajax.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Premium tab'ına tıklama
        document.querySelector('.friends-tab .nitro-icon').parentElement.addEventListener('click', function() {
            // Tüm sekmelerdeki active class'ını kaldır
            document.querySelectorAll('.friends-tab').forEach(tab => tab.classList.remove('active'));
            // Tıklanan sekmeye active class'ı ekle
            this.classList.add('active');
            
            // Ana içeriği gizle
            document.querySelector('.friends-section').style.display = 'none';
            // Premium section'ı göster
            document.querySelector('.premium-section').style.display = 'block';
        });

        // Diğer sekmelere tıklandığında
        document.querySelectorAll('.friends-tab:not(:has(.nitro-icon))').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.friends-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                document.querySelector('.premium-section').style.display = 'none';
                document.querySelector('.friends-section').style.display = 'block';
            });
        });
    });

    // Modal fonksiyonları
    function showCreateCategoryModal() {
        document.getElementById('modalContainer').style.display = 'flex';
        document.getElementById('createCategoryModal').style.display = 'block';
    }

    function hideModal() {
        document.getElementById('modalContainer').style.display = 'none';
        document.getElementById('createCategoryModal').style.display = 'none';
    }

    // ESC tuşu ile modalı kapatma
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            hideModal();
        }
    });

    // Modal dışına tıklayınca kapatma
    document.getElementById('modalContainer').addEventListener('click', function(event) {
        if (event.target === this) {
            hideModal();
        }
    });
    </script>
</body>
</html> 


<style>
    /* Chat container */
    .chat-container {
        display: flex;
        flex-direction: column;
        flex: 1;
        background-color: #36393f;
    }

    /* Chat header */
    .chat-header {
        height: 48px;
        padding: 0 16px;
        display: flex;
        align-items: center;
        border-bottom: 1px solid #202225;
        box-shadow: 0 1px 1px rgba(0,0,0,0.1);
    }

    .channel-info {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #fff;
        font-weight: 600;
    }

    /* Mesaj alanı */
    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 16px 0;
    }

    /* Mesaj input alanı */
    .chat-input {
        padding: 0px 16px 0px;
        margin: 0 16px 24px;
        background-color: #40444b;
        border-radius: 8px;
        position: relative;
    }

    .chat-input form {
        display: flex;
        align-items: center;
        height: 44px; /* Sabit yükseklik verdik */
        padding: 0 8px; /* Yatay padding */
    }

    .chat-input-buttons {
        display: flex;
        align-items: center;
        padding: 0 8px 0 0;
        gap: 4px;
        height: 100%; /* Form yüksekliğine göre ayarladık */
    }

    .chat-input-button {
        width: 32px;
        height: 32px;
        color: #b9bbbe;
        background: none;
        border: none;
        padding: 4px;
        font-size: 20px;
        cursor: pointer;
        transition: color 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
    }

    .chat-input-button:hover {
        color: #dcddde;
    }

    .chat-input input {
        flex: 1;
        background: none;
        border: none;
        color: #dcddde;
        font-size: 1rem;
        height: 100%; /* Form yüksekliğine göre ayarladık */
        outline: none;
        margin: 0;
        line-height: 1.375rem;
    }

    .chat-input input::placeholder {
        color: #72767d;
    }

    .chat-input-actions {
        display: flex;
        align-items: center;
        padding: 0 0 0 8px;
        gap: 8px;
        height: 100%; /* Form yüksekliğine göre ayarladık */
    }

    .chat-input-divider {
        width: 1px;
        height: 24px;
        background-color: #4f545c;
        margin: 0 4px;
    }

    /* Emoji butonu için özel stil */
    .emoji-button {
        color: #b9bbbe;
        transition: color 0.2s;
    }

    .emoji-button:hover {
        color: #dcddde;
    }

    /* GIF butonu için özel stil */
    .gif-button {
        color: #b9bbbe;
        font-weight: 600;
        font-size: 14px;
    }

    .gif-button:hover {
        color: #dcddde;
    }

    /* Mesaj stilleri */
    .message {
        display: flex;
        padding: 8px 16px;
        margin: 4px 0;
        gap: 16px;
        transition: background-color 0.1s ease;
    }

    .message:hover {
        background-color: rgba(4, 4, 5, 0.07);
    }

    /* Ardışık mesajlar için stil */
    .message + .message {
        margin-top: 8px;
    }

    .message-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        overflow: hidden;
        flex-shrink: 0;
    }

    .message-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .default-avatar {
        width: 100%;
        height: 100%;
        background-color: #5865f2;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 16px;
    }

    .message-content {
        flex: 1;
        min-width: 0;
    }

    .message-header {
        display: flex;
        align-items: baseline;
        gap: 8px;
        margin-bottom: 4px;
    }

    .username {
        font-size: 1rem;
        font-weight: 500;
        color: #fff;
        cursor: pointer;
    }

    .username:hover {
        text-decoration: underline;
    }

    .timestamp {
        font-size: 0.75rem;
        color: #72767d;
    }

    .message-text {
        color: #dcddde;
        font-size: 1rem;
        line-height: 1.375rem;
        word-wrap: break-word;
    }

    .members-sidebar {
        background-color: #2f3136;
        width: 240px;
        flex-shrink: 0;
        overflow-y: auto;
    }

    .members-header {
        padding: 16px;
        color: #96989d;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .member-group {
        margin-bottom: 16px;
    }

    .member-role {
        padding: 8px 16px;
        color: #96989d;
        font-size: 12px;
        font-weight: 600;
    }

    .member-item {
        display: flex;
        align-items: center;
        padding: 8px 16px;
        gap: 12px;
        cursor: pointer;
    }

    .member-item:hover {
        background-color: rgba(79, 84, 92, 0.16);
    }

    .member-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        overflow: hidden;
    }

    .member-name {
        color: #dcddde;
        font-size: 14px;
    }

    .category-header {
        display: flex;
        align-items: center;
        padding: 8px;
        color: #96989d;
        cursor: pointer;
        user-select: none;
        font-size: 13px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .category-header:hover {
        color: #dcddde;
    }

    .category-header i.fa-chevron-down {
        margin-right: 8px;
        transition: transform 0.2s ease;
    }

    .channel-list {
        transition: height 0.2s ease;
    }

    .channels-category {
        margin-bottom: 8px;
    }

    .channel-item {
        display: flex;
        align-items: center;
        padding: 6px 8px;
        margin: 1px 0;
        border-radius: 4px;
        color: #96989d;
        cursor: pointer;
        gap: 6px;
    }

    .channel-item:hover {
        background-color: rgba(79, 84, 92, 0.16);
        color: #dcddde;
    }

    .channel-item.active {
        background-color: rgba(79, 84, 92, 0.32);
        color: #fff;
    }

    .channel-item i {
        font-size: 16px;
        width: 16px;
        text-align: center;
        margin-top: 1px;
    }

    .channel-item span {
        font-size: 14px;
        font-weight: 500;
    }

    /* Sunucu Ayarları Menüsü için CSS */
    .server-header {
        position: relative;
    }

    .server-settings-menu {
        position: absolute;
        top: 100%;
        left: 0;
        width: 220px;
        background-color: #18191c;
        border-radius: 4px;
        padding: 6px;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.24);
        z-index: 1000;
        display: none;
    }

    .server-settings-menu.show {
        display: block;
    }

    .menu-item {
        padding: 8px 12px;
        color: #dcddde;
        display: flex;
        align-items: center;
        gap: 8px;
        border-radius: 2px;
        cursor: pointer;
        font-size: 14px;
    }

    .menu-item:hover {
        background-color: #4752c4;
        color: #fff;
    }

    .menu-item.danger {
        color: #ed4245;
    }

    .menu-item.danger:hover {
        background-color: #ed4245;
        color: #fff;
    }

    .menu-divider {
        height: 1px;
        background-color: #2f3136;
        margin: 4px 0;
    }

    /* Loading spinner */
    .loading-spinner {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: #72767d;
    }

    .loading-spinner i {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Loading state için container'lar */
    .channels-container.loading,
    .chat-messages.loading,
    .members-list.loading {
        position: relative;
        min-height: 100px;
    }

    /* Fullscreen Modal */
    .fullscreen-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: #36393f;
        z-index: 1000;
        display: none;
    }

    .settings-container {
        display: flex;
        height: 100%;
    }

    /* Sol Sidebar */
    .settings-sidebar {
        width: 218px;
        background: #2f3136;
        display: flex;
        flex-direction: column;
    }

    .settings-header {
        padding: 20px 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #202225;
    }

    .settings-header h3 {
        color: #fff;
        font-size: 16px;
        font-weight: 600;
    }

    .close-settings {
        background: none;
        border: none;
        color: #b9bbbe;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 4px 8px;
        border-radius: 4px;
    }

    .close-settings:hover {
        color: #dcddde;
        background: rgba(255,255,255,0.1);
    }

    .close-settings span {
        font-size: 12px;
        opacity: 0.7;
    }

    .settings-nav {
        padding: 8px;
    }

    .nav-item {
        padding: 8px 10px;
        margin: 2px 0;
        color: #b9bbbe;
        cursor: pointer;
        border-radius: 4px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .nav-item:hover {
        background: rgba(255,255,255,0.05);
        color: #dcddde;
    }

    .nav-item.active {
        background: rgba(255,255,255,0.1);
        color: #fff;
    }

    .nav-item.danger {
        color: #ed4245;
    }

    .nav-item.danger:hover {
        background: rgba(237,66,69,0.1);
    }

    /* Sağ İçerik Alanı */
    .settings-content {
        flex: 1;
        padding: 60px 40px;
        overflow-y: auto;
        background: #36393f;
        color: #dcddde;
    }

    .settings-section h2 {
        color: #fff;
        font-size: 20px;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .settings-section .description {
        color: #b9bbbe;
        font-size: 14px;
        margin-bottom: 32px;
    }

    .settings-divider {
        height: 1px;
        background-color: #40444b;
        margin: 40px 0;
    }

    .settings-form {
        display: flex;
        flex-direction: column;
        gap: 24px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .form-group label {
        color: #dcddde;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .form-group .description {
        color: #b9bbbe;
        font-size: 14px;
        margin-bottom: 8px;
    }

    .form-group input {
        background: #40444b;
        border: none;
        border-radius: 3px;
        color: #dcddde;
        padding: 10px;
        font-size: 16px;
        transition: border 0.2s;
        border: 1px solid transparent;
    }

    .form-group input:focus {
        outline: none;
        border-color: #5865f2;
    }

    .form-group input:hover {
        border-color: #202225;
    }

    /* Server Icon Upload */
    .server-icon-section {
        display: flex;
        gap: 24px;
        align-items: flex-start;
    }

    .server-icon-upload {
        position: relative;
        width: 100px;
        height: 100px;
        border-radius: 50%;
        overflow: hidden;
        cursor: pointer;
        background: #40444b;
        border: 5px solid #36393f;
        box-shadow: 0 0 0 1px rgba(4,4,5,0.15);
        transition: all 0.2s ease;
    }

    .server-icon-upload:hover {
        box-shadow: 0 0 0 1px #5865f2;
    }

    .server-icon-upload img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: filter 0.2s;
    }

    .server-icon-upload:hover img {
        filter: brightness(0.7);
    }

    .upload-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: rgba(0,0,0,0.7);
        opacity: 0;
        transition: opacity 0.2s;
    }

    .server-icon-upload:hover .upload-overlay {
        opacity: 1;
    }

    .upload-overlay i {
        color: #fff;
        font-size: 24px;
        margin-bottom: 8px;
    }

    .upload-overlay span {
        color: #fff;
        font-size: 10px;
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    /* Server Region Select Styles */
    .form-select {
        background: #40444b;
        border: 1px solid transparent;
        border-radius: 3px;
        color: #dcddde;
        padding: 10px 12px;
        font-size: 16px;
        width: 100%;
        cursor: pointer;
        appearance: none;
        -webkit-appearance: none;
        position: relative;
        transition: border-color 0.2s;
    }

    .form-select-container {
        position: relative;
    }

    .form-select-container::after {
        content: '\f078';
        font-family: 'Font Awesome 5 Free';
        font-weight: 900;
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #b9bbbe;
        pointer-events: none;
    }

    .form-select:hover {
        border-color: #202225;
    }

    .form-select:focus {
        outline: none;
        border-color: #5865f2;
    }

    .form-select option {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px;
        background: #2f3136;
        color: #dcddde;
    }

    .form-select option i {
        margin-right: 8px;
    }

    .form-select-container {
        position: relative;
    }

    .form-select-container::after {
        content: '\f078';
        font-family: 'Font Awesome 5 Free';
        font-weight: 900;
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #b9bbbe;
        pointer-events: none;
    }

    /* Region Option Styles */
    .region-option {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px;
    }

    .region-option i {
        font-size: 16px;
        width: 20px;
        text-align: center;
    }

    /* Save Button */
    .settings-save {
        background: #5865f2;
        color: #fff;
        border: none;
        padding: 12px 16px;
        border-radius: 3px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.2s;
        margin-top: 20px;
    }

    .settings-save:hover {
        background: #4752c4;
    }

    .settings-save:disabled {
        background: #3c409b;
        cursor: not-allowed;
        opacity: 0.5;
    }

    /* Delete Section */
    .danger-zone {
        background: rgba(237, 66, 69, 0.1);
        border: 1px solid #ed4245;
        border-radius: 4px;
        padding: 20px;
        margin-top: 40px;
    }

    .danger-zone h3 {
        color: #ed4245;
        font-size: 16px;
        margin-bottom: 8px;
    }

    .danger-zone p {
        color: #b9bbbe;
        font-size: 14px;
        margin-bottom: 16px;
    }

    .delete-button {
        background: #ed4245;
        color: #fff;
        border: none;
        padding: 10px 16px;
        border-radius: 3px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.2s;
    }

    .delete-button:hover {
        background: #c03537;
    }

    /* Custom Select Styles */
    .custom-select {
        position: relative;
        width: 100%;
    }

    .select-selected {
        background: #40444b;
        border: 1px solid transparent;
        border-radius: 3px;
        color: #dcddde;
        padding: 10px 12px;
        font-size: 16px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: border-color 0.2s;
    }

    .select-selected:hover {
        border-color: #202225;
    }

    .select-selected i.fa-chevron-down {
        margin-left: auto;
        font-size: 12px;
        transition: transform 0.2s;
    }

    .select-selected.active i.fa-chevron-down {
        transform: rotate(180deg);
    }

    .select-items {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: #2f3136;
        border-radius: 3px;
        margin-top: 4px;
        box-shadow: 0 8px 16px rgba(0,0,0,0.24);
        display: none;
        z-index: 1000;
    }

    .select-items.show {
        display: block;
    }

    .select-option {
        padding: 10px 12px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: background 0.2s;
    }

    .select-option:hover {
        background: #4752c4;
        color: #fff;
    }

    .select-option i {
        width: 16px;
        text-align: center;
    }

    .invite-section {
        padding: 20px;
    }

    .invite-link-container {
        display: flex;
        gap: 10px;
        margin: 15px 0;
    }

    .invite-link-container input {
        flex: 1;
        padding: 10px;
        border: 1px solid #40444b;
        border-radius: 4px;
        background: #2f3136;
        color: #dcddde;
    }

    .copy-button {
        padding: 10px 15px;
        background: #5865f2;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .copy-button:hover {
        background: #4752c4;
    }

    .invite-options {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }

    .generate-button {
        padding: 10px 15px;
        background: #4f545c;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 500;
    }

    .generate-button:hover {
        background: #5d6269;
    }

    .expire-select select {
        padding: 10px;
        background: #2f3136;
        color: #dcddde;
        border: 1px solid #40444b;
        border-radius: 4px;
        cursor: pointer;
    }

    /* Modal Container Styles */
    #modalContainer {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5); /* Arka plan opaklığını azalttık */
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }

    /* Davet Modal Stilleri */
    .invite-modal {
        position: relative; /* absolute yerine relative yaptık */
        background: #36393f;
        border-radius: 8px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
        max-width: 440px;
        width: 100%;
        margin: 0 auto; /* Ortalamak için */
    }

    .invite-modal .modal-content {
        padding: 24px;
    }

    .invite-modal .modal-header {
        margin-bottom: 16px;
    }

    .invite-modal .modal-header h2 {
        color: #fff;
        font-size: 20px;
        font-weight: 600;
        margin: 0;
    }

    .invite-modal .close {
        color: #b9bbbe;
        font-size: 24px;
        cursor: pointer;
        padding: 0 8px;
    }

    .invite-modal .close:hover {
        color: #dcddde;
    }

    .invite-description {
        padding: 16px;
        color: #dcddde;
        font-size: 14px;
        border-bottom: 1px solid #202225;
    }

    .invite-settings {
        padding: 16px;
        border-bottom: 1px solid #202225;
    }

    .invite-settings h3 {
        color: #b9bbbe;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 8px;
        letter-spacing: 0.02em;
    }

    .invite-setting-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 8px 0;
    }

    .setting-label {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #dcddde;
    }

    .setting-label i {
        color: #b9bbbe;
        width: 16px;
    }

    .invite-settings select {
        background: #2f3136;
        border: none;
        color: #dcddde;
        padding: 8px 12px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        min-width: 150px;
    }

    .invite-settings select:hover {
        background: #36393f;
    }

    .invite-link-section {
        padding: 16px;
    }

    .invite-link-section h3 {
        color: #b9bbbe;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 8px;
        letter-spacing: 0.02em;
    }

    .invite-link-container {
        display: flex;
        gap: 8px;
        margin-bottom: 16px;
    }

    .invite-link-container input {
        flex: 1;
        background: #202225;
        border: 1px solid #202225;
        color: #dcddde;
        padding: 10px;
        border-radius: 4px;
        font-size: 14px;
    }

    .invite-link-container input:focus {
        outline: none;
        border-color: #5865f2;
    }

    .copy-button {
        background: #5865f2;
        color: white;
        border: none;
        padding: 0 16px;
        border-radius: 4px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 500;
        min-width: 100px;
        justify-content: center;
    }

    .copy-button:hover {
        background: #4752c4;
    }

    .copy-button:disabled {
        background: #4f545c;
        cursor: not-allowed;
    }

    .generate-button {
        width: 100%;
        background: #4f545c;
        color: white;
        border: none;
        padding: 10px;
        border-radius: 4px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 500;
    }

    .generate-button:hover {
        background: #5d6269;
    }

    .generate-button i {
        font-size: 14px;
    }

    /* Servers sidebar için güncellenmiş stiller */
    .servers-sidebar {
        position: relative;
    }

    .server-item {
        position: relative;
        width: 48px;
        height: 48px;
        margin: 8px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        background: #36393f;
        color: #dcddde;
        font-size: 18px;
        transition: all 0.2s ease, border-radius 0.3s ease;
    }

    /* Seçim göstergesi için yeni stiller */
    .server-item::before {
        content: '';
        position: absolute;
        left: -16px;
        width: 8px;
        height: 0;
        background-color: #fff;
        border-radius: 0 4px 4px 0;
        transition: height 0.2s ease;
    }

    /* Hover durumu */
    .server-item:hover {
        background-color: #5865F2;
        color: #fff;
        border-radius: 16px;
    }

    .server-item:hover::before {
        height: 20px;
        background-color: #fff;
    }

    /* Aktif durum */
    .server-item.active {
        background-color: #5865F2;
        color: #fff;
        border-radius: 16px;
    }

    .server-item.active::before {
        height: 40px;
        background-color: #fff;
    }

    /* Ana sayfa butonu için özel stiller */
    .server-item.home {
        background: #36393f;
    }

    .server-item.home.active,
    .server-item.home:hover {
        background: #5865F2;
    }

    /* Sunucu resmi varsa */
    .server-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: inherit;
    }

    /* Ana sayfa stilleri */
    .main-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: #36393f;
    }

    .friends-header {
        height: 48px;
        padding: 0 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid #202225;
        background: #36393f;
    }

    .friends-nav {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .friends-tab {
        padding: 8px 16px;
        border-radius: 4px;
        display: flex;
        align-items: center;
        gap: 8px;
        color: #dcddde;
        cursor: pointer;
        transition: background 0.2s;
    }

    .friends-tab:hover {
        background: rgba(79, 84, 92, 0.16);
    }

    .friends-tab.active {
        background: #5865F2;
        color: #fff;
    }

    .friends-actions {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .new-message-btn {
        background: none;
        border: none;
        color: #dcddde;
        font-size: 20px;
        cursor: pointer;
        padding: 8px;
        border-radius: 4px;
    }

    .new-message-btn:hover {
        color: #fff;
        background: rgba(79, 84, 92, 0.16);
    }

    .friends-search {
        position: relative;
    }

    .friends-search input {
        background: #202225;
        border: none;
        padding: 8px 32px 8px 12px;
        border-radius: 4px;
        color: #dcddde;
        width: 240px;
    }

    .friends-search i {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #72767d;
    }

    .friends-container {
        flex: 1;
        display: flex;
    }

    .friends-sidebar {
        width: 240px;
        background: #2f3136;
        border-right: 1px solid #202225;
    }

    .dm-header {
        padding: 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: #96989d;
        font-size: 12px;
        font-weight: 600;
    }

    .dm-header i {
        cursor: pointer;
        padding: 4px;
        border-radius: 4px;
    }

    .dm-header i:hover {
        color: #dcddde;
        background: rgba(79, 84, 92, 0.16);
    }

    .dm-item {
        padding: 8px 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        cursor: pointer;
        border-radius: 4px;
        margin: 0 8px;
    }

    .dm-item:hover {
        background: rgba(79, 84, 92, 0.16);
    }

    .dm-item.active {
        background: rgba(79, 84, 92, 0.32);
    }

    .dm-avatar {
        position: relative;
        width: 32px;
        height: 32px;
    }

    .dm-avatar img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
    }

    .status-indicator {
        position: absolute;
        bottom: -2px;
        right: -2px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        border: 3px solid #2f3136;
    }

    .status-indicator.online { background: #3ba55c; }
    .status-indicator.idle { background: #faa61a; }
    .status-indicator.dnd { background: #ed4245; }
    .status-indicator.offline { background: #747f8d; }

    .dm-info {
        flex: 1;
        min-width: 0;
    }

    .dm-name {
        color: #dcddde;
        font-size: 14px;
        font-weight: 500;
    }

    .dm-status {
        color: #96989d;
        font-size: 12px;
    }

    .friends-content {
        flex: 1;
        padding: 0;
        height: 100%;
    }

    .section-header {
        color: #96989d;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        margin: 16px 0 8px;
    }

    .friend-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .friend-item {
        padding: 16px;
        display: flex;
        align-items: center;
        gap: 16px;
        background: rgba(47, 49, 54, 0.3);
        border-radius: 8px;
        transition: background 0.2s;
    }

    .friend-item:hover {
        background: rgba(47, 49, 54, 0.6);
    }

    .friend-avatar {
        position: relative;
        width: 40px;
        height: 40px;
    }

    .friend-info {
        flex: 1;
    }

    .friend-name {
        color: #fff;
        font-size: 16px;
        font-weight: 600;
    }

    .friend-activity {
        color: #96989d;
        font-size: 14px;
    }

    .friend-actions {
        display: flex;
        gap: 8px;
    }

    .action-btn {
        background: none;
        border: none;
        color: #b9bbbe;
        padding: 8px;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .action-btn:hover {
        color: #dcddde;
        background: rgba(79, 84, 92, 0.32);
    }

    .nitro-icon {
        color: #ff73fa;
    }

    .friend-list.offline .friend-item {
        opacity: 0.6;
    }

    /* Premium section stilleri */
    .premium-section {
        height: calc(100vh - 48px);
        background: #36393f;
        color: #fff;
        padding: 0;
        display: flex;
        flex-direction: column;
    }

    /* Hero Section */
    .premium-hero {
        background: linear-gradient(135deg, #5865F2 0%, #EB459E 100%);
        padding: 30px 40px;
        text-align: center;
        position: relative;
        margin-bottom: 5px;
    }

    .premium-hero h1 {
        font-size: 36px;
        font-weight: 800;
        margin-bottom: 8px;
        color: #fff;
    }

    .premium-hero p {
        font-size: 16px;
        color: rgba(255, 255, 255, 0.9);
    }

    /* Plans Section */
    .premium-plans {
        display: flex;
        justify-content: center;
        gap: 32px;
        padding: 20px;
        max-width: 1200px;
        margin: 0 auto;
    }

    .premium-plan {
        flex: 1;
        max-width: 400px;
        background: #2f3136;
        border-radius: 16px;
        overflow: hidden;
        transition: transform 0.2s;
    }

    .premium-plan.featured {
        background: linear-gradient(45deg, #5865F2, #8B5CF6);
    }

    .plan-content {
        padding: 32px;
    }

    .plan-name {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 24px;
    }

    .plan-name i {
        font-size: 24px;
        color: #5865F2;
    }

    .premium-plan.featured .plan-name i {
        color: #FFD700;
    }

    .plan-name h3 {
        font-size: 24px;
        font-weight: 700;
        margin: 0;
    }

    .plan-price {
        margin-bottom: 32px;
    }

    .plan-price .amount {
        font-size: 36px;
        font-weight: 800;
    }

    .plan-price .period {
        font-size: 16px;
        opacity: 0.8;
    }

    .plan-features {
        display: flex;
        flex-direction: column;
        gap: 16px;
        margin-bottom: 32px;
    }

    .feature {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .feature i {
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(88, 101, 242, 0.1);
        border-radius: 6px;
        color: #5865F2;
        font-size: 12px;
    }

    .premium-plan.featured .feature i {
        background: rgba(255, 255, 255, 0.1);
        color: #fff;
    }

    .feature span {
        font-size: 14px;
        color: #dcddde;
    }

    .premium-plan.featured .feature span {
        color: #fff;
    }

    .plan-button {
        width: 100%;
        padding: 14px;
        border: none;
        border-radius: 8px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        background: #5865F2;
        color: #fff;
    }

    .premium-plan.featured .plan-button {
        background: #fff;
        color: #5865F2;
    }

    .plan-button:hover {
        opacity: 0.9;
        transform: translateY(-2px);
    }

    /* Hover efektleri */
    .premium-plan:hover {
        transform: translateY(-4px);
    }

    .premium-features {
        display: none; /* Premium ayrıcalıkları kaldırıldı */
    }

    /* Modal content pozisyonunu düzeltme */
    .modal-content.channel-modal {
        position: relative;
        width: 100%;
        max-width: 440px;
        margin: 0 auto; /* Ortalama için */
        background: #36393f;
        border-radius: 5px;
        box-shadow: 0 0 10px rgba(0,0,0,0.5);
    }

    #modalContainer {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.85);
        z-index: 1000;
        justify-content: center;
        align-items: center;
    }

    /* Tüm modal içerikleri için ortalama */
    .modal-content.channel-modal,
    .modal-content[role="dialog"] {
        position: relative;
        width: 100%;
        max-width: 440px;
        margin: 0 auto;
        background: #36393f;
        border-radius: 5px;
        box-shadow: 0 0 10px rgba(0,0,0,0.5);
    }

    /* Kullanıcı ses kontrol paneli */
    .user-controls {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 52px;
        background-color: #292b2f;
        display: flex;
        align-items: center;
        padding: 0 8px;
        z-index: 100;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 8px;
        flex: 1;
        min-width: 0;
        padding: 4px;
        margin-right: 4px;
        border-radius: 4px;
        cursor: pointer;
    }

    .user-info:hover {
        background-color: rgba(79, 84, 92, 0.16);
    }

    .user-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        position: relative;
        overflow: visible; /* overflow: hidden'ı kaldırdık */
    }

    .status-indicator {
        position: absolute;
        bottom: -3px;
        right: -3px;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        border: 3px solid #292b2f;
        background-color: #3ba55c;
        z-index: 2; /* z-index ekledik */
    }

    .user-avatar img,
    .default-avatar {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
        z-index: 1; /* z-index ekledik */
    }

    .default-avatar {
        background-color: #7289da;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }

    .user-details {
        flex: 1;
        min-width: 0;
    }

    .username {
        color: #fff;
        font-size: 14px;
        font-weight: 500;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .user-id {
        color: #b9bbbe;
        font-size: 12px;
    }

    .audio-controls {
        display: flex;
        gap: 4px;
    }

    .audio-btn {
        width: 32px;
        height: 32px;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #b9bbbe;
        background: none;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
    }

    .audio-btn:hover {
        color: #dcddde;
        background-color: rgba(79, 84, 92, 0.32);
    }

    .audio-btn.active {
        color: #ed4245;
        background-color: rgba(237, 66, 69, 0.1);
    }

    .audio-btn.active:hover {
        color: #fff;
        background-color: #ed4245;
    }

    .channels-sidebar {
        position: relative;
        padding-bottom: 52px; /* user-controls yüksekliği kadar padding */
    }

    /* Sunucu ayarları sekme stilleri */
    .settings-section {
        display: none;  /* Varsayılan olarak tüm sekmeleri gizle */
    }

    .settings-section.active {
        display: block;  /* Aktif sekmeyi göster */
    }

    .nav-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 10px;
        margin: 2px 0;
        border-radius: 4px;
        cursor: pointer;
        color: #b9bbbe;
        transition: all 0.2s;
    }

    .nav-item:hover {
        background-color: #42464d;
        color: #dcddde;
    }

    .nav-item.active {
        background-color: #42464d;
        color: #fff;
    }

    .nav-item i {
        width: 20px;
        text-align: center;
    }

    .loading-screen {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: #36393f;
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }

    .loading-content {
        text-align: center;
        animation: fadeIn 0.3s ease;
    }

    .loading-spinner {
        width: 40px;
        height: 40px;
        margin: 0 auto 20px;
        border: 3px solid transparent;
        border-top-color: #5865F2;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    .loading-text {
        color: #fff;
        font-size: 24px;
        font-weight: bold;
        font-family: 'Inter', sans-serif;
        letter-spacing: 0.5px;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }
    </style>

    <script>
    // Loading screen kontrolü
    document.addEventListener('DOMContentLoaded', function() {
        const loadingScreen = document.getElementById('loadingScreen');
        const appContainer = document.querySelector('.app-container');
        
        // Minimum 1 saniye göster
        setTimeout(() => {
            if (loadingScreen) loadingScreen.style.display = 'none';
            if (appContainer) appContainer.style.display = 'flex';
        }, 1000);
    });
    </script>
</body>
</html> 
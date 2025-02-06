<?php
require_once 'config.php';

$user_id = checkAuth();
$server_id = isset($_GET['server_id']) ? (int)$_GET['server_id'] : 0;

if (!$server_id) {
    die('Server ID required');
}

// Kategorileri ve kanalları getir
$sql = "SELECT c.*, cc.name as category_name, cc.id as category_id 
        FROM channels c 
        LEFT JOIN channel_categories cc ON c.category_id = cc.id 
        WHERE c.server_id = ? 
        ORDER BY cc.position, c.position";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $server_id);
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

// Sunucu sahibi kontrolü
$sql = "SELECT owner_id FROM servers WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $server_id);
$stmt->execute();
$server = $stmt->get_result()->fetch_assoc();
$isOwner = $server['owner_id'] == $user_id;

// Kategorili kanalları göster
foreach ($categories as $category) : ?>
    <div class="channels-category category-<?= $category['id'] ?>">
        <div class="category-header">
            <i class="fas fa-chevron-down"></i>
            <span><?= htmlspecialchars($category['name']) ?></span>
            <?php if ($isOwner): ?>
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
            <span>GENEL KANALLAR</span>
            <?php if ($isOwner): ?>
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
<?php endif; ?> 
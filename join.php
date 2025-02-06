<?php
require_once 'includes/config.php';

// Davet kodunu al
$invite_code = isset($_GET['code']) ? $_GET['code'] : '';

// Davet kodunu kontrol et
$stmt = $conn->prepare("SELECT i.*, s.name as server_name, s.icon as server_icon, 
    (SELECT COUNT(*) FROM server_members WHERE server_id = s.id) as member_count 
    FROM invites i 
    JOIN servers s ON i.server_id = s.id 
    WHERE i.code = ?");
$stmt->bind_param("s", $invite_code);
$stmt->execute();
$result = $stmt->get_result();
$invite = $result->fetch_assoc();

// Davet geçerli mi kontrol et
$invite_valid = false;
$error_message = '';

if ($invite) {
    // Süre kontrolü
    if ($invite['expires_at'] && strtotime($invite['expires_at']) < time()) {
        $error_message = 'Bu davet linki süresi dolmuş.';
    }
    // Kullanıcı zaten sunucuda mı kontrol et
    else {
        $check_member = $conn->prepare("SELECT * FROM server_members WHERE server_id = ? AND user_id = ?");
        $check_member->bind_param("ii", $invite['server_id'], $_SESSION['user_id']);
        $check_member->execute();
        $member_result = $check_member->get_result();

        if ($member_result->num_rows > 0) {
            $error_message = 'Zaten bu sunucuya üyesiniz.';
        } else {
            $invite_valid = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sunucuya Katıl | Duygula</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #36393f;
            margin: 0;
            font-family: 'Inter', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), 
                        url('assets/images/blurple-mountains.svg') center/cover fixed;
        }

        .join-container {
            background-color: #2f3136;
            border-radius: 8px;
            width: 480px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transform: scale(0.95);
            animation: popIn 0.3s ease forwards;
            overflow: hidden;
        }

        @keyframes popIn {
            0% {
                opacity: 0;
                transform: scale(0.9);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        .server-info {
            padding: 32px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            background: linear-gradient(to bottom, #2f3136, #292b2f);
        }

        .server-icon {
            width: 90px;
            height: 90px;
            border-radius: 20px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 600;
            color: white;
            background: #5865f2;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            transition: transform 0.2s;
        }

        .server-icon:hover {
            transform: scale(1.05);
        }

        .server-icon img {
            width: 100%;
            height: 100%;
            border-radius: 20px;
            object-fit: cover;
        }

        .server-name {
            color: #fff;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 12px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .member-count {
            color: #b9bbbe;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .member-count i {
            font-size: 14px;
        }

        .join-description {
            padding: 20px 32px;
            color: #dcddde;
            font-size: 14px;
            line-height: 1.5;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .join-actions {
            padding: 20px;
            display: flex;
            gap: 12px;
            background: #292b2f;
        }

        .join-button {
            flex: 1;
            padding: 14px;
            border: none;
            border-radius: 4px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .join-button i {
            font-size: 16px;
        }

        .join-button.accept {
            background-color: #5865f2;
            color: white;
        }

        .join-button.accept:hover {
            background-color: #4752c4;
            transform: translateY(-1px);
        }

        .join-button.decline {
            background-color: #4f545c;
            color: white;
        }

        .join-button.decline:hover {
            background-color: #5d6269;
            transform: translateY(-1px);
        }

        .error-container {
            padding: 32px;
            text-align: center;
        }

        .error-icon {
            font-size: 48px;
            color: #ed4245;
            margin-bottom: 16px;
        }

        .error-message {
            color: #ed4245;
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 24px;
        }

        .loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #5865f2;
            border-top: 4px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="join-container">
        <?php if ($invite_valid): ?>
            <div class="server-info">
                <?php if ($invite['server_icon']): ?>
                    <div class="server-icon">
                        <img src="../uploads/server_icons/<?php echo htmlspecialchars($invite['server_icon']); ?>" 
                             alt="<?php echo htmlspecialchars($invite['server_name']); ?>">
                    </div>
                <?php else: ?>
                    <div class="server-icon">
                        <?php echo strtoupper(substr($invite['server_name'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
                <div class="server-name"><?php echo htmlspecialchars($invite['server_name']); ?></div>
                <div class="member-count">
                    <i class="fas fa-user"></i>
                    <?php echo number_format($invite['member_count']); ?> üye
                </div>
            </div>
            <div class="join-description">
                <strong><?php echo htmlspecialchars($invite['server_name']); ?></strong> sunucusuna katılmak üzeresin
            </div>
            <div class="join-actions">
                <button class="join-button decline" onclick="window.location.href='/'">
                    <i class="fas fa-times"></i>
                    Reddet
                </button>
                <button class="join-button accept" onclick="joinServer('<?php echo $invite_code; ?>')">
                    <i class="fas fa-check"></i>
                    Katıl
                </button>
            </div>
        <?php else: ?>
            <div class="error-container">
                <div class="error-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="error-message">
                    <?php echo $error_message ? $error_message : 'Geçersiz davet linki.'; ?>
                </div>
                <button class="join-button decline" onclick="window.location.href='/'">
                    <i class="fas fa-home"></i>
                    Ana Sayfaya Dön
                </button>
            </div>
        <?php endif; ?>
    </div>

    <div class="loading">
        <div class="loading-spinner"></div>
    </div>

    <script>
    function joinServer(inviteCode) {
        // Loading ekranını göster
        document.querySelector('.loading').style.display = 'flex';
        
        fetch('includes/join_server.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                invite_code: inviteCode
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = '/chat.php?server=' + data.server_id;
            } else {
                document.querySelector('.loading').style.display = 'none';
                alert(data.error || 'Sunucuya katılırken bir hata oluştu.');
            }
        })
        .catch(error => {
            document.querySelector('.loading').style.display = 'none';
            console.error('Error:', error);
            alert('Bir hata oluştu.');
        });
    }
    </script>
</body>
</html> 
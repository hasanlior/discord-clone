<?php
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Kontroller
    if ($password !== $confirm_password) {
        $error = "Şifreler eşleşmiyor!";
    } elseif (strlen($password) < 6) {
        $error = "Şifre en az 6 karakter olmalıdır!";
    } else {
        // Email kontrolü
        $sql = "SELECT id FROM users WHERE email = ? OR username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $email, $username);
        $stmt->execute();
        $check = $stmt->get_result();
        if ($check->num_rows > 0) {
            $error = "Bu email veya kullanıcı adı zaten kullanımda!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $username, $email, $hashed_password);
            
            if ($stmt->execute()) {
                $_SESSION['user_id'] = $stmt->insert_id;
                $_SESSION['username'] = $username;
                header("Location: chat.php");
                exit();
            } else {
                $error = "Kayıt sırasında bir hata oluştu!";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol - Duygula</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <h1 class="auth-brand">Duygula</h1>
                <h2>Maceraya Katıl</h2>
                <p class="auth-subtitle">Yeni nesil iletişim platformunda yerini al!</p>
            </div>
            <?php if (isset($error)) echo "<div class='error-message'><i class='fas fa-exclamation-circle'></i> $error</div>"; ?>
            <form method="POST" class="auth-form" id="registerForm">
                <div class="form-group">
                    <label for="username">KULLANICI ADI</label>
                    <input type="text" id="username" name="username" required 
                           placeholder="CoolUser123" autocomplete="username">
                </div>
                <div class="form-group">
                    <label for="email">E-POSTA</label>
                    <input type="email" id="email" name="email" required 
                           placeholder="ornek@email.com" autocomplete="email">
                </div>
                <div class="form-group">
                    <label for="password">ŞİFRE</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="••••••••" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="confirm_password">ŞİFREYİ TEKRARLA</label>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           required placeholder="••••••••" autocomplete="new-password">
                </div>
                <button type="submit" class="auth-button">
                    <i class="fas fa-user-plus"></i> Kayıt Ol
                </button>
                <div class="auth-link">
                    Zaten hesabın var mı? <a href="login.php">Giriş Yap</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 
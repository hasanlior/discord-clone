<?php
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT id, username, password FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: chat.php");
            exit();
        }
    }
    $error = "Geçersiz email veya şifre";
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - Duygula</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <h1 class="auth-brand">Duygula</h1>
                <h2>Tekrar Hoş Geldin!</h2>
            </div>
            <?php if (isset($error)) echo "<div class='error-message'>$error</div>"; ?>
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="email">E-POSTA</label>
                    <input type="email" id="email" name="email" required autocomplete="email">
                </div>
                <div class="form-group">
                    <label for="password">ŞİFRE</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="auth-button">Giriş Yap</button>
                <div class="auth-link">
                    Hesabın yok mu? <a href="register.php">Kayıt Ol</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 
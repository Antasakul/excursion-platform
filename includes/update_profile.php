<?php
require_once __DIR__ . '/../config/app.php';
require_once base_path('config/database.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(!isset($_SESSION['user_id'])) {
    header('Location: ../pages/login.php');
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $new_password = $_POST['new_password'];

    try {
        // Проверяем email на уникальность
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if($stmt->fetch()) {
            $_SESSION['error'] = "Этот email уже используется другим пользователем";
            require_once __DIR__ . '/redirect_helper.php';
            $redirectUrl = getRedirectUrl();
            header('Location: ' . $redirectUrl);
            exit();
        }

        // Обновляем данные
        if(!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, password = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $phone, $hashed_password, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $phone, $user_id]);
        }

        // Получаем обновленные данные пользователя
        $stmt = $pdo->prepare("SELECT avatar_url FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $updated_user = $stmt->fetch();
        
        $_SESSION['success'] = "Профиль успешно обновлен";
        $_SESSION['full_name'] = $full_name;
        if($updated_user && $updated_user['avatar_url']) {
            $_SESSION['avatar_url'] = $updated_user['avatar_url'];
        }
        
    } catch(PDOException $e) {
        $_SESSION['error'] = "Ошибка обновления профиля: " . $e->getMessage();
    }
}

// Перенаправляем на предыдущую страницу
require_once __DIR__ . '/redirect_helper.php';
$redirectUrl = getRedirectUrl();
header('Location: ' . $redirectUrl);
exit();
?>
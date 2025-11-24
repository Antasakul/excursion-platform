<?php
require_once __DIR__ . '/../config/app.php';
require_once base_path('config/database.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Обработка запроса на восстановление пароля
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forgot_password'])) {
    $email = trim($_POST['email'] ?? '');
    
    if(empty($email)) {
        $_SESSION['error'] = 'Введите email';
        header('Location: ' . route_path('pages/forgot_password.php'));
        exit();
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id, email, full_name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if(!$user) {
            // Для безопасности не сообщаем, что пользователь не найден
            $_SESSION['success'] = 'Если аккаунт с таким email существует, инструкции отправлены на почту.';
            header('Location: ' . route_path('pages/forgot_password.php'));
            exit();
        }
        
        // Генерируем токен
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 час
        
        // Удаляем старые токены для этого пользователя
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        
        // Сохраняем новый токен
        $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$user['id'], $token, $expiresAt]);
        
        // В реальном приложении здесь должна быть отправка email
        // Для демо-версии просто показываем ссылку
        $resetLink = route_path('pages/reset_password.php') . '?token=' . $token;
        
        // В продакшене используйте PHPMailer или аналогичную библиотеку:
        /*
        $subject = "Восстановление пароля";
        $message = "Здравствуйте, {$user['full_name']}!\n\n";
        $message .= "Для восстановления пароля перейдите по ссылке:\n";
        $message .= $resetLink . "\n\n";
        $message .= "Ссылка действительна 1 час.\n\n";
        $message .= "Если вы не запрашивали восстановление пароля, проигнорируйте это письмо.";
        mail($email, $subject, $message);
        */
        
        // Для демо: сохраняем ссылку в сессии (в продакшене удалите это)
        $_SESSION['reset_link'] = $resetLink;
        $_SESSION['success'] = 'Инструкции по восстановлению пароля отправлены на ваш email. ' .
                              'Для демо-версии ссылка: <a href="' . $resetLink . '">' . $resetLink . '</a>';
        
    } catch(PDOException $e) {
        $_SESSION['error'] = 'Ошибка при обработке запроса: ' . $e->getMessage();
    }
    
    header('Location: ' . route_path('pages/forgot_password.php'));
    exit();
}

// Обработка установки нового пароля
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    if(empty($token) || empty($password) || empty($password_confirm)) {
        $_SESSION['error'] = 'Заполните все поля';
        header('Location: ' . route_path('pages/reset_password.php') . '?token=' . $token);
        exit();
    }
    
    if($password !== $password_confirm) {
        $_SESSION['error'] = 'Пароли не совпадают';
        header('Location: ' . route_path('pages/reset_password.php') . '?token=' . $token);
        exit();
    }
    
    if(strlen($password) < 8) {
        $_SESSION['error'] = 'Пароль должен быть не менее 8 символов';
        header('Location: ' . route_path('pages/reset_password.php') . '?token=' . $token);
        exit();
    }
    
    try {
        // Проверяем токен
        $stmt = $pdo->prepare("
            SELECT pr.user_id, u.email 
            FROM password_resets pr 
            JOIN users u ON pr.user_id = u.id 
            WHERE pr.token = ? AND pr.expires_at > NOW()
        ");
        $stmt->execute([$token]);
        $reset = $stmt->fetch();
        
        if(!$reset) {
            $_SESSION['error'] = 'Недействительная или истекшая ссылка';
            header('Location: ' . route_path('pages/forgot_password.php'));
            exit();
        }
        
        // Обновляем пароль
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $reset['user_id']]);
        
        // Удаляем использованный токен
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
        $stmt->execute([$token]);
        
        $_SESSION['success'] = 'Пароль успешно изменен. Теперь вы можете войти.';
        header('Location: ' . route_path('pages/login.php'));
        exit();
        
    } catch(PDOException $e) {
        $_SESSION['error'] = 'Ошибка при сбросе пароля: ' . $e->getMessage();
        header('Location: ' . route_path('pages/reset_password.php') . '?token=' . $token);
        exit();
    }
}


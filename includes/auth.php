<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/app.php';
require_once base_path('config/database.php');

if(isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    header('Location: ' . route_path('index.php'));
    exit();
}

// РЕГИСТРАЦИЯ
if(isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $full_name = trim($_POST['full_name']);
    $user_type = $_POST['user_type'];
    $phone = trim($_POST['phone']);

    try {
        // Проверяем, не занят ли username или email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if($stmt->fetch()) {
            $_SESSION['error'] = "Пользователь с таким именем или email уже существует";
            header('Location: ' . route_path('pages/register.php'));
            exit();
        }

        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, user_type, phone) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $email, $password, $full_name, $user_type, $phone]);
        
        $_SESSION['success'] = "Регистрация успешна! Теперь войдите в систему.";
        header('Location: ' . route_path('pages/login.php'));
        exit();
        
    } catch(PDOException $e) {
        $_SESSION['error'] = "Ошибка регистрации: " . $e->getMessage();
        header('Location: ' . route_path('pages/register.php'));
        exit();
    }
}

// ВХОД В СИСТЕМУ
if(isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if($user && password_verify($password, $user['password'])) {
            // Успешный вход
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            
            // Запоминаем пользователя если выбрано "Запомнить меня"
            if(isset($_POST['remember_me'])) {
                setcookie('user_id', $user['id'], time() + 86400 * 30, '/'); // 30 дней
            }
            
            $_SESSION['success'] = "Добро пожаловать, " . $user['full_name'] . "!";
            if($user['user_type'] === 'admin') {
                header('Location: ' . route_path('pages/admin/dashboard.php'));
            } else {
                header('Location: ' . route_path('pages/dashboard.php'));
            }
            exit();
            
        } else {
            $_SESSION['error'] = "Неверное имя пользователя или пароль!";
            header('Location: ' . route_path('pages/login.php'));
            exit();
        }
        
    } catch(PDOException $e) {
        $_SESSION['error'] = "Ошибка входа: " . $e->getMessage();
        header('Location: ' . route_path('pages/login.php'));
        exit();
    }
}

// ВОССТАНОВЛЕНИЕ ПАРОЛЯ - перенаправляем в отдельный обработчик
if(isset($_POST['forgot_password'])) {
    require_once __DIR__ . '/reset_password.php';
    exit();
}
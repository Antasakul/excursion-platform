<?php
require_once __DIR__ . '/../config/app.php';
require_once base_path('config/database.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$token = $_GET['token'] ?? '';

if(empty($token)) {
    $_SESSION['error'] = 'Неверная ссылка для восстановления пароля';
    header('Location: ' . route_path('pages/forgot_password.php'));
    exit();
}

// Проверяем токен
try {
    $stmt = $pdo->prepare("
        SELECT pr.user_id, u.email, u.full_name 
        FROM password_resets pr 
        JOIN users u ON pr.user_id = u.id 
        WHERE pr.token = ? AND pr.expires_at > NOW()
    ");
    $stmt->execute([$token]);
    $reset = $stmt->fetch();
    
    if(!$reset) {
        $_SESSION['error'] = 'Недействительная или истекшая ссылка для восстановления пароля';
        header('Location: ' . route_path('pages/forgot_password.php'));
        exit();
    }
} catch(PDOException $e) {
    $_SESSION['error'] = 'Ошибка при проверке токена';
    header('Location: ' . route_path('pages/forgot_password.php'));
    exit();
}

require_once base_path('includes/header.php');
?>

<div class="container">
    <div class="auth-form">
        <h2>Установка нового пароля</h2>
        
        <p>Здравствуйте, <strong><?php echo htmlspecialchars($reset['full_name']); ?></strong>!</p>
        <p>Введите новый пароль для вашего аккаунта (<?php echo htmlspecialchars($reset['email']); ?>).</p>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-error" style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success" style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo route_path('includes/reset_password.php'); ?>">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            
            <div class="form-group">
                <label>Новый пароль:</label>
                <input type="password" name="password" required minlength="8" autocomplete="new-password">
                <small style="color: #666;">Минимум 8 символов</small>
            </div>
            
            <div class="form-group">
                <label>Подтвердите пароль:</label>
                <input type="password" name="password_confirm" required minlength="8" autocomplete="new-password">
            </div>

            <button type="submit" name="reset_password" class="btn btn-primary btn-block">
                Установить новый пароль
            </button>
        </form>

        <div class="auth-links" style="text-align: center; margin-top: 1.5rem;">
            <p><a href="<?php echo route_path('pages/login.php'); ?>">Вернуться к входу</a></p>
        </div>
    </div>
</div>

<?php require_once base_path('includes/footer.php'); ?>


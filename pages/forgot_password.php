<?php
require_once __DIR__ . '/../config/app.php';
require_once base_path('includes/header.php');
?>

<div class="container">
    <div class="auth-form">
        <h2>Восстановление пароля</h2>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo route_path('includes/reset_password.php'); ?>">
            <div class="form-group">
                <label>Введите ваш Email:</label>
                <input type="email" name="email" required>
            </div>

            <button type="submit" name="forgot_password" class="btn btn-primary btn-block">
                Отправить инструкции
            </button>
        </form>

        <div class="auth-links">
            <p><a href="<?php echo route_path('pages/login.php'); ?>">Вернуться к входу</a></p>
            <p>Нет аккаунта? <a href="<?php echo route_path('pages/register.php'); ?>">Зарегистрируйтесь</a></p>
        </div>
    </div>
</div>

<?php require_once base_path('includes/footer.php'); ?>
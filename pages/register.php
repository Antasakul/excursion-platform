<?php
require_once __DIR__ . '/../config/app.php';
require_once base_path('includes/header.php');
?>

<div class="container">
    <div class="auth-form">
        <h2>Регистрация</h2>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo route_path('includes/auth.php'); ?>">
            <div class="form-group">
                <label>Имя пользователя:</label>
                <input type="text" name="username" required autocomplete="username">
            </div>
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" required autocomplete="email">
            </div>
            <div class="form-group">
                <label>Полное имя:</label>
                <input type="text" name="full_name" required autocomplete="name">
            </div>
            <div class="form-group">
                <label>Пароль:</label>
                <input type="password" name="password" required autocomplete="new-password">
            </div>
            <div class="form-group">
                <label>Телефон:</label>
                <input type="tel" name="phone" autocomplete="tel">
            </div>
            <div class="form-group">
                <label>Я хочу:</label>
                <select name="user_type" required>
                    <option value="customer">Заказывать экскурсии</option>
                    <option value="guide">Быть гидом</option>
                </select>
            </div>
            <button type="submit" name="register" class="btn btn-primary">Зарегистрироваться</button>
        </form>
        <p style="  text-align: center;">Уже есть аккаунт? <a href="<?php echo route_path('pages/login.php'); ?>">Войдите</a></p>
    </div>
</div>

<?php require_once base_path('includes/footer.php'); ?>
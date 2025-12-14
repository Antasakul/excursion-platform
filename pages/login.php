<?php
require_once __DIR__ . '/../config/app.php';
require_once base_path('includes/header.php');
?>

<div class="container">
    <div class="auth-form">
        <h2>Вход в систему</h2>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php 
                echo $_SESSION['error']; 
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['success']; 
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo route_path('includes/auth.php'); ?>">
            <div class="form-group">
                <label>Имя пользователя:</label>
                <input type="text" name="username" required autocomplete="username"
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label>Пароль:</label>
                <input type="password" name="password" required autocomplete="current-password">
            </div>
            <!--
            <div class="form-group">
                <label>
                    <input type="checkbox" name="remember_me"> Запомнить меня
                </label>
            </div>-->

            <button type="submit" name="login" class="btn btn-primary btn-block">Войти</button>
        </form>

        <div class="auth-links">
            <p>Нет аккаунта? <a href="<?php echo route_path('pages/register.php'); ?>">Зарегистрируйтесь</a></p>
           <!-- <p><a href="<?php echo route_path('pages/forgot_password.php'); ?>">Забыли пароль?</a></p>-->
        </div>
        
        <!-- Демо-аккаунты для тестирования 
        <div class="demo-accounts" style="margin-top: 2rem; padding: 1rem; background: #f8f9fa; border-radius: 5px;">
            <h4>Демо-аккаунты для тестирования:</h4>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
                <div>
                    <strong>👨‍💼 Гид:</strong><br>
                    Логин: <code>guide1</code><br>
                    Пароль: <code>password123</code>
                </div>
                <div>
                    <strong>👤 Клиент:</strong><br>
                    Логин: <code>customer1</code><br>
                    Пароль: <code>password123</code>
                </div>
            </div>
        </div>-->
    </div>
</div>

<style>
.btn-block {
    width: 100%;
    padding: 12px;
}

.auth-links {
    text-align: center;
    margin-top: 1.5rem;
}

.auth-links a {
    color: #3498db;
    text-decoration: none;
}

.auth-links a:hover {
    text-decoration: underline;
}

.demo-accounts {
    font-size: 0.9rem;
}

.demo-accounts code {
    background: #e9ecef;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
}
</style>

<?php require_once base_path('includes/footer.php'); ?>
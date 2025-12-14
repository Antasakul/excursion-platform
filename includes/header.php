<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/app.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Платформа частных экскурсий</title>
    <link rel="stylesheet" href="<?php echo asset_path('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="<?php echo route_path('index.php'); ?>">ExcursionPro</a>
            </div>
            <div class="nav-menu">
                <a href="<?php echo route_path('index.php'); ?>">Главная</a>
                <a href="<?php echo route_path('pages/excursions.php'); ?>">Экскурсии</a>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if($_SESSION['user_type'] === 'admin'): ?>
                        <a href="<?php echo route_path('pages/admin/dashboard.php'); ?>">Админ</a>
                    <?php else: ?>
                        <a href="<?php echo route_path('pages/dashboard.php'); ?>">Личный кабинет</a>
                    <?php endif; ?>
                    <a href="<?php echo route_path('includes/auth.php'); ?>?action=logout">Выйти</a>
                <?php else: ?>
                    <a href="<?php echo route_path('pages/login.php'); ?>">Войти</a>
                    <a href="<?php echo route_path('pages/register.php'); ?>">Регистрация</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <main>
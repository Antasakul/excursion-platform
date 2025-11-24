<?php
require_once __DIR__ . '/../config/app.php';
require_once base_path('config/database.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(!isset($_SESSION['user_id'])) {
    header('Location: ' . route_path('pages/login.php'));
    exit();
}

if(isset($_POST['action']) && isset($_POST['excursion_id'])) {
    $user_id = $_SESSION['user_id'];
    $excursion_id = (int)$_POST['excursion_id'];
    $action = $_POST['action'];

    try {
        if($action == 'add') {
            $stmt = $pdo->prepare("INSERT IGNORE INTO favorites (user_id, excursion_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $excursion_id]);
            $_SESSION['success'] = "Экскурсия добавлена в избранное";
        } elseif($action == 'remove') {
            $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND excursion_id = ?");
            $stmt->execute([$user_id, $excursion_id]);
            $_SESSION['success'] = "Экскурсия удалена из избранного";
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Ошибка: " . $e->getMessage();
    }
}

// Возвращаем на предыдущую страницу
$referer = $_SERVER['HTTP_REFERER'] ?? route_path('pages/excursions.php');
header('Location: ' . $referer);
exit();
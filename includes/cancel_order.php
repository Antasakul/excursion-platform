<?php
require_once __DIR__ . '/../config/app.php';
require_once base_path('config/database.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(!isset($_SESSION['user_id']) || !isset($_GET['order_id'])) {
    header('Location: ' . route_path('pages/dashboard.php'));
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = (int)$_GET['order_id'];

try {
    $pdo->beginTransaction();

    // Получаем информацию о заказе
    $stmt = $pdo->prepare("
        SELECT o.*, ed.available_date, ed.available_time, ed.excursion_id
        FROM orders o
        JOIN excursion_dates ed ON o.excursion_date_id = ed.id
        WHERE o.id = ? AND o.customer_id = ?
    ");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch();

    if(!$order) {
        throw new Exception("Заказ не найден");
    }

    if($order['status'] === 'cancelled') {
        throw new Exception("Заказ уже отменен");
    }

    if($order['status'] === 'completed') {
        throw new Exception("Невозможно отменить завершенный заказ");
    }

    // Проверка времени отмены (минимум за 48 часов)
    $excursion_datetime = strtotime($order['available_date'] . ' ' . $order['available_time']);
    $now = time();
    $hours_until = ($excursion_datetime - $now) / 3600;

    if($hours_until < 48) {
        throw new Exception("Отмена возможна не менее чем за 48 часов до начала экскурсии");
    }

    // Отменяем заказ (отмена пользователем)
    $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled', cancelled_by = 'customer' WHERE id = ?");
    $stmt->execute([$order_id]);

    // Возвращаем места
    $stmt = $pdo->prepare("UPDATE excursion_dates SET available_slots = available_slots + ? WHERE id = ?");
    $stmt->execute([$order['participants_count'], $order['excursion_date_id']]);

    $pdo->commit();
    $_SESSION['success'] = "Заказ успешно отменен. Места возвращены.";

} catch(Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Ошибка отмены: " . $e->getMessage();
}

header('Location: ' . route_path('pages/customer/dashboard.php'));
exit();


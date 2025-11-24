<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(!isset($_SESSION['user_id'])) {
    header('Location: ' . route_path('pages/login.php'));
    exit();
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_SESSION['user_id'];
    $excursion_date_id = $_POST['excursion_date_id'] ?? null;
    $participants_count = (int)($_POST['participants_count'] ?? 0);
    $promo_code = trim($_POST['promo_code'] ?? '');

    if(!$excursion_date_id || $participants_count < 1) {
        $_SESSION['error'] = "Некорректные данные бронирования.";
        header('Location: ' . route_path('pages/booking.php') . '?excursion_id=' . ($_POST['excursion_id'] ?? ''));
        exit();
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            SELECT ed.*, e.price, e.guide_id, e.title, ed.available_slots, ed.excursion_id
            FROM excursion_dates ed 
            JOIN excursions e ON ed.excursion_id = e.id 
            WHERE ed.id = ? AND ed.is_available = TRUE
            FOR UPDATE
        ");
        $stmt->execute([$excursion_date_id]);
        $date_info = $stmt->fetch();

        if(!$date_info) {
            throw new Exception("Выбранная дата недоступна");
        }

        if($date_info['available_slots'] < $participants_count) {
            throw new Exception("Недостаточно доступных мест");
        }

        // Проверка минимального времени бронирования (24 часа)
        $excursion_datetime = strtotime($date_info['available_date'] . ' ' . $date_info['available_time']);
        $hours_until = ($excursion_datetime - time()) / 3600;
        
        if($hours_until < 24) {
            throw new Exception("Бронирование возможно не менее чем за 24 часа до начала экскурсии");
        }

        $base_price = $date_info['price'] * $participants_count;
        $special_requests = trim($_POST['special_requests'] ?? '');
        $discount = 0;

        if(!empty($promo_code)) {
            $stmt = $pdo->prepare("
                SELECT * FROM promo_codes 
                WHERE code = ? 
                  AND is_active = TRUE 
                  AND valid_until >= CURDATE() 
                  AND used_count < max_uses
                FOR UPDATE
            ");
            $stmt->execute([$promo_code]);
            $promo = $stmt->fetch();

            if($promo) {
                $discount = $base_price * ($promo['discount_percent'] / 100);

                $stmt = $pdo->prepare("UPDATE promo_codes SET used_count = used_count + 1 WHERE id = ?");
                $stmt->execute([$promo['id']]);
            } else {
                $_SESSION['error'] = "Промо-код недействителен.";
            }
        }

        $total_price = $base_price - $discount;

        // Проверяем наличие поля special_requests в таблице
        try {
            $checkStmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'special_requests'");
            $hasSpecialRequests = $checkStmt->rowCount() > 0;
        } catch(PDOException $e) {
            $hasSpecialRequests = false;
        }

        if($hasSpecialRequests) {
            $stmt = $pdo->prepare("
                INSERT INTO orders (customer_id, excursion_date_id, participants_count, total_price, status, special_requests) 
                VALUES (?, ?, ?, ?, 'pending', ?)
            ");
            $stmt->execute([$customer_id, $excursion_date_id, $participants_count, $total_price, $special_requests]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO orders (customer_id, excursion_date_id, participants_count, total_price, status) 
                VALUES (?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([$customer_id, $excursion_date_id, $participants_count, $total_price]);
        }

        $stmt = $pdo->prepare("UPDATE excursion_dates SET available_slots = available_slots - ? WHERE id = ?");
        $stmt->execute([$participants_count, $excursion_date_id]);

        $pdo->commit();

        $_SESSION['success'] = "Бронирование успешно создано! Ожидайте подтверждения от гида.";
    } catch(Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Ошибка бронирования: " . $e->getMessage();
    }
}

$redirectExcursionId = $_POST['excursion_id'] ?? '';
$userType = $_SESSION['user_type'] ?? 'customer';
if(!empty($redirectExcursionId)) {
    $redirectUrl = route_path('pages/booking.php') . '?excursion_id=' . urlencode($redirectExcursionId);
} else {
    if($userType === 'guide') {
        $redirectUrl = route_path('pages/guide/dashboard.php');
    } else {
        $redirectUrl = route_path('pages/customer/dashboard.php');
    }
}

header('Location: ' . $redirectUrl);
exit();


<?php
require_once __DIR__ . '/../config/app.php';
require_once base_path('config/database.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(!isset($_SESSION['user_id'])) {
    header('Location: ../pages/login.php');
    exit();
}

if(isset($_GET['action']) && isset($_GET['id'])) {
    $order_id = $_GET['id'];
    $action = $_GET['action'];
    $user_id = $_SESSION['user_id'];
    $user_type = $_SESSION['user_type'];

    try {
        // Проверяем права доступа
        if($user_type == 'guide') {
            $stmt = $pdo->prepare("
                SELECT o.* FROM orders o 
                JOIN excursion_dates ed ON o.excursion_date_id = ed.id 
                JOIN excursions e ON ed.excursion_id = e.id 
                WHERE o.id = ? AND e.guide_id = ?
            ");
            $stmt->execute([$order_id, $user_id]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND customer_id = ?");
            $stmt->execute([$order_id, $user_id]);
        }

        $order = $stmt->fetch();

        if(!$order) {
            throw new Exception("Заказ не найден или у вас нет прав для управления им");
        }

        switch($action) {
            case 'confirm':
                if($user_type != 'guide') throw new Exception("Недостаточно прав");
                $stmt = $pdo->prepare("UPDATE orders SET status = 'confirmed' WHERE id = ?");
                $stmt->execute([$order_id]);
                $_SESSION['success'] = "Бронирование подтверждено";
                break;

            case 'cancel':
                if($user_type != 'guide') throw new Exception("Недостаточно прав");
                // Отменяем заказ (отмена гидом)
                $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled', cancelled_by = 'guide' WHERE id = ?");
                // Возвращаем места
                $update_slots = $pdo->prepare("
                    UPDATE excursion_dates ed 
                    SET available_slots = available_slots + ? 
                    WHERE id = ?
                ");
                $update_slots->execute([$order['participants_count'], $order['excursion_date_id']]);
                $stmt->execute([$order_id]);
                $_SESSION['success'] = "Бронирование отменено";
                break;

            case 'complete':
                if($user_type != 'guide') throw new Exception("Недостаточно прав");
                $stmt = $pdo->prepare("UPDATE orders SET status = 'completed' WHERE id = ?");
                $stmt->execute([$order_id]);
                $_SESSION['success'] = "Экскурсия отмечена как завершенная";
                break;

            default:
                throw new Exception("Неизвестное действие");
        }

    } catch(Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Перенаправляем на предыдущую страницу
require_once __DIR__ . '/redirect_helper.php';
$redirectUrl = getRedirectUrl();
header('Location: ' . $redirectUrl);
exit();
?>
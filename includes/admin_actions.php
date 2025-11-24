<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/app.php';
require_once base_path('config/database.php');

if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ' . route_path('pages/login.php'));
    exit();
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'update_user_role':
            $userId = (int)($_POST['user_id'] ?? 0);
            $userType = $_POST['user_type'] ?? '';
            $allowedRoles = ['guide','customer','admin'];

            if($userId === $_SESSION['user_id']) {
                throw new RuntimeException('Нельзя изменить собственную роль');
            }

            if(!in_array($userType, $allowedRoles, true)) {
                throw new RuntimeException('Недопустимая роль');
            }

            $stmt = $pdo->prepare("UPDATE users SET user_type = ? WHERE id = ?");
            $stmt->execute([$userType, $userId]);
            $_SESSION['success'] = 'Роль пользователя обновлена';
            break;

        case 'toggle_user_avatar':
            $userId = (int)($_POST['user_id'] ?? 0);
            $stmt = $pdo->prepare("UPDATE users SET avatar_url = NULL WHERE id = ?");
            $stmt->execute([$userId]);
            $_SESSION['success'] = 'Аватар удалён';
            break;

        case 'toggle_excursion':
            $excursionId = (int)($_POST['excursion_id'] ?? 0);
            $stmt = $pdo->prepare("SELECT is_active FROM excursions WHERE id = ?");
            $stmt->execute([$excursionId]);
            $excursion = $stmt->fetch();

            if(!$excursion) {
                throw new RuntimeException('Экскурсия не найдена');
            }

            $newStatus = $excursion['is_active'] ? 0 : 1;
            $stmt = $pdo->prepare("UPDATE excursions SET is_active = ? WHERE id = ?");
            $stmt->execute([$newStatus, $excursionId]);
            $_SESSION['success'] = $newStatus ? 'Экскурсия активирована' : 'Экскурсия отключена';
            break;

        case 'update_order_status':
            $orderId = (int)($_POST['order_id'] ?? 0);
            $status = $_POST['status'] ?? 'pending';
            $allowedStatuses = ['pending','confirmed','cancelled','completed'];
            if(!in_array($status, $allowedStatuses, true)) {
                throw new RuntimeException('Статус недопустим');
            }
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$status, $orderId]);
            $_SESSION['success'] = 'Статус заказа обновлён';
            break;

        case 'delete_review':
            $reviewId = (int)($_POST['review_id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
            $stmt->execute([$reviewId]);
            $_SESSION['success'] = 'Отзыв удалён';
            break;

        default:
            $_SESSION['error'] = 'Неизвестное действие';
    }
} catch(Throwable $e) {
    $_SESSION['error'] = 'Ошибка: ' . $e->getMessage();
}

header('Location: ' . route_path('pages/admin/dashboard.php'));
exit();


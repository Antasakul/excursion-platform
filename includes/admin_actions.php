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

        case 'delete_user':
            $userId = (int)($_POST['user_id'] ?? 0);
            
            if($userId === $_SESSION['user_id']) {
                throw new RuntimeException('Нельзя удалить самого себя');
            }
            
            // Проверяем, есть ли у пользователя связанные данные
            $stmt = $pdo->prepare("SELECT user_type FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if(!$user) {
                throw new RuntimeException('Пользователь не найден');
            }
            
            // Если это гид, проверяем наличие экскурсий
            if($user['user_type'] === 'guide') {
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM excursions WHERE guide_id = ?");
                $stmt->execute([$userId]);
                $excursionCount = $stmt->fetch()['count'];
                
                if($excursionCount > 0) {
                    throw new RuntimeException('Нельзя удалить гида с активными экскурсиями. Сначала удалите или передайте экскурсии другому гиду.');
                }
            }
            
            // Если это клиент, проверяем наличие заказов
            if($user['user_type'] === 'customer') {
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE customer_id = ?");
                $stmt->execute([$userId]);
                $orderCount = $stmt->fetch()['count'];
                
                if($orderCount > 0) {
                    // Можно удалить, но лучше предупредить
                    // Удаляем связанные отзывы
                    $stmt = $pdo->prepare("DELETE r FROM reviews r JOIN orders o ON r.order_id = o.id WHERE o.customer_id = ?");
                    $stmt->execute([$userId]);
                }
            }
            
            // Удаляем пользователя
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $_SESSION['success'] = 'Пользователь удалён';
            break;

        case 'update_user':
            $userId = (int)($_POST['user_id'] ?? 0);
            $fullName = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $newPassword = $_POST['new_password'] ?? '';
            
            if(empty($fullName) || empty($email)) {
                throw new RuntimeException('Имя и email обязательны');
            }
            
            if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Некорректный email');
            }
            
            // Проверяем уникальность email
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $userId]);
            if($stmt->fetch()) {
                throw new RuntimeException('Email уже используется');
            }
            
            if(!empty($newPassword)) {
                if(strlen($newPassword) < 6) {
                    throw new RuntimeException('Пароль должен быть не менее 6 символов');
                }
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, password = ? WHERE id = ?");
                $stmt->execute([$fullName, $email, $phone, $hashedPassword, $userId]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
                $stmt->execute([$fullName, $email, $phone, $userId]);
            }
            
            $_SESSION['success'] = 'Данные пользователя обновлены';
            break;

        default:
            $_SESSION['error'] = 'Неизвестное действие';
    }
} catch(Throwable $e) {
    $_SESSION['error'] = 'Ошибка: ' . $e->getMessage();
}

// Используем redirect_helper для остаться на текущей странице
require_once __DIR__ . '/redirect_helper.php';
$redirectUrl = getRedirectUrl();
header('Location: ' . $redirectUrl);
exit();


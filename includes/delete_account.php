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

// Разрешаем удаление только для клиентов и гидов
if($_SESSION['user_type'] === 'admin') {
    $_SESSION['error'] = 'Удаление аккаунта администратора недоступно';
    header('Location: ' . route_path('pages/admin/dashboard.php'));
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Проверяем подтверждение удаления
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    try {
        $pdo->beginTransaction();
        
        // Удаляем все связанные данные пользователя
        // Избранное удалится автоматически благодаря CASCADE
        // Заказы - нужно проверить, можно ли их удалять или нужно оставить для истории
        
        // Отменяем все активные заказы пользователя
        if($user_type === 'customer') {
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET status = 'cancelled', 
                    cancelled_by = 'customer' 
                WHERE customer_id = ? 
                AND status IN ('pending', 'confirmed')
            ");
            $stmt->execute([$user_id]);
        }
        
        // Если это гид, отменяем все его активные экскурсии
        if($user_type === 'guide') {
            // Отменяем все активные заказы на экскурсии гида
            $stmt = $pdo->prepare("
                UPDATE orders o
                JOIN excursion_dates ed ON o.excursion_date_id = ed.id
                JOIN excursions e ON ed.excursion_id = e.id
                SET o.status = 'cancelled', 
                    o.cancelled_by = 'guide'
                WHERE e.guide_id = ? 
                AND o.status IN ('pending', 'confirmed')
            ");
            $stmt->execute([$user_id]);
            
            // Деактивируем все экскурсии гида
            $stmt = $pdo->prepare("UPDATE excursions SET is_active = FALSE WHERE guide_id = ?");
            $stmt->execute([$user_id]);
        }
        
        // Удаляем аватар, если он есть
        $stmt = $pdo->prepare("SELECT avatar_url FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if($user && $user['avatar_url']) {
            $avatar_path = base_path($user['avatar_url']);
            if(file_exists($avatar_path)) {
                @unlink($avatar_path);
            }
        }
        
        // Удаляем пользователя (все связанные данные удалятся благодаря CASCADE)
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        
        $pdo->commit();
        
        // Очищаем сессию
        session_destroy();
        
        $_SESSION['success'] = 'Ваш аккаунт успешно удален';
        header('Location: ' . route_path('index.php'));
        exit();
        
    } catch(PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Ошибка при удалении аккаунта: ' . $e->getMessage();
        
        if($user_type === 'customer') {
            header('Location: ' . route_path('pages/customer/dashboard.php'));
        } else {
            header('Location: ' . route_path('pages/guide/dashboard.php'));
        }
        exit();
    }
} else {
    // Если это GET запрос, просто перенаправляем обратно
    if($user_type === 'customer') {
        header('Location: ' . route_path('pages/customer/dashboard.php'));
    } else {
        header('Location: ' . route_path('pages/guide/dashboard.php'));
    }
    exit();
}
?>


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

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $customer_id = $_SESSION['user_id'];
    $order_id = (int)$_POST['order_id'];
    $rating = (int)$_POST['rating'];
    $guide_rating = isset($_POST['guide_rating']) ? (int)$_POST['guide_rating'] : $rating; // По умолчанию такая же как оценка экскурсии
    $comment = trim($_POST['comment']);
    $is_edit = isset($_POST['review_id']) && (int)$_POST['review_id'] > 0;

    // Проверка что заказ принадлежит пользователю и завершен
    $stmt = $pdo->prepare("
        SELECT o.id, o.status 
        FROM orders o 
        WHERE o.id = ? AND o.customer_id = ? AND o.status = 'completed'
    ");
    $stmt->execute([$order_id, $customer_id]);
    $order = $stmt->fetch();

    if(!$order) {
        $_SESSION['error'] = "Вы можете оставить отзыв только для завершенных заказов";
        require_once __DIR__ . '/redirect_helper.php';
        $redirectUrl = getRedirectUrl();
        header('Location: ' . $redirectUrl);
        exit();
    }

    try {
        // Проверяем наличие поля guide_rating
        try {
            $checkStmt = $pdo->query("SHOW COLUMNS FROM reviews LIKE 'guide_rating'");
            $hasGuideRating = $checkStmt->rowCount() > 0;
        } catch(PDOException $e) {
            $hasGuideRating = false;
        }
        
        if($is_edit) {
            // Редактирование существующего отзыва
            $review_id = (int)$_POST['review_id'];
            
            // Проверяем, что отзыв принадлежит пользователю
            $stmt = $pdo->prepare("SELECT id FROM reviews WHERE id = ? AND order_id = ?");
            $stmt->execute([$review_id, $order_id]);
            if(!$stmt->fetch()) {
                throw new Exception("Отзыв не найден или не принадлежит вам");
            }
            
            if($hasGuideRating) {
                $stmt = $pdo->prepare("UPDATE reviews SET rating = ?, guide_rating = ?, comment = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$rating, $guide_rating, $comment, $review_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE reviews SET rating = ?, comment = ? WHERE id = ?");
                $stmt->execute([$rating, $comment, $review_id]);
            }
            $_SESSION['success'] = "Отзыв успешно обновлен!";
        } else {
            // Создание нового отзыва
            // Проверка что отзыв еще не оставлен
            $stmt = $pdo->prepare("SELECT id FROM reviews WHERE order_id = ?");
            $stmt->execute([$order_id]);
            if($stmt->fetch()) {
                $_SESSION['error'] = "Вы уже оставили отзыв для этого заказа. Вы можете отредактировать его.";
                require_once __DIR__ . '/redirect_helper.php';
                $redirectUrl = getRedirectUrl();
                header('Location: ' . $redirectUrl);
                exit();
            }
            
            if($hasGuideRating) {
                $stmt = $pdo->prepare("INSERT INTO reviews (order_id, rating, guide_rating, comment) VALUES (?, ?, ?, ?)");
                $stmt->execute([$order_id, $rating, $guide_rating, $comment]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO reviews (order_id, rating, comment) VALUES (?, ?, ?)");
                $stmt->execute([$order_id, $rating, $comment]);
            }
            $_SESSION['success'] = "Спасибо за ваш отзыв!";
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Ошибка при сохранении отзыва: " . $e->getMessage();
    } catch(Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Используем redirect_helper для остаться на текущей странице
require_once __DIR__ . '/redirect_helper.php';
$redirectUrl = getRedirectUrl();
header('Location: ' . $redirectUrl);
exit();


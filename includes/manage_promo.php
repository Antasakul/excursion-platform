<?php
require_once __DIR__ . '/../config/app.php';
require_once base_path('config/database.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ' . route_path('pages/login.php'));
    exit();
}

$action = $_POST['action'] ?? '';

try {
    switch($action) {
        case 'create':
            $code = strtoupper(trim($_POST['code'] ?? ''));
            $discount_percent = (float)($_POST['discount_percent'] ?? 0);
            $valid_until = $_POST['valid_until'] ?? '';
            $max_uses = (int)($_POST['max_uses'] ?? 0);
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            // Валидация
            if(empty($code)) {
                throw new Exception("Код промокода обязателен");
            }

            if($discount_percent < 1 || $discount_percent > 100) {
                throw new Exception("Скидка должна быть от 1 до 100%");
            }

            if(empty($valid_until)) {
                throw new Exception("Дата окончания обязательна");
            }

            if($max_uses < 1) {
                throw new Exception("Максимум использований должен быть больше 0");
            }

            // Проверка уникальности кода
            $stmt = $pdo->prepare("SELECT id FROM promo_codes WHERE code = ?");
            $stmt->execute([$code]);
            if($stmt->fetch()) {
                throw new Exception("Промокод с таким кодом уже существует");
            }

            // Создание промокода
            $stmt = $pdo->prepare("
                INSERT INTO promo_codes (code, discount_percent, max_uses, valid_until, is_active) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$code, $discount_percent, $max_uses, $valid_until, $is_active]);

            $_SESSION['success'] = "Промокод успешно создан!";
            break;

        case 'update':
            $promo_id = (int)($_POST['promo_id'] ?? 0);
            $code = strtoupper(trim($_POST['code'] ?? ''));
            $discount_percent = (float)($_POST['discount_percent'] ?? 0);
            $valid_until = $_POST['valid_until'] ?? '';
            $max_uses = (int)($_POST['max_uses'] ?? 0);
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            if($promo_id < 1) {
                throw new Exception("Неверный ID промокода");
            }

            // Валидация
            if(empty($code)) {
                throw new Exception("Код промокода обязателен");
            }

            if($discount_percent < 1 || $discount_percent > 100) {
                throw new Exception("Скидка должна быть от 1 до 100%");
            }

            if(empty($valid_until)) {
                throw new Exception("Дата окончания обязательна");
            }

            if($max_uses < 1) {
                throw new Exception("Максимум использований должен быть больше 0");
            }

            // Проверка уникальности кода (исключая текущий промокод)
            $stmt = $pdo->prepare("SELECT id FROM promo_codes WHERE code = ? AND id != ?");
            $stmt->execute([$code, $promo_id]);
            if($stmt->fetch()) {
                throw new Exception("Промокод с таким кодом уже существует");
            }

            // Проверка, что max_uses не меньше used_count
            $stmt = $pdo->prepare("SELECT used_count FROM promo_codes WHERE id = ?");
            $stmt->execute([$promo_id]);
            $promo = $stmt->fetch();
            
            if(!$promo) {
                throw new Exception("Промокод не найден");
            }

            if($max_uses < $promo['used_count']) {
                throw new Exception("Максимум использований не может быть меньше уже использованных (" . $promo['used_count'] . ")");
            }

            // Обновление промокода
            $stmt = $pdo->prepare("
                UPDATE promo_codes 
                SET code = ?, discount_percent = ?, max_uses = ?, valid_until = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([$code, $discount_percent, $max_uses, $valid_until, $is_active, $promo_id]);

            $_SESSION['success'] = "Промокод успешно обновлен!";
            break;

        case 'delete':
            $promo_id = (int)($_POST['promo_id'] ?? 0);

            if($promo_id < 1) {
                throw new Exception("Неверный ID промокода");
            }

            // Удаление промокода
            $stmt = $pdo->prepare("DELETE FROM promo_codes WHERE id = ?");
            $stmt->execute([$promo_id]);

            $_SESSION['success'] = "Промокод успешно удален!";
            break;

        default:
            throw new Exception("Неизвестное действие");
    }

} catch(Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

// Перенаправляем на предыдущую страницу
require_once __DIR__ . '/redirect_helper.php';
$redirectUrl = getRedirectUrl();
header('Location: ' . $redirectUrl);
exit();



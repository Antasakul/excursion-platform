<?php
require_once 'config/database.php';

// Создаем тестовых пользователей
$users = [
    ['guide1', 'guide@example.com', password_hash('password123', PASSWORD_DEFAULT), 'Иван Гидов', 'guide', '+79161234567'],
    ['customer1', 'customer@example.com', password_hash('password123', PASSWORD_DEFAULT), 'Петр Туристов', 'customer', '+79167654321']
];

foreach($users as $user) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, email, password, full_name, user_type, phone) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute($user);
}

// Создаем тестовые промо-коды
$promo_codes = [
    ['WELCOME10', 10, 100],
    ['SUMMER2024', 15, 50]
];

foreach($promo_codes as $promo) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO promo_codes (code, discount_percent, max_uses, valid_until) VALUES (?, ?, ?, DATE_ADD(CURDATE(), INTERVAL 30 DAY))");
    $stmt->execute($promo);
}

echo "Тестовые данные успешно добавлены!";
?>
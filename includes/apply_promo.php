<?php
header('Content-Type: application/json');
require_once 'config/database.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $promo_code = $_POST['promo_code'];
    $total_price = $_POST['total_price'];

    try {
        $stmt = $pdo->prepare("
            SELECT * FROM promo_codes 
            WHERE code = ? AND is_active = TRUE 
            AND valid_until >= CURDATE() 
            AND used_count < max_uses
        ");
        $stmt->execute([$promo_code]);
        $promo = $stmt->fetch();

        if($promo) {
            echo json_encode([
                'success' => true,
                'discount_percent' => $promo['discount_percent'],
                'message' => 'Промо-код применен успешно'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Промо-код недействителен или истек'
            ]);
        }
    } catch(PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка проверки промо-кода'
        ]);
    }
}
?>
<?php
header('Content-Type: application/json');
require_once '../config/database.php';

if(isset($_GET['excursion_id'])) {
    $excursion_id = $_GET['excursion_id'];
    
    $stmt = $pdo->prepare("
        SELECT * FROM excursion_dates 
        WHERE excursion_id = ? AND is_available = TRUE 
        AND available_slots > 0 
        AND available_date >= CURDATE()
        ORDER BY available_date, available_time
    ");
    $stmt->execute([$excursion_id]);
    $dates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($dates);
}
?>
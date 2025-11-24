<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(!isset($_SESSION['user_id'])) {
    header('Location: ' . route_path('pages/login.php'));
    exit();
}

if($_SESSION['user_type'] !== 'guide') {
    // Перенаправляем в зависимости от роли
    if($_SESSION['user_type'] === 'admin') {
        header('Location: ' . route_path('pages/admin/dashboard.php'));
    } else {
        header('Location: ' . route_path('pages/customer/dashboard.php'));
    }
    exit();
}

require_once base_path('includes/header.php');

$user_id = $_SESSION['user_id'];
$statuses = [
    'pending' => 'Ожидание',
    'confirmed' => 'Подтверждено',
    'cancelled' => 'Отменено',
    'completed' => 'Завершено'
];

// Функция для получения статуса с учетом того, кто отменил
function getOrderStatus($order) {
    global $statuses;
    if ($order['status'] === 'cancelled' && isset($order['cancelled_by']) && $order['cancelled_by'] === 'guide') {
        return 'Отменено гидом';
    }
    return $statuses[$order['status']] ?? $order['status'];
}
?>

<div class="container">
    <h1>Личный кабинет гида</h1>
    <p>Добро пожаловать, <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong>!</p>
    
    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success" style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-error" style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
            <strong>Ошибка:</strong> <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    
    <div class="dashboard-tabs">
        <button class="tab-btn active" onclick="openTab('profile')">Профиль</button>
        <button class="tab-btn" onclick="openTab('my_excursions')">Мои экскурсии</button>
        <button class="tab-btn" onclick="openTab('guide_orders')">Бронирования</button>
    </div>

    <!-- Вкладка профиля -->
    <div id="profile" class="tab-content active">
        <h2>Настройки профиля</h2>
        <?php
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        ?>
        <form method="POST" action="<?php echo route_path('includes/update_profile.php'); ?>" class="profile-form">
            <div class="form-group">
                <label>Полное имя:</label>
                <input type="text" name="full_name" value="<?php echo $user['full_name']; ?>" required>
            </div>
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" value="<?php echo $user['email']; ?>" required>
            </div>
            <div class="form-group">
                <label>Телефон:</label>
                <input type="tel" name="phone" value="<?php echo $user['phone']; ?>">
            </div>
            <div class="form-group">
                <label>Новый пароль (оставьте пустым, если не меняете):</label>
                <input type="password" name="new_password">
            </div>
            <button type="submit" class="btn btn-primary">Обновить профиль</button>
        </form>
    </div>

    <!-- Вкладка моих экскурсий -->
    <div id="my_excursions" class="tab-content">
        <h2>Мои экскурсии</h2>
        <a href="<?php echo route_path('pages/create_excursions.php'); ?>" class="btn btn-primary">Создать новую экскурсию</a>
        
        <div class="excursions-grid">
            <?php
            $stmt = $pdo->prepare("SELECT * FROM excursions WHERE guide_id = ? ORDER BY created_at DESC");
            $stmt->execute([$user_id]);
            while($excursion = $stmt->fetch()):
            ?>
            <div class="excursion-card">
                <div class="card-content">
                    <h3><?php echo htmlspecialchars($excursion['title']); ?></h3>
                    <p class="price">💰 <?php echo $excursion['price']; ?> руб.</p>
                    <p class="status">Статус: <?php echo $excursion['is_active'] ? 'Активна' : 'Неактивна'; ?></p>
                    <div class="card-actions">
                        <a href="<?php echo route_path('pages/edit_excursion.php'); ?>?id=<?php echo $excursion['id']; ?>" class="btn btn-secondary">Редактировать</a>
                        <a href="<?php echo route_path('includes/manage_excursion.php'); ?>?action=toggle&id=<?php echo $excursion['id']; ?>" 
                           class="btn <?php echo $excursion['is_active'] ? 'btn-warning' : 'btn-success'; ?>">
                            <?php echo $excursion['is_active'] ? 'Деактивировать' : 'Активировать'; ?>
                        </a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Вкладка бронирований гида -->
    <div id="guide_orders" class="tab-content">
        <h2>Бронирования моих экскурсий</h2>
        <div class="orders-list">
            <?php
            $stmt = $pdo->prepare("
                SELECT o.*, e.title, u.full_name as customer_name, ed.available_date, ed.available_time
                FROM orders o
                JOIN excursion_dates ed ON o.excursion_date_id = ed.id
                JOIN excursions e ON ed.excursion_id = e.id
                JOIN users u ON o.customer_id = u.id
                WHERE e.guide_id = ? AND (o.cancelled_by IS NULL OR o.cancelled_by = 'guide')
                ORDER BY o.order_date DESC
            ");
            $stmt->execute([$user_id]);
            while($order = $stmt->fetch()):
            ?>
            <div class="order-item">
                <h4><?php echo htmlspecialchars($order['title']); ?></h4>
                <p>Клиент: <?php echo htmlspecialchars($order['customer_name']); ?></p>
                <p>Дата: <?php echo $order['available_date']; ?> <?php echo $order['available_time']; ?></p>
                <p>Участников: <?php echo $order['participants_count']; ?></p>
                <p>Стоимость: <?php echo $order['total_price']; ?> руб.</p>
                <p>Статус: 
                    <span class="status-<?php echo $order['status']; ?>">
                        <?php echo getOrderStatus($order); ?>
                    </span>
                </p>
                <div class="order-actions">
                    <?php if($order['status'] == 'pending'): ?>
                        <a href="<?php echo route_path('includes/manage_order.php'); ?>?action=confirm&id=<?php echo $order['id']; ?>" class="btn btn-success">Подтвердить</a>
                        <a href="<?php echo route_path('includes/manage_order.php'); ?>?action=cancel&id=<?php echo $order['id']; ?>" class="btn btn-danger">Отклонить</a>
                    <?php elseif($order['status'] == 'confirmed'): ?>
                        <a href="<?php echo route_path('includes/manage_order.php'); ?>?action=complete&id=<?php echo $order['id']; ?>" class="btn btn-primary">Завершить</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<script>
function openTab(tabName) {
    // Скрыть все вкладки
    const tabContents = document.getElementsByClassName('tab-content');
    for (let i = 0; i < tabContents.length; i++) {
        tabContents[i].classList.remove('active');
    }

    // Убрать активный класс со всех кнопок
    const tabButtons = document.getElementsByClassName('tab-btn');
    for (let i = 0; i < tabButtons.length; i++) {
        tabButtons[i].classList.remove('active');
    }

    // Показать выбранную вкладку
    document.getElementById(tabName).classList.add('active');
    event.currentTarget.classList.add('active');
}
</script>

<?php require_once base_path('includes/footer.php'); ?>



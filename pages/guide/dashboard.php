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

<div class="dashboard-container">
    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
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
        <div class="profile-layout">
            <!-- Левая панель профиля -->
            <div class="profile-sidebar">
                <div class="profile-card">
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch();
                    $avatarUrl = $user['avatar_url'] ?? null;
                    
                    // Получаем статистику
                    $excursionsCount = $pdo->prepare("SELECT COUNT(*) as count FROM excursions WHERE guide_id = ?");
                    $excursionsCount->execute([$user_id]);
                    $excursionsTotal = $excursionsCount->fetch()['count'];
                    
                    $ordersCount = $pdo->prepare("SELECT COUNT(*) as count FROM orders o JOIN excursion_dates ed ON o.excursion_date_id = ed.id JOIN excursions e ON ed.excursion_id = e.id WHERE e.guide_id = ?");
                    $ordersCount->execute([$user_id]);
                    $ordersTotal = $ordersCount->fetch()['count'];
                    
                    $completedCount = $pdo->prepare("SELECT COUNT(*) as count FROM orders o JOIN excursion_dates ed ON o.excursion_date_id = ed.id JOIN excursions e ON ed.excursion_id = e.id WHERE e.guide_id = ? AND o.status = 'completed'");
                    $completedCount->execute([$user_id]);
                    $completedTotal = $completedCount->fetch()['count'];
                    ?>
                    
                    <div class="profile-avatar">
                        <?php if($avatarUrl): ?>
                            <img src="<?php echo asset_path($avatarUrl); ?>" alt="Аватар" class="avatar-large">
                        <?php else: ?>
                            <div class="avatar-large-placeholder">
                                <i class="bi bi-person"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <h2 class="profile-name"><?php echo htmlspecialchars($user['full_name']); ?></h2>
                    <p class="profile-role">Гид</p>
                    
                    <div class="profile-stats">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $excursionsTotal; ?></span>
                            <span class="stat-label">Экскурсий</span>
                        </div>
                        <div class="stat-divider"></div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $ordersTotal; ?></span>
                            <span class="stat-label">Бронирований</span>
                        </div>
                        <div class="stat-divider"></div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $completedTotal; ?></span>
                            <span class="stat-label">Завершено</span>
                        </div>
                    </div>
                    
                    <form method="POST" action="<?php echo route_path('includes/upload_avatar.php'); ?>" enctype="multipart/form-data" class="avatar-upload-form">
                        <input type="file" name="avatar" id="avatarInput" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" style="display: none;" onchange="previewAvatar(this)">
                        <label for="avatarInput" class="btn-upload-avatar">
                            <i class="bi bi-camera"></i> Загрузить новое фото
                        </label>
                        <button type="submit" style="display: none;" id="submitAvatar"></button>
                    </form>
                    
                    <?php if($user['phone']): ?>
                        <div class="profile-location">
                            <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($user['phone']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="profile-location">
                        <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($user['email']); ?>
                    </div>
                    
                    <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--border-color);">
                        <form method="POST" action="<?php echo route_path('includes/delete_account.php'); ?>" onsubmit="return confirm('Вы уверены, что хотите удалить свой аккаунт? Это действие нельзя отменить. Все ваши данные будут удалены, включая все созданные экскурсии.');">
                            <button type="submit" name="confirm_delete" class="btn-delete-account" style="width: 100%; padding: 12px 24px; background: #e74c3c; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s ease;">
                                <i class="bi bi-trash"></i> Удалить аккаунт
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Правая панель редактирования -->
            <div class="profile-edit-panel">
                <div class="edit-panel-header">
                    <h2>ОСНОВНАЯ ИНФОРМАЦИЯ</h2>
                    <div class="edit-actions">
                        <button type="button" class="btn-cancel" onclick="resetForm()">Отмена</button>
                        <button type="submit" form="profileForm" class="btn-save">Сохранить</button>
                    </div>
                </div>
                
                <form method="POST" action="<?php echo route_path('includes/update_profile.php'); ?>" id="profileForm" class="profile-edit-form">
                    <div class="form-row-2">
                        <div class="form-group">
                            <label>ИМЯ</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>EMAIL</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row-2">
                        <div class="form-group">
                            <label>ТЕЛЕФОН</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>НОВЫЙ ПАРОЛЬ</label>
                            <input type="password" name="new_password" placeholder="Оставьте пустым, если не меняете">
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Вкладка моих экскурсий -->
    <div id="my_excursions" class="tab-content">
       <!-- <h2>Мои экскурсии</h2>-->
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
                    <p class="price"><i class="bi bi-currency-exchange"></i> <?php echo $excursion['price']; ?> руб.</p>
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
       <!-- <h2>Бронирования моих экскурсий</h2>-->
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
                        <a href="<?php echo route_path('includes/manage_order.php'); ?>?action=confirm&id=<?php echo $order['id']; ?>&redirect=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? route_path('pages/guide/dashboard.php')); ?>" class="btn btn-success">Подтвердить</a>
                        <a href="<?php echo route_path('includes/manage_order.php'); ?>?action=cancel&id=<?php echo $order['id']; ?>&redirect=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? route_path('pages/guide/dashboard.php')); ?>" class="btn btn-danger">Отклонить</a>
                    <?php elseif($order['status'] == 'confirmed'): ?>
                        <a href="<?php echo route_path('includes/manage_order.php'); ?>?action=complete&id=<?php echo $order['id']; ?>&redirect=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? route_path('pages/guide/dashboard.php')); ?>" class="btn btn-primary">Завершить</a>
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

function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.querySelector('.avatar-large');
            const placeholder = document.querySelector('.avatar-large-placeholder');
            if (preview) {
                preview.src = e.target.result;
            } else if (placeholder) {
                placeholder.innerHTML = '<img src="' + e.target.result + '" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">';
                placeholder.classList.remove('avatar-large-placeholder');
                placeholder.classList.add('avatar-large');
            }
            document.getElementById('submitAvatar').click();
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function resetForm() {
    const form = document.getElementById('profileForm');
    form.reset();
    location.reload();
}
</script>

<style>
.dashboard-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 24px;
}

.dashboard-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 32px;
    border-bottom: 2px solid var(--border-color);
    padding-bottom: 0;
}

.tab-btn {
    padding: 12px 24px;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    font-size: 16px;
    font-weight: 600;
    color: var(--text-light);
    cursor: pointer;
    transition: all 0.2s ease;
    margin-bottom: -2px;
}

.tab-btn:hover {
    color: var(--primary-color);
}

.tab-btn.active {
    color: var(--primary-color);
    border-bottom-color: var(--primary-color);
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.profile-layout {
    display: grid;
    grid-template-columns: 350px 1fr;
    gap: 32px;
    align-items: start;
}

.profile-sidebar {
    position: sticky;
    top: 24px;
}

.profile-card {
    background: var(--bg-white);
    border-radius: 16px;
    padding: 32px;
    box-shadow: var(--shadow-md);
    border: 1px solid var(--border-color);
    text-align: center;
}

.profile-avatar {
    margin-bottom: 24px;
}

.avatar-large {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid var(--border-color);
    margin: 0 auto;
    display: block;
}

.avatar-large-placeholder {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
    border: 4px solid var(--border-color);
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 64px;
    color: white;
}

.profile-name {
    font-size: 24px;
    font-weight: 700;
    color: var(--text-dark);
    margin: 0 0 8px 0;
}

.profile-role {
    font-size: 14px;
    color: var(--text-light);
    margin: 0 0 24px 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.profile-stats {
    display: flex;
    justify-content: space-around;
    align-items: center;
    padding: 24px 0;
    border-top: 1px solid var(--border-color);
    border-bottom: 1px solid var(--border-color);
    margin: 24px 0;
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
}

.stat-number {
    font-size: 24px;
    font-weight: 700;
    color: var(--text-dark);
}

.stat-label {
    font-size: 12px;
    color: var(--text-light);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-divider {
    width: 1px;
    height: 40px;
    background: var(--border-color);
}

.btn-upload-avatar {
    width: 100%;
    padding: 12px 24px;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-bottom: 24px;
}

.btn-upload-avatar:hover {
    background: var(--primary-hover);
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

.profile-location {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: var(--text-light);
    font-size: 14px;
    margin-bottom: 16px;
}

.profile-bio {
    text-align: left;
    color: var(--text-light);
    font-size: 14px;
    line-height: 1.6;
}

.profile-edit-panel {
    background: var(--bg-white);
    border-radius: 16px;
    padding: 32px;
    box-shadow: var(--shadow-md);
    border: 1px solid var(--border-color);
}

.edit-panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;
    padding-bottom: 24px;
    border-bottom: 1px solid var(--border-color);
}

.edit-panel-header h2 {
    font-size: 18px;
    font-weight: 700;
    color: var(--text-dark);
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.edit-actions {
    display: flex;
    gap: 12px;
}

.btn-cancel {
    padding: 10px 24px;
    background: var(--bg-white);
    color: var(--text-dark);
    border: 2px solid var(--border-color);
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-cancel:hover {
    border-color: var(--text-light);
    background: var(--bg-light);
}

.btn-save {
    padding: 10px 24px;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-save:hover {
    background: var(--primary-hover);
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

.profile-edit-form {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.form-row-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-group label {
    font-size: 12px;
    font-weight: 700;
    color: var(--text-dark);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.form-group input {
    padding: 12px 16px;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    font-size: 16px;
    font-family: inherit;
    transition: all 0.2s ease;
    background: var(--bg-white);
    color: var(--text-dark);
}

.form-group input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(255, 90, 95, 0.1);
}

@media (max-width: 1024px) {
    .profile-layout {
        grid-template-columns: 1fr;
    }
    
    .profile-sidebar {
        position: static;
    }
    
    .form-row-2 {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once base_path('includes/footer.php'); ?>




<?php
require_once __DIR__ . '/../../config/app.php';
require_once base_path('config/database.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ' . route_path('pages/login.php'));
    exit();
}

$stats = [
    'users' => $pdo->query("SELECT COUNT(*) AS total FROM users")->fetch()['total'],
    'guides' => $pdo->query("SELECT COUNT(*) AS total FROM users WHERE user_type = 'guide'")->fetch()['total'],
    'customers' => $pdo->query("SELECT COUNT(*) AS total FROM users WHERE user_type = 'customer'")->fetch()['total'],
    'excursions' => $pdo->query("SELECT COUNT(*) AS total FROM excursions")->fetch()['total'],
    'active_excursions' => $pdo->query("SELECT COUNT(*) AS total FROM excursions WHERE is_active = TRUE")->fetch()['total'],
    'orders' => $pdo->query("SELECT COUNT(*) AS total FROM orders")->fetch()['total']
];

$usersStmt = $pdo->query("SELECT id, username, email, full_name, user_type, created_at FROM users ORDER BY created_at DESC LIMIT 20");
$users = $usersStmt->fetchAll();

$latestOrdersStmt = $pdo->query("
    SELECT o.id, o.status, o.total_price, o.order_date, e.title, u.full_name AS customer_name
    FROM orders o
    JOIN excursion_dates ed ON o.excursion_date_id = ed.id
    JOIN excursions e ON ed.excursion_id = e.id
    JOIN users u ON o.customer_id = u.id
    ORDER BY o.order_date DESC
    LIMIT 15
");
$latestOrders = $latestOrdersStmt->fetchAll();

$excursionsStmt = $pdo->query("
    SELECT e.id, e.title, e.is_active, u.full_name AS guide_name, e.created_at
    FROM excursions e
    JOIN users u ON e.guide_id = u.id
    ORDER BY e.created_at DESC
    LIMIT 20
");
$excursions = $excursionsStmt->fetchAll();

$reviewsStmt = $pdo->query("
    SELECT r.id, r.rating, r.comment, r.created_at, u.full_name AS author, e.title
    FROM reviews r
    JOIN orders o ON r.order_id = o.id
    JOIN excursion_dates ed ON o.excursion_date_id = ed.id
    JOIN excursions e ON ed.excursion_id = e.id
    JOIN users u ON o.customer_id = u.id
    ORDER BY r.created_at DESC
    LIMIT 10
");
$reviews = $reviewsStmt->fetchAll();

require_once base_path('includes/header.php');
?>

<div class="dashboard-container" style="max-width: 1400px; margin: 0 auto; padding: 24px;">
    <h1><i class="bi bi-speedometer2"></i> Админ-панель</h1>

    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <div class="dashboard-tabs">
        <button class="tab-btn active" onclick="openTab('admin_panel')"><i class="bi bi-speedometer2"></i> Админ-панель</button>
        <button class="tab-btn" onclick="openTab('profile')"><i class="bi bi-person"></i> Профиль</button>
        <a href="<?php echo route_path('pages/admin/promo_codes.php'); ?>" class="tab-btn" style="text-decoration: none;">
            <i class="bi bi-ticket-perforated"></i> Промокоды
        </a>
    </div>
    
    <!-- Вкладка профиля -->
    <div id="profile" class="tab-content">
        <div class="profile-layout">
            <!-- Левая панель профиля -->
            <div class="profile-sidebar">
                <div class="profile-card">
                    <?php
                    $user_id = $_SESSION['user_id'];
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch();
                    $avatarUrl = $user['avatar_url'] ?? null;
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
                    <p class="profile-role">Администратор</p>
                    
                    <div class="profile-stats">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $stats['users']; ?></span>
                            <span class="stat-label">Пользователей</span>
                        </div>
                        <div class="stat-divider"></div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $stats['excursions']; ?></span>
                            <span class="stat-label">Экскурсий</span>
                        </div>
                        <div class="stat-divider"></div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $stats['orders']; ?></span>
                            <span class="stat-label">Заказов</span>
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
    
    <!-- Вкладка админ-панели -->
    <div id="admin_panel" class="tab-content active">

    <section class="stats-grid">
        <div class="stat-card">
            <h3>Пользователей</h3>
            <p><?php echo $stats['users']; ?></p>
        </div>
        <div class="stat-card">
            <h3>Гидов</h3>
            <p><?php echo $stats['guides']; ?></p>
        </div>
        <div class="stat-card">
            <h3>Клиентов</h3>
            <p><?php echo $stats['customers']; ?></p>
        </div>
        <div class="stat-card">
            <h3>Экскурсий</h3>
            <p><?php echo $stats['excursions']; ?> (<?php echo $stats['active_excursions']; ?> активны)</p>
        </div>
        <div class="stat-card">
            <h3>Заказов</h3>
            <p><?php echo $stats['orders']; ?></p>
        </div>
    </section>

    <section class="admin-section">
        <h2><i class="bi bi-people"></i> Управление пользователями (<?php echo count($users); ?>)</h2>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th><i class="bi bi-hash"></i> ID</th>
                        <th><i class="bi bi-person"></i> Имя</th>
                        <th><i class="bi bi-envelope"></i> Email</th>
                        <th><i class="bi bi-shield-check"></i> Роль</th>
                        <th><i class="bi bi-calendar"></i> Создан</th>
                        <th><i class="bi bi-gear"></i> Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($users)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-light);">
                                <i class="bi bi-inbox" style="font-size: 48px; display: block; margin-bottom: 16px; opacity: 0.3;"></i>
                                Пользователи не найдены
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($user['full_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <form method="POST" action="<?php echo route_path('includes/admin_actions.php'); ?>" class="inline-form">
                                    <input type="hidden" name="action" value="update_user_role">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                    <select name="user_type" onchange="this.form.submit()" 
                                            class="role-select" 
                                            <?php echo $user['id'] === $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                                        <option value="customer" <?php echo $user['user_type'] === 'customer' ? 'selected' : ''; ?>>Клиент</option>
                                        <option value="guide" <?php echo $user['user_type'] === 'guide' ? 'selected' : ''; ?>>Гид</option>
                                        <option value="admin" <?php echo $user['user_type'] === 'admin' ? 'selected' : ''; ?>>Админ</option>
                                    </select>
                                </form>
                            </td>
                            <td><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <?php if($user['avatar_url'] && $user['id'] !== $_SESSION['user_id']): ?>
                                    <form method="POST" action="<?php echo route_path('includes/admin_actions.php'); ?>" class="inline-form">
                                        <input type="hidden" name="action" value="toggle_user_avatar">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                        <button type="submit" class="btn btn-warning" style="padding: 6px 12px; font-size: 14px;">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="admin-section">
        <h2><i class="bi bi-map"></i> Управление экскурсиями (<?php echo count($excursions); ?>)</h2>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th><i class="bi bi-hash"></i> ID</th>
                        <th><i class="bi bi-bookmark"></i> Название</th>
                        <th><i class="bi bi-person"></i> Гид</th>
                        <th><i class="bi bi-toggle-on"></i> Статус</th>
                        <th><i class="bi bi-gear"></i> Действие</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($excursions)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-light);">
                                <i class="bi bi-inbox" style="font-size: 48px; display: block; margin-bottom: 16px; opacity: 0.3;"></i>
                                Экскурсии не найдены
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($excursions as $exc): ?>
                        <tr>
                            <td><?php echo $exc['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($exc['title']); ?></strong></td>
                            <td><?php echo htmlspecialchars($exc['guide_name']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $exc['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $exc['is_active'] ? 'Активна' : 'Отключена'; ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" action="<?php echo route_path('includes/admin_actions.php'); ?>" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle_excursion">
                                    <input type="hidden" name="excursion_id" value="<?php echo $exc['id']; ?>">
                                    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                    <button type="submit" class="btn <?php echo $exc['is_active'] ? 'btn-warning' : 'btn-success'; ?>" style="padding: 6px 12px; font-size: 14px;">
                                        <i class="bi <?php echo $exc['is_active'] ? 'bi-pause-circle' : 'bi-play-circle'; ?>"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="admin-section">
        <h2><i class="bi bi-cart"></i> Последние заказы (<?php echo count($latestOrders); ?>)</h2>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th><i class="bi bi-hash"></i> ID</th>
                        <th><i class="bi bi-bookmark"></i> Экскурсия</th>
                        <th><i class="bi bi-person"></i> Клиент</th>
                        <th><i class="bi bi-currency-exchange"></i> Сумма</th>
                        <th><i class="bi bi-info-circle"></i> Статус</th>
                        <th><i class="bi bi-gear"></i> Действие</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($latestOrders)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-light);">
                                <i class="bi bi-inbox" style="font-size: 48px; display: block; margin-bottom: 16px; opacity: 0.3;"></i>
                                Заказы не найдены
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($latestOrders as $order): ?>
                        <tr>
                            <td><?php echo $order['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($order['title']); ?></strong></td>
                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                            <td><strong style="color: var(--primary-color);"><?php echo number_format($order['total_price'], 2); ?> руб.</strong></td>
                            <td>
                                <?php
                                $statusClasses = [
                                    'pending' => 'status-pending',
                                    'confirmed' => 'status-confirmed',
                                    'cancelled' => 'status-cancelled',
                                    'completed' => 'status-completed'
                                ];
                                $statusTexts = [
                                    'pending' => 'Ожидание',
                                    'confirmed' => 'Подтвержден',
                                    'cancelled' => 'Отменен',
                                    'completed' => 'Завершен'
                                ];
                                $statusClass = $statusClasses[$order['status']] ?? 'status-pending';
                                $statusText = $statusTexts[$order['status']] ?? $order['status'];
                                ?>
                                <span class="status-badge <?php echo $statusClass; ?>">
                                    <?php echo $statusText; ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" action="<?php echo route_path('includes/admin_actions.php'); ?>" class="inline-form">
                                    <input type="hidden" name="action" value="update_order_status">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                    <select name="status" onchange="this.form.submit()" class="status-select">
                                        <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Ожидание</option>
                                        <option value="confirmed" <?php echo $order['status'] === 'confirmed' ? 'selected' : ''; ?>>Подтвержден</option>
                                        <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Отменен</option>
                                        <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Завершен</option>
                                    </select>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="admin-section">
        <h2><i class="bi bi-star"></i> Отзывы (<?php echo count($reviews); ?>)</h2>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th><i class="bi bi-hash"></i> ID</th>
                        <th><i class="bi bi-bookmark"></i> Экскурсия</th>
                        <th><i class="bi bi-person"></i> Автор</th>
                        <th><i class="bi bi-star-fill"></i> Оценка</th>
                        <th><i class="bi bi-chat-left-text"></i> Комментарий</th>
                        <th><i class="bi bi-gear"></i> Действие</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($reviews)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-light);">
                                <i class="bi bi-inbox" style="font-size: 48px; display: block; margin-bottom: 16px; opacity: 0.3;"></i>
                                Отзывы не найдены
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($reviews as $review): ?>
                        <tr>
                            <td><?php echo $review['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($review['title']); ?></strong></td>
                            <td><?php echo htmlspecialchars($review['author']); ?></td>
                            <td>
                                <span style="display: flex; align-items: center; gap: 4px;">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi bi-star<?php echo $i <= $review['rating'] ? '-fill' : ''; ?>" 
                                           style="color: <?php echo $i <= $review['rating'] ? '#FBBF24' : '#E0E0E0'; ?>;"></i>
                                    <?php endfor; ?>
                                    <strong style="margin-left: 8px; color: var(--text-dark);"><?php echo $review['rating']; ?>/5</strong>
                                </span>
                            </td>
                            <td style="max-width: 300px;">
                                <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" 
                                     title="<?php echo htmlspecialchars($review['comment']); ?>">
                                    <?php echo htmlspecialchars($review['comment']); ?>
                                </div>
                            </td>
                            <td>
                                <form method="POST" action="<?php echo route_path('includes/admin_actions.php'); ?>" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_review">
                                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                    <button type="submit" class="btn btn-danger" 
                                            style="padding: 6px 12px; font-size: 14px;"
                                            onclick="return confirm('Вы уверены, что хотите удалить этот отзыв?');">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    </div>
</div>

<script>
function openTab(tabName) {
    const tabContents = document.getElementsByClassName('tab-content');
    for (let i = 0; i < tabContents.length; i++) {
        tabContents[i].classList.remove('active');
    }

    const tabButtons = document.getElementsByClassName('tab-btn');
    for (let i = 0; i < tabButtons.length; i++) {
        tabButtons[i].classList.remove('active');
    }

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
    max-width: 1400px;
    margin: 0 auto;
    padding: 24px;
}

.dashboard-container h1 {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 32px;
    font-size: 32px;
    color: var(--text-dark);
}

.dashboard-container h1 i {
    color: var(--primary-color);
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

.tab-btn i {
    margin-right: 6px;
    font-size: 16px;
}

.dashboard-tabs a.tab-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    color: var(--text-light);
}

.dashboard-tabs a.tab-btn:hover {
    color: var(--primary-color);
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

/* Стили для админских таблиц */
.admin-section {
    background: var(--bg-white);
    border-radius: var(--radius-md);
    padding: 32px;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
    margin-bottom: 32px;
}

.admin-section h2 {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 24px;
    font-size: 24px;
    color: var(--text-dark);
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
}

.admin-table thead {
    background: var(--bg-light);
}

.admin-table th {
    padding: 16px;
    text-align: left;
    font-weight: 600;
    color: var(--text-dark);
    border-bottom: 2px solid var(--border-color);
    font-size: 14px;
    white-space: nowrap;
}

.admin-table th i {
    margin-right: 6px;
    color: var(--primary-color);
}

.admin-table td {
    padding: 16px;
    border-bottom: 1px solid var(--border-color);
    vertical-align: middle;
}

.admin-table tbody tr:hover {
    background: var(--bg-light);
}

.role-select,
.status-select {
    padding: 8px 12px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    background: var(--bg-white);
    color: var(--text-dark);
    cursor: pointer;
    transition: all 0.2s ease;
}

.role-select:focus,
.status-select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(255, 90, 95, 0.1);
}

.inline-form {
    display: inline;
    margin: 0;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 24px;
    margin-bottom: 32px;
}

.stat-card {
    background: var(--bg-white);
    padding: 24px;
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
    text-align: center;
    transition: all 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.stat-card h3 {
    font-size: 14px;
    color: var(--text-light);
    margin-bottom: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}

.stat-card p {
    font-size: 32px;
    font-weight: 700;
    color: var(--primary-color);
    margin: 0;
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
    
    .admin-section {
        padding: 20px;
    }
    
    .admin-table {
        font-size: 14px;
    }
    
    .admin-table th,
    .admin-table td {
        padding: 12px 8px;
    }
}
</style>

<?php require_once base_path('includes/footer.php'); ?>


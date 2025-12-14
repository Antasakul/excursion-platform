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

if($_SESSION['user_type'] !== 'customer') {
    // Перенаправляем в зависимости от роли
    if($_SESSION['user_type'] === 'admin') {
        header('Location: ' . route_path('pages/admin/dashboard.php'));
    } else {
        header('Location: ' . route_path('pages/guide/dashboard.php'));
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
        <button class="tab-btn" onclick="openTab('my_orders')">Мои заказы</button>
        <button class="tab-btn" onclick="openTab('favorites')">Избранное</button>
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
                    $ordersCount = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE customer_id = ?");
                    $ordersCount->execute([$user_id]);
                    $ordersTotal = $ordersCount->fetch()['count'];
                    
                    $favoritesCount = $pdo->prepare("SELECT COUNT(*) as count FROM favorites WHERE user_id = ?");
                    $favoritesCount->execute([$user_id]);
                    $favoritesTotal = $favoritesCount->fetch()['count'];
                    
                    $completedCount = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE customer_id = ? AND status = 'completed'");
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
                    <p class="profile-role">Клиент</p>
                    
                    <div class="profile-stats">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $ordersTotal; ?></span>
                            <span class="stat-label">Заказов</span>
                        </div>
                        <div class="stat-divider"></div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $favoritesTotal; ?></span>
                            <span class="stat-label">Избранное</span>
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
                        <form method="POST" action="<?php echo route_path('includes/delete_account.php'); ?>" onsubmit="return confirm('Вы уверены, что хотите удалить свой аккаунт? Это действие нельзя отменить. Все ваши данные будут удалены.');">
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

    <!-- Вкладка заказов клиента -->
    <div id="my_orders" class="tab-content">
        <h2>Мои заказы</h2>
        <div class="orders-list">
            <?php
            // Получаем заказы, исключая отмененные пользователем (показываем только отмененные гидом)
            $stmt = $pdo->prepare("
                SELECT o.*, e.title, e.id as excursion_id, u.full_name as guide_name, ed.available_date, ed.available_time
                FROM orders o
                JOIN excursion_dates ed ON o.excursion_date_id = ed.id
                JOIN excursions e ON ed.excursion_id = e.id
                JOIN users u ON e.guide_id = u.id
                WHERE o.customer_id = ? AND (o.cancelled_by IS NULL OR o.cancelled_by = 'guide')
                ORDER BY o.order_date DESC
            ");
            $stmt->execute([$user_id]);
            $orders = $stmt->fetchAll();
            
            if(count($orders) > 0):
                foreach($orders as $order):
                    // Проверка возможности отмены (минимум за 48 часов)
                    $excursion_datetime = strtotime($order['available_date'] . ' ' . $order['available_time']);
                    $hours_until = ($excursion_datetime - time()) / 3600;
                    $can_cancel = ($order['status'] === 'pending' || $order['status'] === 'confirmed') && $hours_until >= 48;
                    
                    // Проверка наличия отзыва
                    try {
                        $checkStmt = $pdo->query("SHOW COLUMNS FROM reviews LIKE 'guide_rating'");
                        $hasGuideRating = $checkStmt->rowCount() > 0;
                    } catch(PDOException $e) {
                        $hasGuideRating = false;
                    }
                    
                    if($hasGuideRating) {
                        $reviewStmt = $pdo->prepare("SELECT id, rating, guide_rating, comment FROM reviews WHERE order_id = ?");
                    } else {
                        $reviewStmt = $pdo->prepare("SELECT id, rating, comment FROM reviews WHERE order_id = ?");
                    }
                    $reviewStmt->execute([$order['id']]);
                    $review = $reviewStmt->fetch();
                    if($review && !isset($review['guide_rating'])) {
                        $review['guide_rating'] = null;
                    }
            ?>
            <div class="order-item" style="border: 1px solid #ddd; padding: 1.5rem; margin-bottom: 1.5rem; border-radius: 8px;">
                <h4><?php echo htmlspecialchars($order['title']); ?></h4>
                <p><strong>Гид:</strong> <?php echo htmlspecialchars($order['guide_name']); ?></p>
                <p><strong>Дата и время:</strong> <?php echo date('d.m.Y', strtotime($order['available_date'])); ?> в <?php echo $order['available_time']; ?></p>
                <p><strong>Участников:</strong> <?php echo $order['participants_count']; ?></p>
                <p><strong>Стоимость:</strong> <?php echo number_format($order['total_price'], 2); ?> руб.</p>
                <p><strong>Статус:</strong> <span class="status-<?php echo $order['status']; ?>" style="padding: 0.25rem 0.5rem; border-radius: 4px; font-weight: bold;">
                    <?php echo getOrderStatus($order); ?>
                </span></p>
                
                <div class="order-actions" style="margin-top: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <?php if($can_cancel): ?>
                        <a href="<?php echo route_path('includes/cancel_order.php'); ?>?order_id=<?php echo $order['id']; ?>&redirect=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? route_path('pages/customer/dashboard.php')); ?>" 
                           class="btn btn-danger" 
                           onclick="return confirm('Вы уверены, что хотите отменить бронирование? Места будут возвращены.')">
                           <i class="bi bi-x-circle"></i> Отменить бронь
                        </a>
                    <?php elseif($order['status'] !== 'cancelled' && $order['status'] !== 'completed'): ?>
                        <small style="color: #888;"><i class="bi bi-exclamation-triangle"></i> Отмена возможна не менее чем за 48 часов до начала экскурсии</small>
                    <?php endif; ?>
                  
                    <a href="<?php echo route_path('pages/booking.php'); ?>?excursion_id=<?php echo $order['excursion_id']; ?>" class="btn btn-secondary">
                        <i class="bi bi-file-text"></i> Посмотреть экскурсию
                    </a>
                </div>
                
                <!-- Форма отзыва -->
                <?php if($order['status'] === 'completed'): ?>
                    <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #eee;">
                        <?php if($review): ?>
                            <div style="background: #e8f5e9; padding: 1rem; border-radius: 4px;">
                                <h5><i class="bi bi-check-circle"></i> Ваш отзыв:</h5>
                                <div style="margin: 0.5rem 0;">
                                    <strong>Оценка экскурсии:</strong>
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <span style="font-size: 1.2rem;"><i class="bi <?php echo $i <= $review['rating'] ? 'bi-star-fill' : 'bi-star'; ?>"></i></span>
                                    <?php endfor; ?>
                                    <span style="margin-left: 0.5rem;">(<?php echo $review['rating']; ?>/5)</span>
                                </div>
                                <?php if(isset($review['guide_rating']) && $review['guide_rating']): ?>
                                <div style="margin: 0.5rem 0;">
                                    <strong>Оценка гида:</strong>
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <span style="font-size: 1.2rem;"><i class="bi <?php echo $i <= $review['guide_rating'] ? 'bi-star-fill' : 'bi-star'; ?>"></i></span>
                                    <?php endfor; ?>
                                    <span style="margin-left: 0.5rem;">(<?php echo $review['guide_rating']; ?>/5)</span>
                                </div>
                                <?php endif; ?>
                                <p style="margin-top: 0.5rem;"><strong>Комментарий:</strong> <?php echo htmlspecialchars($review['comment']); ?></p>
                            </div>
                        <?php else: ?>
                            <div style="margin-top: 1rem;">
                                <h5 style="margin-bottom: 1rem; color: #3498db;"><i class="bi bi-pencil-square"></i> Оставить отзыв</h5>
                                <form method="POST" action="<?php echo route_path('includes/submit_review.php'); ?>" style="padding: 1rem; background: #f8f9fa; border-radius: 4px;">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <div class="form-group" style="margin-bottom: 1rem;">
                                        <label><strong>Оценка экскурсии:</strong></label>
                                        <select name="rating" required style="padding: 0.5rem; width: 100%; max-width: 300px; border: 1px solid #ddd; border-radius: 4px;">
                                            <option value="5"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i> Отлично (5)</option>
                                            <option value="4"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i> Хорошо (4)</option>
                                            <option value="3"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i> Нормально (3)</option>
                                            <option value="2"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i> Плохо (2)</option>
                                            <option value="1"><i class="bi bi-star-fill"></i> Ужасно (1)</option>
                                        </select>
                                    </div>
                                    <div class="form-group" style="margin-bottom: 1rem;">
                                        <label><strong>Оценка гида:</strong></label>
                                        <select name="guide_rating" required style="padding: 0.5rem; width: 100%; max-width: 300px; border: 1px solid #ddd; border-radius: 4px;">
                                            <option value="5"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i> Отлично (5)</option>
                                            <option value="4"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i> Хорошо (4)</option>
                                            <option value="3"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i> Нормально (3)</option>
                                            <option value="2"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i> Плохо (2)</option>
                                            <option value="1"><i class="bi bi-star-fill"></i> Ужасно (1)</option>
                                        </select>
                                    </div>
                                    <div class="form-group" style="margin-bottom: 1rem;">
                                        <label><strong>Комментарий:</strong></label>
                                        <textarea name="comment" rows="4" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;" placeholder="Расскажите о вашем опыте..."></textarea>
                                    </div>
                                    <button type="submit" name="submit_review" class="btn btn-primary"><i class="bi bi-send"></i> Отправить отзыв</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php 
                endforeach;
            else:
            ?>
            <p style="text-align: center; padding: 2rem; color: #888;">У вас пока нет заказов. <a href="<?php echo route_path('pages/excursions.php'); ?>">Посмотреть экскурсии</a></p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Вкладка избранного -->
    <div id="favorites" class="tab-content">
        <h2>Избранное</h2>
        <div class="excursions-grid">
            <?php
            $stmt = $pdo->prepare("
                SELECT e.*, u.full_name as guide_name
                FROM favorites f
                JOIN excursions e ON f.excursion_id = e.id
                JOIN users u ON e.guide_id = u.id
                WHERE f.user_id = ? AND e.is_active = TRUE
                ORDER BY f.created_at DESC
            ");
            $stmt->execute([$user_id]);
            $favorites = $stmt->fetchAll();
            
            if(count($favorites) > 0):
                foreach($favorites as $exc):
            ?>
            <div class="excursion-card" style="position: relative;">
                <?php if($exc['image_url']): ?>
                    <img src="<?php echo asset_path($exc['image_url']); ?>" alt="<?php echo htmlspecialchars($exc['title']); ?>" style="width: 100%; height: 200px; object-fit: cover; border-radius: 8px 8px 0 0;">
                <?php endif; ?>
                <div class="card-content" style="padding: 1rem;">
                    <h3><?php echo htmlspecialchars($exc['title']); ?></h3>
                    <p class="description" style="color: #666; margin: 0.5rem 0;"><?php echo htmlspecialchars($exc['short_description'] ?? ''); ?></p>
                    <p class="city"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($exc['city']); ?></p>
                    <p class="category"><i class="bi bi-tag"></i> <?php echo htmlspecialchars($exc['category']); ?></p>
                    <p class="guide"><i class="bi bi-person"></i> Гид: <?php echo htmlspecialchars($exc['guide_name']); ?></p>
                    <p class="duration"><i class="bi bi-clock"></i> <?php echo $exc['duration']; ?> мин.</p>
                    <p class="price" style="font-weight: bold; font-size: 1.2rem; color: #e74c3c;"><i class="bi bi-currency-exchange"></i> <?php echo number_format($exc['price'], 2); ?> руб./чел.</p>
                    <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                        <a href="<?php echo route_path('pages/booking.php'); ?>?excursion_id=<?php echo $exc['id']; ?>" class="btn btn-primary" style="flex: 1;">Забронировать</a>
                        <form method="POST" action="<?php echo route_path('includes/manage_favorite.php'); ?>" style="margin: 0;">
                            <input type="hidden" name="excursion_id" value="<?php echo $exc['id']; ?>">
                            <input type="hidden" name="action" value="remove">
                            <button type="submit" class="btn btn-danger" title="Убрать из избранного"><i class="bi bi-heart-fill"></i></button>
                        </form>
                    </div>
                </div>
            </div>
            <?php 
                endforeach;
            else:
            ?>
            <div style="text-align: center; padding: 3rem; color: #888;">
                <p style="font-size: 1.5rem; margin-bottom: 1rem;"><i class="bi bi-heart"></i></p>
                <p>Вы еще не добавили ни одной экскурсии в избранное.</p>
                <a href="<?php echo route_path('pages/excursions.php'); ?>" class="btn btn-primary" style="margin-top: 1rem;">Посмотреть экскурсии</a>
            </div>
            <?php endif; ?>
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
            // Автоматически отправляем форму
            document.getElementById('submitAvatar').click();
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function resetForm() {
    const form = document.getElementById('profileForm');
    form.reset();
    // Перезагружаем страницу для сброса к исходным значениям
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

/* Layout профиля */
.profile-layout {
    display: grid;
    grid-template-columns: 350px 1fr;
    gap: 32px;
    align-items: start;
}

/* Левая панель профиля */
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

/* Правая панель редактирования */
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

.form-group input,
.form-group textarea {
    padding: 12px 16px;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    font-size: 16px;
    font-family: inherit;
    transition: all 0.2s ease;
    background: var(--bg-white);
    color: var(--text-dark);
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(255, 90, 95, 0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 120px;
}

.form-section {
    margin-top: 8px;
}

.form-section h3 {
    font-size: 18px;
    font-weight: 700;
    color: var(--text-dark);
    margin: 0 0 16px 0;
    text-transform: uppercase;
    letter-spacing: 1px;
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




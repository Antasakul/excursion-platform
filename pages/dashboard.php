<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(!isset($_SESSION['user_id'])) {
    header('Location: ' . route_path('pages/login.php'));
    exit();
}

// Перенаправляем в зависимости от роли
if(isset($_SESSION['user_type'])) {
    if($_SESSION['user_type'] === 'admin') {
        header('Location: ' . route_path('pages/admin/dashboard.php'));
    } elseif($_SESSION['user_type'] === 'guide') {
        header('Location: ' . route_path('pages/guide/dashboard.php'));
    } else {
        header('Location: ' . route_path('pages/customer/dashboard.php'));
    }
    exit();
}

require_once base_path('includes/header.php');

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
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
    <h1>Личный кабинет</h1>
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
        <?php if($user_type == 'guide'): ?>
            <button class="tab-btn" onclick="openTab('my_excursions')">Мои экскурсии</button>
            <button class="tab-btn" onclick="openTab('guide_orders')">Бронирования</button>
        <?php else: ?>
            <button class="tab-btn" onclick="openTab('my_orders')">Мои заказы</button>
            <button class="tab-btn" onclick="openTab('favorites')">Избранное</button>
        <?php endif; ?>
    </div>

    <!-- Вкладка профиля -->
    <div id="profile" class="tab-content active">
        <h2>Настройки профиля</h2>
        <?php
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        $avatarUrl = $user['avatar_url'] ?? null;
        ?>
        
        <!-- Загрузка фото профиля -->
        <div class="avatar-upload-section" style="margin-bottom: 32px; padding: 24px; background: var(--bg-white); border-radius: var(--radius-md); box-shadow: var(--shadow-sm); border: 1px solid var(--border-color);">
            <h3 style="margin-bottom: 16px;">Фото профиля</h3>
            <div style="display: flex; align-items: center; gap: 24px; flex-wrap: wrap;">
                <div style="flex-shrink: 0;">
                    <?php if($avatarUrl): ?>
                        <img src="<?php echo asset_path($avatarUrl); ?>" alt="Аватар" class="avatar-preview" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid var(--border-color);">
                    <?php else: ?>
                        <div class="avatar-placeholder" style="width: 120px; height: 120px; border-radius: 50%; background: var(--bg-light); border: 3px solid var(--border-color); display: flex; align-items: center; justify-content: center; font-size: 48px; color: var(--text-light);">
                            <i class="bi bi-person"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div style="flex: 1; min-width: 250px;">
                    <form method="POST" action="<?php echo route_path('includes/upload_avatar.php'); ?>" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 12px;">
                        <input type="file" name="avatar" id="avatarInput" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" style="display: none;" onchange="previewAvatar(this)">
                        <label for="avatarInput" class="btn btn-secondary" style="cursor: pointer; width: auto; display: inline-flex;">
                            <i class="bi bi-camera"></i> Выбрать фото
                        </label>
                        <button type="submit" class="btn btn-primary" style="width: auto;">
                            <i class="bi bi-upload"></i> Загрузить фото
                        </button>
                        <small style="color: var(--text-light); font-size: 13px;">Разрешены: JPG, PNG, GIF, WEBP. Максимальный размер: 5 МБ</small>
                    </form>
                </div>
            </div>
        </div>
        
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

    <?php if($user_type == 'guide'): ?>
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
                        <p class="price"><i class="bi bi-currency-exchange"></i> <?php echo $excursion['price']; ?> руб.</p>
                        <p class="status">Статус: <?php echo $excursion['is_active'] ? 'Активна' : 'Неактивна'; ?></p>
                        <div class="card-actions">
                            <a href="<?php echo route_path('pages/edit_excursion.php'); ?>?id=<?php echo $excursion['id']; ?>" class="btn btn-secondary">Редактировать</a>
                            <a href="<?php echo route_path('includes/manage_excursion.php'); ?>?action=toggle&id=<?php echo $excursion['id']; ?>&redirect=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? route_path('pages/dashboard.php')); ?>" 
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
                            <a href="<?php echo route_path('includes/manage_order.php'); ?>?action=confirm&id=<?php echo $order['id']; ?>&redirect=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? route_path('pages/dashboard.php')); ?>" class="btn btn-success">Подтвердить</a>
                            <a href="<?php echo route_path('includes/manage_order.php'); ?>?action=cancel&id=<?php echo $order['id']; ?>&redirect=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? route_path('pages/dashboard.php')); ?>" class="btn btn-danger">Отклонить</a>
                        <?php elseif($order['status'] == 'confirmed'): ?>
                            <a href="<?php echo route_path('includes/manage_order.php'); ?>?action=complete&id=<?php echo $order['id']; ?>&redirect=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? route_path('pages/dashboard.php')); ?>" class="btn btn-primary">Завершить</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

    <?php else: ?>
        <!-- Вкладка заказов клиента -->
        <div id="my_orders" class="tab-content">
            <h2>Мои заказы</h2>
            <?php if(isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
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
                        // Проверяем наличие поля guide_rating
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
                            <a href="<?php echo route_path('includes/cancel_order.php'); ?>?order_id=<?php echo $order['id']; ?>&redirect=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? route_path('pages/dashboard.php')); ?>" 
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
                                <details style="margin-top: 1rem;" <?php echo !$review ? 'open' : ''; ?>>
                                    <summary style="cursor: pointer; font-weight: bold; color: #3498db;"><i class="bi <?php echo $review ? 'bi-pencil' : 'bi-pencil-square'; ?>"></i> <?php echo $review ? 'Редактировать отзыв' : 'Оставить отзыв'; ?></summary>
                                    <form method="POST" action="<?php echo route_path('includes/submit_review.php'); ?>" style="margin-top: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 4px;">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <?php if($review): ?>
                                            <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                        <?php endif; ?>
                                        <div class="form-group" style="margin-bottom: 1rem;">
                                            <label><strong>Оценка экскурсии:</strong></label>
                                            <select name="rating" required style="padding: 0.5rem; width: 100%; max-width: 300px; border: 1px solid #ddd; border-radius: 4px;">
                                                <option value="5" <?php echo ($review && $review['rating'] == 5) ? 'selected' : ''; ?>><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i> Отлично (5)</option>
                                                <option value="4" <?php echo ($review && $review['rating'] == 4) ? 'selected' : ''; ?>><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i> Хорошо (4)</option>
                                                <option value="3" <?php echo ($review && $review['rating'] == 3) ? 'selected' : ''; ?>><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i> Нормально (3)</option>
                                                <option value="2" <?php echo ($review && $review['rating'] == 2) ? 'selected' : ''; ?>><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i> Плохо (2)</option>
                                                <option value="1" <?php echo ($review && $review['rating'] == 1) ? 'selected' : ''; ?>><i class="bi bi-star-fill"></i> Ужасно (1)</option>
                                            </select>
                                        </div>
                                        <div class="form-group" style="margin-bottom: 1rem;">
                                            <label><strong>Оценка гида:</strong></label>
                                            <select name="guide_rating" required style="padding: 0.5rem; width: 100%; max-width: 300px; border: 1px solid #ddd; border-radius: 4px;">
                                                <option value="5" <?php echo ($review && $review['guide_rating'] == 5) ? 'selected' : ''; ?>><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i> Отлично (5)</option>
                                                <option value="4" <?php echo ($review && $review['guide_rating'] == 4) ? 'selected' : ''; ?>><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i> Хорошо (4)</option>
                                                <option value="3" <?php echo ($review && $review['guide_rating'] == 3) ? 'selected' : ''; ?>><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i> Нормально (3)</option>
                                                <option value="2" <?php echo ($review && $review['guide_rating'] == 2) ? 'selected' : ''; ?>><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i> Плохо (2)</option>
                                                <option value="1" <?php echo ($review && $review['guide_rating'] == 1) ? 'selected' : ''; ?>><i class="bi bi-star-fill"></i> Ужасно (1)</option>
                                            </select>
                                        </div>
                                        <div class="form-group" style="margin-bottom: 1rem;">
                                            <label><strong>Комментарий:</strong></label>
                                            <textarea name="comment" rows="4" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;" placeholder="Расскажите о вашем опыте..."><?php echo $review ? htmlspecialchars($review['comment']) : ''; ?></textarea>
                                        </div>
                                        <button type="submit" name="submit_review" class="btn btn-primary"><i class="bi <?php echo $review ? 'bi-save' : 'bi-send'; ?>"></i> <?php echo $review ? 'Сохранить изменения' : 'Отправить отзыв'; ?></button>
                                    </form>
                                </details>
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
            <?php if(isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
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
    <?php endif; ?>
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
            const preview = document.querySelector('.avatar-preview');
            const placeholder = document.querySelector('.avatar-placeholder');
            if (preview) {
                preview.src = e.target.result;
            } else if (placeholder) {
                placeholder.innerHTML = '<img src="' + e.target.result + '" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">';
                placeholder.classList.remove('avatar-placeholder');
                placeholder.classList.add('avatar-preview');
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once base_path('includes/footer.php'); ?>
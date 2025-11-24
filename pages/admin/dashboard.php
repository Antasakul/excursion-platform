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

<div class="container admin-dashboard">
    <h1>Админ-панель</h1>

    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

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
        <h2>Управление пользователями</h2>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Имя</th>
                        <th>Email</th>
                        <th>Роль</th>
                        <th>Создан</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <form method="POST" action="<?php echo route_path('includes/admin_actions.php'); ?>" class="inline-form">
                                <input type="hidden" name="action" value="update_user_role">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <select name="user_type" onchange="this.form.submit()" <?php echo $user['id'] === $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                                    <option value="customer" <?php echo $user['user_type'] === 'customer' ? 'selected' : ''; ?>>Клиент</option>
                                    <option value="guide" <?php echo $user['user_type'] === 'guide' ? 'selected' : ''; ?>>Гид</option>
                                    <option value="admin" <?php echo $user['user_type'] === 'admin' ? 'selected' : ''; ?>>Админ</option>
                                </select>
                            </form>
                        </td>
                        <td><?php echo $user['created_at']; ?></td>
                        <td>
                            <?php if($user['avatar_url']): ?>
                                <form method="POST" action="<?php echo route_path('includes/admin_actions.php'); ?>" class="inline-form">
                                    <input type="hidden" name="action" value="toggle_user_avatar">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="btn btn-warning" <?php echo $user['id'] === $_SESSION['user_id'] ? 'disabled' : ''; ?>>Удалить аватар</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="admin-section">
        <h2>Экскурсии</h2>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Гид</th>
                        <th>Статус</th>
                        <th>Действие</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($excursions as $exc): ?>
                    <tr>
                        <td><?php echo $exc['id']; ?></td>
                        <td><?php echo htmlspecialchars($exc['title']); ?></td>
                        <td><?php echo htmlspecialchars($exc['guide_name']); ?></td>
                        <td><?php echo $exc['is_active'] ? 'Активна' : 'Отключена'; ?></td>
                        <td>
                            <form method="POST" action="<?php echo route_path('includes/admin_actions.php'); ?>">
                                <input type="hidden" name="action" value="toggle_excursion">
                                <input type="hidden" name="excursion_id" value="<?php echo $exc['id']; ?>">
                                <button type="submit" class="btn <?php echo $exc['is_active'] ? 'btn-warning' : 'btn-success'; ?>">
                                    <?php echo $exc['is_active'] ? 'Деактивировать' : 'Активировать'; ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="admin-section">
        <h2>Последние заказы</h2>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Экскурсия</th>
                        <th>Клиент</th>
                        <th>Сумма</th>
                        <th>Статус</th>
                        <th>Действие</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($latestOrders as $order): ?>
                    <tr>
                        <td><?php echo $order['id']; ?></td>
                        <td><?php echo htmlspecialchars($order['title']); ?></td>
                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                        <td><?php echo $order['total_price']; ?> руб.</td>
                        <td><?php echo $order['status']; ?></td>
                        <td>
                            <form method="POST" action="<?php echo route_path('includes/admin_actions.php'); ?>" class="inline-form">
                                <input type="hidden" name="action" value="update_order_status">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <select name="status" onchange="this.form.submit()">
                                    <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Ожидание</option>
                                    <option value="confirmed" <?php echo $order['status'] === 'confirmed' ? 'selected' : ''; ?>>Подтвержден</option>
                                    <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Отменен</option>
                                    <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Завершен</option>
                                </select>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="admin-section">
        <h2>Отзывы</h2>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Экскурсия</th>
                        <th>Автор</th>
                        <th>Оценка</th>
                        <th>Комментарий</th>
                        <th>Удалить</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($reviews as $review): ?>
                    <tr>
                        <td><?php echo $review['id']; ?></td>
                        <td><?php echo htmlspecialchars($review['title']); ?></td>
                        <td><?php echo htmlspecialchars($review['author']); ?></td>
                        <td><?php echo $review['rating']; ?>/5</td>
                        <td><?php echo htmlspecialchars($review['comment']); ?></td>
                        <td>
                            <form method="POST" action="<?php echo route_path('includes/admin_actions.php'); ?>">
                                <input type="hidden" name="action" value="delete_review">
                                <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                <button type="submit" class="btn btn-danger">Удалить</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php require_once base_path('includes/footer.php'); ?>


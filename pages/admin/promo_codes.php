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

// Получаем все промокоды
try {
    $promoCodesStmt = $pdo->query("
        SELECT * FROM promo_codes 
        ORDER BY id DESC
    ");
    $promoCodes = $promoCodesStmt->fetchAll();
} catch(PDOException $e) {
    // Если есть поле created_at, используем его
    try {
        $promoCodesStmt = $pdo->query("
            SELECT * FROM promo_codes 
            ORDER BY created_at DESC
        ");
        $promoCodes = $promoCodesStmt->fetchAll();
    } catch(PDOException $e2) {
        $promoCodes = [];
    }
}

require_once base_path('includes/header.php');
?>

<div class="dashboard-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
        <h1><i class="bi bi-ticket-perforated"></i> Управление промокодами</h1>
        <a href="<?php echo route_path('pages/admin/dashboard.php'); ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Назад к панели
        </a>
    </div>

    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <i class="bi bi-exclamation-triangle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Форма добавления/редактирования промокода -->
    <div class="promo-form-card">
        <h2>
            <i class="bi bi-plus-circle"></i> 
            <span id="form-title">Добавить новый промокод</span>
        </h2>
        <form method="POST" action="<?php echo route_path('includes/manage_promo.php'); ?>" id="promoForm">
            <input type="hidden" name="action" value="create" id="form-action">
            <input type="hidden" name="promo_id" id="promo-id">
            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
            
            <div class="form-row-2">
                <div class="form-group">
                    <label><i class="bi bi-tag"></i> Код промокода</label>
                    <input type="text" name="code" id="promo-code" required 
                           placeholder="SUMMER2024" pattern="[A-Z0-9]+" 
                           style="text-transform: uppercase;">
                    <small>Только заглавные буквы и цифры</small>
                </div>
                
                <div class="form-group">
                    <label><i class="bi bi-percent"></i> Процент скидки</label>
                    <input type="number" name="discount_percent" id="promo-discount" 
                           required min="1" max="100" step="0.01" 
                           placeholder="10.00">
                    <small>От 1 до 100%</small>
                </div>
            </div>
            
            <div class="form-row-2">
                <div class="form-group">
                    <label><i class="bi bi-calendar-check"></i> Срок действия</label>
                    <input type="date" name="valid_until" id="promo-valid-until" required>
                    <small>Дата окончания действия промокода</small>
                </div>
                
                <div class="form-group">
                    <label><i class="bi bi-123"></i> Максимум использований</label>
                    <input type="number" name="max_uses" id="promo-max-uses" 
                           required min="1" placeholder="100">
                    <small>Максимальное количество использований</small>
                </div>
            </div>
            
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="is_active" id="promo-is-active" checked style="width: auto;">
                    <span><i class="bi bi-toggle-on"></i> Активен</span>
                </label>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Сохранить
                </button>
                <button type="button" class="btn btn-secondary" onclick="resetForm()" id="cancel-btn" style="display: none;">
                    <i class="bi bi-x-lg"></i> Отмена
                </button>
            </div>
        </form>
    </div>

    <!-- Список промокодов -->
    <div class="promo-list-section">
        <h2><i class="bi bi-list-ul"></i> Все промокоды (<?php echo count($promoCodes); ?>)</h2>
        
        <div class="table-responsive">
            <table class="promo-table">
                <thead>
                    <tr>
                        <th><i class="bi bi-hash"></i> ID</th>
                        <th><i class="bi bi-tag"></i> Код</th>
                        <th><i class="bi bi-percent"></i> Скидка</th>
                        <th><i class="bi bi-123"></i> Использовано</th>
                        <th><i class="bi bi-calendar-check"></i> Срок действия</th>
                        <th><i class="bi bi-toggle-on"></i> Статус</th>
                        <th><i class="bi bi-gear"></i> Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($promoCodes)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-light);">
                                <i class="bi bi-inbox" style="font-size: 48px; display: block; margin-bottom: 16px; opacity: 0.3;"></i>
                                Промокоды не найдены
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($promoCodes as $promo): ?>
                            <?php
                            $isExpired = strtotime($promo['valid_until']) < time();
                            $isExhausted = $promo['used_count'] >= $promo['max_uses'];
                            $statusClass = '';
                            $statusText = '';
                            
                            if(!$promo['is_active']) {
                                $statusClass = 'status-inactive';
                                $statusText = 'Неактивен';
                            } elseif($isExpired) {
                                $statusClass = 'status-expired';
                                $statusText = 'Истек';
                            } elseif($isExhausted) {
                                $statusClass = 'status-exhausted';
                                $statusText = 'Исчерпан';
                            } else {
                                $statusClass = 'status-active';
                                $statusText = 'Активен';
                            }
                            ?>
                            <tr>
                                <td><?php echo $promo['id']; ?></td>
                                <td>
                                    <strong style="font-family: monospace; font-size: 16px; color: var(--primary-color);">
                                        <?php echo htmlspecialchars($promo['code']); ?>
                                    </strong>
                                </td>
                                <td>
                                    <span style="color: var(--secondary-color); font-weight: 600;">
                                        <?php echo number_format($promo['discount_percent'], 2); ?>%
                                    </span>
                                </td>
                                <td>
                                    <span style="color: var(--text-dark);">
                                        <?php echo $promo['used_count']; ?> / <?php echo $promo['max_uses']; ?>
                                    </span>
                                    <?php if($promo['max_uses'] > 0): ?>
                                        <div style="width: 100px; height: 4px; background: var(--bg-light); border-radius: 2px; margin-top: 4px;">
                                            <div style="width: <?php echo min(100, ($promo['used_count'] / $promo['max_uses']) * 100); ?>%; height: 100%; background: var(--primary-color); border-radius: 2px;"></div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo date('d.m.Y', strtotime($promo['valid_until'])); ?>
                                    <?php if($isExpired): ?>
                                        <br><small style="color: var(--danger-color);"><i class="bi bi-exclamation-circle"></i> Истек</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo $statusText; ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                        <button onclick="editPromo(<?php echo htmlspecialchars(json_encode($promo)); ?>)" 
                                                class="btn btn-secondary" style="padding: 6px 12px; font-size: 14px;">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" action="<?php echo route_path('includes/manage_promo.php'); ?>" 
                                              style="display: inline;" 
                                              onsubmit="return confirm('Вы уверены, что хотите удалить этот промокод?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="promo_id" value="<?php echo $promo['id']; ?>">
                                            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                            <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 14px;">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.promo-form-card {
    background: var(--bg-white);
    border-radius: var(--radius-md);
    padding: 32px;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
    margin-bottom: 32px;
}

.promo-form-card h2 {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 24px;
    font-size: 24px;
    color: var(--text-dark);
}

.promo-list-section {
    background: var(--bg-white);
    border-radius: var(--radius-md);
    padding: 32px;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
}

.promo-list-section h2 {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 24px;
    font-size: 24px;
    color: var(--text-dark);
}

.table-responsive {
    overflow-x: auto;
}

.promo-table {
    width: 100%;
    border-collapse: collapse;
}

.promo-table thead {
    background: var(--bg-light);
}

.promo-table th {
    padding: 16px;
    text-align: left;
    font-weight: 600;
    color: var(--text-dark);
    border-bottom: 2px solid var(--border-color);
    font-size: 14px;
    white-space: nowrap;
}

.promo-table th i {
    margin-right: 6px;
    color: var(--primary-color);
}

.promo-table td {
    padding: 16px;
    border-bottom: 1px solid var(--border-color);
    vertical-align: middle;
}

.promo-table tbody tr:hover {
    background: var(--bg-light);
}

.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-active {
    background: #D1FAE5;
    color: #10B981;
}

.status-inactive {
    background: #FEE2E2;
    color: #EF4444;
}

.status-expired {
    background: #FEF3C7;
    color: #F59E0B;
}

.status-exhausted {
    background: #E5E7EB;
    color: #6B7280;
}

.form-group small {
    display: block;
    margin-top: 6px;
    font-size: 12px;
    color: var(--text-light);
}

@media (max-width: 768px) {
    .promo-form-card,
    .promo-list-section {
        padding: 20px;
    }
    
    .promo-table {
        font-size: 14px;
    }
    
    .promo-table th,
    .promo-table td {
        padding: 12px 8px;
    }
}
</style>

<script>
function editPromo(promo) {
    document.getElementById('form-title').textContent = 'Редактировать промокод';
    document.getElementById('form-action').value = 'update';
    document.getElementById('promo-id').value = promo.id;
    document.getElementById('promo-code').value = promo.code;
    document.getElementById('promo-discount').value = promo.discount_percent;
    document.getElementById('promo-valid-until').value = promo.valid_until;
    document.getElementById('promo-max-uses').value = promo.max_uses;
    document.getElementById('promo-is-active').checked = promo.is_active == 1;
    document.getElementById('cancel-btn').style.display = 'inline-flex';
    
    // Прокрутка к форме
    document.querySelector('.promo-form-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function resetForm() {
    document.getElementById('form-title').textContent = 'Добавить новый промокод';
    document.getElementById('form-action').value = 'create';
    document.getElementById('promo-id').value = '';
    document.getElementById('promoForm').reset();
    document.getElementById('promo-is-active').checked = true;
    document.getElementById('cancel-btn').style.display = 'none';
}

// Автоматически преобразуем код в верхний регистр
document.getElementById('promo-code').addEventListener('input', function(e) {
    e.target.value = e.target.value.toUpperCase();
});
</script>

<?php require_once base_path('includes/footer.php'); ?>


<?php
require_once __DIR__ . '/../config/app.php';
require_once base_path('config/database.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'guide') {
    header('Location: ' . route_path('pages/login.php'));
    exit();
}

$excursionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM excursions WHERE id = ? AND guide_id = ?");
$stmt->execute([$excursionId, $_SESSION['user_id']]);
$excursion = $stmt->fetch();

if(!$excursion) {
    $_SESSION['error'] = 'Экскурсия не найдена';
    header('Location: ' . route_path('pages/guide/dashboard.php'));
    exit();
}

$datesStmt = $pdo->prepare("SELECT * FROM excursion_dates WHERE excursion_id = ? ORDER BY available_date, available_time");
$datesStmt->execute([$excursionId]);
$dates = $datesStmt->fetchAll();

require_once base_path('includes/header.php');
?>

<div class="container">
    <h1>Редактирование экскурсии</h1>
    
    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-error" style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
            <strong>Ошибка:</strong> <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success" style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['info'])): ?>
        <div class="alert alert-info" style="background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
            <?php echo htmlspecialchars($_SESSION['info']); unset($_SESSION['info']); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="<?php echo route_path('includes/manage_excursion.php'); ?>" enctype="multipart/form-data" class="excursion-form">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="excursion_id" value="<?php echo $excursion['id']; ?>">
        <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? route_path('pages/edit_excursion.php') . '?id=' . $excursion['id']); ?>">
        <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo 20 * 1024 * 1024; ?>">

        <div class="form-group">
            <label>Название экскурсии:</label>
            <input type="text" name="title" required value="<?php echo htmlspecialchars($excursion['title']); ?>">
        </div>

        <div class="form-group">
            <label>Краткое описание:</label>
            <textarea name="short_description" required maxlength="200"><?php echo htmlspecialchars($excursion['short_description']); ?></textarea>
        </div>

        <div class="form-group">
            <label>Полное описание:</label>
            <textarea name="description" required rows="6"><?php echo htmlspecialchars($excursion['description']); ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Город:</label>
                <input type="text" name="city" required value="<?php echo htmlspecialchars($excursion['city']); ?>">
            </div>

            <div class="form-group">
                <label>Категория:</label>
                <select name="category" required>
                    <?php
                    $categories = ["История","Архитектура","Искусство","Гастрономия","Природа","Приключения","Фотосессия"];
                    foreach($categories as $category):
                    ?>
                        <option value="<?php echo $category; ?>" <?php echo $excursion['category'] === $category ? 'selected' : ''; ?>><?php echo $category; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Продолжительность (мин):</label>
                <input type="number" name="duration" min="30" required value="<?php echo $excursion['duration']; ?>">
            </div>
            <div class="form-group">
                <label>Цена (руб):</label>
                <input type="number" name="price" min="0" step="0.01" required value="<?php echo $excursion['price']; ?>">
            </div>
            <div class="form-group">
                <label>Максимум участников:</label>
                <input type="number" name="max_participants" min="1" required value="<?php echo $excursion['max_participants']; ?>">
            </div>
        </div>

        <div class="form-group">
            <label>Адрес/Место:</label>
            <input type="text" name="address" value="<?php echo htmlspecialchars($excursion['address']); ?>">
        </div>

        <div class="form-group">
            <label>Место встречи:</label>
            <textarea name="meeting_point" required><?php echo htmlspecialchars($excursion['meeting_point']); ?></textarea>
        </div>

        <div class="form-group">
            <label>Текущее изображение:</label>
            <?php if($excursion['image_url']): ?>
                <div style="margin-bottom:1rem;">
                    <img src="<?php echo asset_path($excursion['image_url']); ?>" alt="" style="max-width:250px;">
                </div>
            <?php endif; ?>
            <label>Заменить изображение:</label>
            <input type="file" name="image" accept="image/*">
            <small>Макс. 20 МБ</small>
        </div>


        <h3 style="margin-top: 32px; margin-bottom: 20px; font-size: 20px; color: var(--text-dark);">
            <i class="bi bi-calendar-event"></i> Доступные даты
        </h3>
        <?php if($dates): ?>
            <?php foreach($dates as $date): ?>
                <div class="date-item-compact">
                    <div class="date-item-row">
                        <div class="date-field">
                            <label><i class="bi bi-calendar-event"></i> Дата:</label>
                            <input type="date" name="existing_dates[<?php echo $date['id']; ?>][date]" value="<?php echo $date['available_date']; ?>" required class="date-input">
                        </div>
                        <div class="date-field">
                            <label><i class="bi bi-clock"></i> Время:</label>
                            <input type="time" name="existing_dates[<?php echo $date['id']; ?>][time]" value="<?php echo substr($date['available_time'],0,5); ?>" required class="time-input">
                        </div>
                        <div class="date-field">
                            <label><i class="bi bi-people"></i> Мест:</label>
                            <input type="number" name="existing_dates[<?php echo $date['id']; ?>][slots]" min="1" value="<?php echo $date['available_slots']; ?>" required class="slots-input">
                        </div>
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="existing_dates[<?php echo $date['id']; ?>][is_available]" <?php echo $date['is_available'] ? 'checked' : ''; ?> class="small-checkbox">
                                <span>Доступна</span>
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="remove_date_ids[]" value="<?php echo $date['id']; ?>" class="small-checkbox">
                                <span>Удалить</span>
                            </label>
                        </div>
                        <button type="button" class="btn-remove-date" title="Удалить дату">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color: var(--text-light); padding: 20px; text-align: center; background: var(--bg-light); border-radius: 8px;">Даты ещё не добавлены</p>
        <?php endif; ?>

        <div id="new-dates-container"></div>
        <button type="button" id="add-new-date" class="btn btn-secondary btn-compact">
            <i class="bi bi-plus-circle"></i> Добавить дату
        </button>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-compact">
                <i class="bi bi-check-lg"></i> Сохранить изменения
            </button>
            <a href="<?php echo route_path('pages/guide/dashboard.php'); ?>" class="btn btn-danger btn-compact">
                <i class="bi bi-x-lg"></i> Отмена
            </a>
        </div>
    </form>
</div>

<style>
.date-item-compact {
    background: var(--bg-light);
    padding: 16px;
    border-radius: var(--radius-sm);
    margin-bottom: 12px;
    border: 1px solid var(--border-color);
}

.date-item-row {
    display: flex;
    align-items: end;
    gap: 12px;
    flex-wrap: wrap;
}

.date-field {
    flex: 1;
    min-width: 150px;
}

.date-field label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.date-field label i {
    color: var(--primary-color);
    font-size: 14px;
}

.date-input,
.time-input,
.slots-input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s ease;
    font-family: inherit;
}

.date-input:focus,
.time-input:focus,
.slots-input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(255, 90, 95, 0.1);
}

.checkbox-group {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-shrink: 0;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    font-size: 13px;
    color: var(--text-dark);
    margin: 0;
    white-space: nowrap;
}

.small-checkbox {
    width: 16px;
    height: 16px;
    cursor: pointer;
    accent-color: var(--primary-color);
    flex-shrink: 0;
}

.btn-compact {
    padding: 12px 24px;
    font-size: 15px;
    height: auto;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.form-actions {
    display: flex;
    gap: 12px;
    margin-top: 32px;
    flex-wrap: wrap;
}

.form-actions .btn {
    flex: 1;
    min-width: 150px;
    justify-content: center;
}

.btn-remove-date {
    background: var(--danger-color);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 10px 14px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 42px;
    flex-shrink: 0;
}

.btn-remove-date:hover {
    background: #DC2626;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
}

.btn-remove-date i {
    font-size: 18px;
}

@media (max-width: 768px) {
    .date-item-row {
        flex-direction: column;
    }
    
    .date-field {
        width: 100%;
    }
    
    .btn-remove-date {
        width: 100%;
    }
    
    .form-actions .btn {
        width: 100%;
    }
}
</style>

<script>
document.getElementById('add-new-date').addEventListener('click', function() {
    const wrapper = document.createElement('div');
    wrapper.className = 'date-item-compact';
    wrapper.innerHTML = `
        <div class="date-item-row">
            <div class="date-field">
                <label><i class="bi bi-calendar"></i> Дата:</label>
                <input type="date" name="new_dates[]" min="<?php echo date('Y-m-d'); ?>" required class="date-input">
            </div>
            <div class="date-field">
                <label><i class="bi bi-clock"></i> Время:</label>
                <input type="time" name="new_times[]" required class="time-input">
            </div>
            <div class="date-field">
                <label><i class="bi bi-people"></i> Мест:</label>
                <input type="number" name="new_slots[]" min="1" required class="slots-input">
            </div>
            <button type="button" class="btn-remove-date" title="Удалить дату">
                <i class="bi bi-x-circle"></i>
            </button>
        </div>
    `;
    document.getElementById('new-dates-container').appendChild(wrapper);
});

document.addEventListener('click', function(e) {
    if(e.target.closest('.btn-remove-date')) {
        e.target.closest('.date-item-compact').remove();
    }
});
</script>

<?php require_once base_path('includes/footer.php'); ?>



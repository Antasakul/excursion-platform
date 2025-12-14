<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'guide') {
    header('Location: ' . route_path('pages/login.php'));
    exit();
}

require_once base_path('includes/header.php');
?>

<div class="container">
    <h1>Создание новой экскурсии</h1>
    
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
        <input type="hidden" name="action" value="create">
        <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? route_path('pages/create_excursions.php')); ?>">
        <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo 20 * 1024 * 1024; ?>">
        
        <div class="form-group">
            <label>Название экскурсии:</label>
            <input type="text" name="title" required>
        </div>
        
        <div class="form-group">
            <label>Краткое описание:</label>
            <textarea name="short_description" required maxlength="200"></textarea>
        </div>
        
        <div class="form-group">
            <label>Полное описание:</label>
            <textarea name="description" required rows="6"></textarea>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Город:</label>
                <input type="text" name="city" required>
            </div>
            
            <div class="form-group">
                <label>Категория:</label>
                <select name="category" required>
                    <option value="">Выберите категорию</option>
                    <option value="История">История</option>
                    <option value="Архитектура">Архитектура</option>
                    <option value="Искусство">Искусство</option>
                    <option value="Гастрономия">Гастрономия</option>
                    <option value="Природа">Природа</option>
                    <option value="Приключения">Приключения</option>
                    <option value="Фотосессия">Фотосессия</option>
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Продолжительность (минут):</label>
                <input type="number" name="duration" required min="30">
            </div>
            
            <div class="form-group">
                <label>Цена за человека (руб.):</label>
                <input type="number" name="price" required min="0" step="0.01">
            </div>
            
            <div class="form-group">
                <label>Максимум участников:</label>
                <input type="number" name="max_participants" required min="1">
            </div>
        </div>
        
        <div class="form-group">
            <label>Место встречи:</label>
            <textarea name="meeting_point" required></textarea>
        </div>
        
        <div class="form-group">
            <label>Изображение экскурсии (основное):</label>
            <input type="file" name="image" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
            <small style="color: #666; display: block; margin-top: 0.25rem;">
                Форматы: JPG, PNG, GIF, WEBP. Максимальный размер: 20 МБ
            </small>
        </div>
        
        
        <h3 style="margin-top: 32px; margin-bottom: 20px; font-size: 20px; color: var(--text-dark);">
            <i class="bi bi-calendar-event"></i> Доступные даты
        </h3>
        <div id="dates-container">
            <div class="date-item-compact">
                <div class="date-item-row">
                    <div class="date-field">
                        <label><i class="bi bi-calendar"></i> Дата:</label>
                        <input type="date" name="dates[]" required min="<?php echo date('Y-m-d'); ?>" class="date-input">
                    </div>
                    <div class="date-field">
                        <label><i class="bi bi-clock"></i> Время:</label>
                        <input type="time" name="times[]" required class="time-input">
                    </div>
                    <div class="date-field">
                        <label><i class="bi bi-people"></i> Мест:</label>
                        <input type="number" name="slots[]" required min="1" class="slots-input">
                    </div>
                    <button type="button" class="btn-remove-date" title="Удалить дату">
                        <i class="bi bi-x-circle"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <button type="button" id="add-date" class="btn btn-secondary btn-compact">
            <i class="bi bi-plus-circle"></i> Добавить дату
        </button>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-compact">
                <i class="bi bi-check-lg"></i> Создать экскурсию
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

.checkbox-field {
    display: flex;
    align-items: center;
    flex-shrink: 0;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-size: 14px;
    color: var(--text-dark);
    margin: 0;
}

.small-checkbox {
    width: 18px;
    height: 18px;
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
document.getElementById('add-date').addEventListener('click', function() {
    const container = document.getElementById('dates-container');
    const newDate = document.createElement('div');
    newDate.className = 'date-item-compact';
    newDate.innerHTML = `
        <div class="date-item-row">
            <div class="date-field">
                <label><i class="bi bi-calendar"></i> Дата:</label>
                <input type="date" name="dates[]" required min="<?php echo date('Y-m-d'); ?>" class="date-input">
            </div>
            <div class="date-field">
                <label><i class="bi bi-clock"></i> Время:</label>
                <input type="time" name="times[]" required class="time-input">
            </div>
            <div class="date-field">
                <label><i class="bi bi-people"></i> Мест:</label>
                <input type="number" name="slots[]" required min="1" class="slots-input">
            </div>
            <button type="button" class="btn-remove-date" title="Удалить дату">
                <i class="bi bi-x-circle"></i>
            </button>
        </div>
    `;
    container.appendChild(newDate);
});

document.addEventListener('click', function(e) {
    if(e.target.closest('.btn-remove-date')) {
        e.target.closest('.date-item-compact').remove();
    }
});
</script>

<?php require_once base_path('includes/footer.php'); ?>
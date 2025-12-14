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
        
        <div class="form-group">
            <label>Дополнительные изображения (галерея):</label>
            <input type="file" name="additional_images[]" multiple accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
            <small style="color: #666; display: block; margin-top: 0.25rem;">
                Можно выбрать несколько файлов. Каждый файл до 20 МБ. Форматы: JPG, PNG, GIF, WEBP
            </small>
            <small style="color: #e74c3c; display: block; margin-top: 0.25rem;">
                <i class="bi bi-exclamation-triangle"></i> Если общий размер всех файлов превышает лимит сервера, загрузка не произойдет. 
                Рекомендуется загружать изображения по одному или уменьшить их размер.
            </small>
        </div>
        
        <h3>Доступные даты</h3>
        <div id="dates-container">
            <div class="date-item form-row">
                <div class="form-group">
                    <label>Дата:</label>
                    <input type="date" name="dates[]" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label>Время:</label>
                    <input type="time" name="times[]" required>
                </div>
                <div class="form-group">
                    <label>Доступных мест:</label>
                    <input type="number" name="slots[]" required min="1">
                </div>
                <button type="button" class="btn btn-danger remove-date">Удалить</button>
            </div>
        </div>
        
        <button type="button" id="add-date" class="btn btn-secondary">Добавить дату</button>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Создать экскурсию</button>
            <a href="<?php echo route_path('pages/guide/dashboard.php'); ?>" class="btn btn-danger">Отмена</a>
        </div>
    </form>
</div>

<script>
document.getElementById('add-date').addEventListener('click', function() {
    const container = document.getElementById('dates-container');
    const newDate = document.createElement('div');
    newDate.className = 'date-item form-row';
    newDate.innerHTML = `
        <div class="form-group">
            <label>Дата:</label>
            <input type="date" name="dates[]" required min="<?php echo date('Y-m-d'); ?>">
        </div>
        <div class="form-group">
            <label>Время:</label>
            <input type="time" name="times[]" required>
        </div>
        <div class="form-group">
            <label>Доступных мест:</label>
            <input type="number" name="slots[]" required min="1">
        </div>
        <button type="button" class="btn btn-danger remove-date">Удалить</button>
    `;
    container.appendChild(newDate);
});

document.addEventListener('click', function(e) {
    if(e.target.classList.contains('remove-date')) {
        e.target.closest('.date-item').remove();
    }
});
</script>

<?php require_once base_path('includes/footer.php'); ?>
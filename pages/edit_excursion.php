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

$imagesStmt = $pdo->prepare("SELECT * FROM excursion_images WHERE excursion_id = ?");
$imagesStmt->execute([$excursionId]);
$galleryImages = $imagesStmt->fetchAll();

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

        <div class="form-group">
            <label>Галерея:</label>
            <?php if($galleryImages): ?>
                <div class="gallery-list">
                    <?php foreach($galleryImages as $image): ?>
                        <div class="gallery-item">
                            <img src="<?php echo asset_path($image['image_url']); ?>" alt="" style="max-width:150px;">
                            <label>
                                <input type="checkbox" name="remove_image_ids[]" value="<?php echo $image['id']; ?>">
                                Удалить
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>Нет дополнительных изображений</p>
            <?php endif; ?>
            <label>Добавить новые изображения:</label>
            <input type="file" name="additional_images[]" multiple accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
            <small style="color: #666; display: block; margin-top: 0.25rem;">
                Можно выбрать несколько файлов. Каждый файл до 20 МБ. Форматы: JPG, PNG, GIF, WEBP
            </small>
            <small style="color: #e74c3c; display: block; margin-top: 0.25rem;">
                ⚠️ Если общий размер всех файлов превышает лимит сервера, загрузка не произойдет.
            </small>
        </div>

        <h3>Доступные даты</h3>
        <?php if($dates): ?>
            <?php foreach($dates as $date): ?>
                <div class="date-item form-row">
                    <div class="form-group">
                        <label>Дата:</label>
                        <input type="date" name="existing_dates[<?php echo $date['id']; ?>][date]" value="<?php echo $date['available_date']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Время:</label>
                        <input type="time" name="existing_dates[<?php echo $date['id']; ?>][time]" value="<?php echo substr($date['available_time'],0,5); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Мест:</label>
                        <input type="number" name="existing_dates[<?php echo $date['id']; ?>][slots]" min="1" value="<?php echo $date['available_slots']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Доступна:</label>
                        <input type="checkbox" name="existing_dates[<?php echo $date['id']; ?>][is_available]" <?php echo $date['is_available'] ? 'checked' : ''; ?>>
                    </div>
                    <div class="form-group">
                        <label>Удалить:</label>
                        <input type="checkbox" name="remove_date_ids[]" value="<?php echo $date['id']; ?>">
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Даты ещё не добавлены</p>
        <?php endif; ?>

        <div id="new-dates-container"></div>
        <button type="button" id="add-new-date" class="btn btn-secondary">Добавить дату</button>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Сохранить изменения</button>
            <a href="<?php echo route_path('pages/guide/dashboard.php'); ?>" class="btn btn-danger">Отмена</a>
        </div>
    </form>
</div>

<script>
document.getElementById('add-new-date').addEventListener('click', function() {
    const wrapper = document.createElement('div');
    wrapper.className = 'date-item form-row';
    wrapper.innerHTML = `
        <div class="form-group">
            <label>Дата:</label>
            <input type="date" name="new_dates[]" min="<?php echo date('Y-m-d'); ?>" required>
        </div>
        <div class="form-group">
            <label>Время:</label>
            <input type="time" name="new_times[]" required>
        </div>
        <div class="form-group">
            <label>Мест:</label>
            <input type="number" name="new_slots[]" min="1" required>
        </div>
        <button type="button" class="btn btn-danger remove-date">Удалить</button>
    `;
    document.getElementById('new-dates-container').appendChild(wrapper);
});

document.addEventListener('click', function(event) {
    if(event.target.classList.contains('remove-date')) {
        event.target.closest('.date-item').remove();
    }
});
</script>

<?php require_once base_path('includes/footer.php'); ?>



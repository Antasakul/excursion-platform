<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

if(!isset($_GET['excursion_id'])) {
    header('Location: ' . route_path('pages/excursions.php'));
    exit();
}

require_once base_path('includes/header.php');

$excursion_id = $_GET['excursion_id'];

// Получаем информацию об экскурсии
$stmt = $pdo->prepare("
    SELECT e.*, u.full_name as guide_name, u.phone as guide_phone, u.avatar_url as guide_avatar 
    FROM excursions e 
    JOIN users u ON e.guide_id = u.id 
    WHERE e.id = ? AND e.is_active = TRUE
");
$stmt->execute([$excursion_id]);
$excursion = $stmt->fetch();

if(!$excursion) {
    die('Экскурсия не найдена');
}

// Получаем доступные даты
$dates_stmt = $pdo->prepare("
    SELECT * FROM excursion_dates 
    WHERE excursion_id = ? AND is_available = TRUE 
    AND available_slots > 0 
    AND available_date >= CURDATE()
    ORDER BY available_date, available_time
");
$dates_stmt->execute([$excursion_id]);
$available_dates = $dates_stmt->fetchAll();

// Получаем отзывы
$reviews_stmt = $pdo->prepare("
    SELECT r.*, u.full_name 
    FROM reviews r
    JOIN orders o ON r.order_id = o.id
    JOIN users u ON o.customer_id = u.id
    JOIN excursion_dates ed ON o.excursion_date_id = ed.id
    WHERE ed.excursion_id = ?
    ORDER BY r.created_at DESC
");
$reviews_stmt->execute([$excursion_id]);
$reviews = $reviews_stmt->fetchAll();

// Считаем средний рейтинг
$avg_rating = 0;
if(count($reviews) > 0) {
    $total_rating = 0;
    foreach($reviews as $review) {
        $total_rating += $review['rating'];
    }
    $avg_rating = round($total_rating / count($reviews), 1);
}
?>

<div class="container">
    <div class="booking-page">
        <div class="excursion-details">
            <h1><?php echo htmlspecialchars($excursion['title']); ?></h1>
            
            <div class="excursion-meta">
                <div class="rating">
                    <?php for($i = 1; $i <= 5; $i++): ?>
                        <span class="star <?php echo $i <= $avg_rating ? 'filled' : ''; ?>"><i class="bi bi-star-fill"></i></span>
                    <?php endfor; ?>
                    <span>(<?php echo $avg_rating; ?>)</span>
                </div>
                <p class="guide" style="display: flex; align-items: center; gap: 8px;">
                    <?php if($excursion['guide_avatar']): ?>
                        <img src="<?php echo asset_path($excursion['guide_avatar']); ?>" alt="Аватар гида" style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover;">
                    <?php endif; ?>
                    <i class="bi bi-person"></i> Гид: <?php echo htmlspecialchars($excursion['guide_name']); ?>
                </p>
                <p class="city"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($excursion['city']); ?></p>
            </div>

            <?php if($excursion['image_url']): ?>
                <img src="<?php echo asset_path($excursion['image_url']); ?>" alt="<?php echo htmlspecialchars($excursion['title']); ?>" class="excursion-main-image">
            <?php endif; ?>

            <div class="excursion-info">
                <h3>Описание</h3>
                <p><?php echo nl2br(htmlspecialchars($excursion['description'])); ?></p>
                
                <div class="details-grid">
                    <div class="detail-item">
                        <strong><i class="bi bi-clock"></i> Продолжительность:</strong>
                        <span><?php echo $excursion['duration']; ?> минут</span>
                    </div>
                    <div class="detail-item">
                        <strong><i class="bi bi-people"></i> Максимум участников:</strong>
                        <span><?php echo $excursion['max_participants']; ?> человек</span>
                    </div>
                    <div class="detail-item">
                        <strong><i class="bi bi-currency-exchange"></i> Цена за человека:</strong>
                        <span class="price"><?php echo $excursion['price']; ?> руб.</span>
                    </div>
                    <div class="detail-item">
                        <strong><i class="bi bi-geo-alt"></i> Место встречи:</strong>
                        <span><?php echo htmlspecialchars($excursion['meeting_point']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Отзывы -->
            <div class="reviews-section">
                <h3>Отзывы (<?php echo count($reviews); ?>)</h3>
                <?php if(count($reviews) > 0): ?>
                    <?php foreach($reviews as $review): ?>
                    <div class="review-item">
                        <div class="review-header">
                            <strong><?php echo htmlspecialchars($review['full_name']); ?></strong>
                            <div class="review-rating">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <span class="star <?php echo $i <= $review['rating'] ? 'filled' : ''; ?>"><i class="bi bi-star-fill"></i></span>
                                <?php endfor; ?>
                            </div>
                            <span class="review-date"><?php echo date('d.m.Y', strtotime($review['created_at'])); ?></span>
                        </div>
                        <p><?php echo htmlspecialchars($review['comment']); ?></p>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Пока нет отзывов</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="booking-form-container">
            <div class="booking-form">
                <h2>Бронирование</h2>
                
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if($_SESSION['user_type'] !== 'customer'): ?>
                        <div class="alert alert-error" style="margin-bottom: 24px; padding: 20px; border-radius: 12px; border-left: 4px solid #EF4444; background: #FFF5F5; color: #C53030; display: flex; align-items: start; gap: 12px;">
                            <i class="bi bi-exclamation-triangle-fill" style="font-size: 24px; flex-shrink: 0;"></i>
                            <div>
                                <strong style="display: block; margin-bottom: 4px; font-size: 16px;">Бронирование недоступно!</strong>
                                <p style="margin: 0; font-size: 14px; line-height: 1.5;">Данный функционал доступен только для клиентов. Администраторы и гиды не могут бронировать экскурсии.</p>
                            </div>
                        </div>
                    <?php elseif(count($available_dates) > 0): ?>
                        <form method="POST" action="<?php echo route_path('includes/process_booking.php'); ?>">
                            <input type="hidden" name="excursion_id" value="<?php echo $excursion_id; ?>">
                            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars(route_path('pages/booking.php') . '?excursion_id=' . urlencode($excursion_id)); ?>">
                            
                            <div class="form-group">
                                <label>Выберите дату и время:</label>
                                <select name="excursion_date_id" required id="dateSelect">
                                    <option value="">Выберите дату</option>
                                    <?php foreach($available_dates as $date): ?>
                                        <option value="<?php echo $date['id']; ?>" 
                                                data-slots="<?php echo $date['available_slots']; ?>"
                                                data-price="<?php echo $excursion['price']; ?>">
                                            <?php echo date('d.m.Y', strtotime($date['available_date'])); ?> 
                                            в <?php echo $date['available_time']; ?> 
                                            (доступно мест: <?php echo $date['available_slots']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Количество участников:</label>
                                <input type="number" name="participants_count" id="participantsCount" 
                                       min="1" max="<?php echo $excursion['max_participants']; ?>" 
                                       value="1" required>
                                <small>Максимум: <?php echo $excursion['max_participants']; ?> человек</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Промокод:</label>
                                <input type="text" name="promo_code" id="promoCode" placeholder="Введите промокод">
                                <button type="button" id="applyPromo" class="btn btn-secondary" style="margin-top: 8px; width: 100%;">Применить</button>
                            </div>
                            
                            <div class="form-group">
                                <label>Специальные пожелания (необязательно):</label>
                                <textarea name="special_requests" rows="3" placeholder="Укажите любые особые пожелания или требования к экскурсии..."></textarea>
                                <small style="color: #666;">Например, особые потребности, предпочтения по маршруту и т.д.</small>
                            </div>
                            
                            <div class="price-summary">
                                <h3>Итого к оплате:</h3>
                                <div class="price-details">
                                    <p>Стоимость: <span id="basePrice">0</span> <span class="currency">руб.</span></p>
                                    <p>Скидка: <span id="discountAmount">0</span> <span class="currency">руб.</span></p>
                                    <p class="total-price">Всего: <span id="totalPrice">0</span> <span class="currency">руб.</span></p>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-secondary btn-large">Забронировать</button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-error">
                            На данный момент нет доступных дат для бронирования
                        </div>
                    <?php endif; ?>
                    
                    <!-- Кнопка добавления в избранное -->
                    <?php if(isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'customer'): ?>
                        <?php
                        $is_favorite = false;
                        $fav_stmt = $pdo->prepare("SELECT * FROM favorites WHERE user_id = ? AND excursion_id = ?");
                        $fav_stmt->execute([$_SESSION['user_id'], $excursion_id]);
                        $is_favorite = $fav_stmt->fetch();
                        ?>
                        <div class="favorite-section">
                            <form method="POST" action="<?php echo route_path('includes/manage_favorite.php'); ?>" style="display: inline;">
                                <input type="hidden" name="excursion_id" value="<?php echo $excursion_id; ?>">
                                <input type="hidden" name="action" value="<?php echo $is_favorite ? 'remove' : 'add'; ?>">
                                <button type="submit" class="btn <?php echo $is_favorite ? 'btn-danger' : 'btn-primary'; ?>">
                                    <i class="bi <?php echo $is_favorite ? 'bi-heart-fill' : 'bi-heart'; ?>"></i> <?php echo $is_favorite ? 'Удалить из избранного' : 'Добавить в избранное'; ?>
                                </button>
                            </form>
                        </div>
                    <?php elseif(isset($_SESSION['user_id'])): ?>
                        <div class="alert alert-info" style="margin-top: 24px; padding: 20px; border-radius: 12px; border-left: 4px solid #3B82F6; background: #EFF6FF; color: #1E40AF; display: flex; align-items: start; gap: 12px;">
                            <i class="bi bi-info-circle-fill" style="font-size: 24px; flex-shrink: 0;"></i>
                            <div>
                                <strong style="display: block; margin-bottom: 4px; font-size: 16px;">Добавление в избранное недоступно!</strong>
                                <p style="margin: 0; font-size: 14px; line-height: 1.5;">Данный функционал доступен только для клиентов. Администраторы и гиды не могут добавлять экскурсии в избранное.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="alert alert-info">
                        <p>Для бронирования необходимо <a href="<?php echo route_path('pages/login.php'); ?>">войти в систему</a></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dateSelect = document.getElementById('dateSelect');
    const participantsCount = document.getElementById('participantsCount');
    const basePriceElement = document.getElementById('basePrice');
    const discountAmountElement = document.getElementById('discountAmount');
    const totalPriceElement = document.getElementById('totalPrice');
    const promoCodeInput = document.getElementById('promoCode');
    
    let currentDiscount = 0;
    let currentPrice = 0;
    
    function calculatePrice() {
        const selectedDate = dateSelect.options[dateSelect.selectedIndex];
        if(selectedDate && selectedDate.value) {
            const pricePerPerson = parseFloat(selectedDate.getAttribute('data-price'));
            const participants = parseInt(participantsCount.value);
            const maxSlots = parseInt(selectedDate.getAttribute('data-slots'));
            
            // Ограничиваем количество участников доступными слотами
            if(participants > maxSlots) {
                participantsCount.value = maxSlots;
                participants = maxSlots;
            }
            
            currentPrice = pricePerPerson * participants;
            const discount = currentPrice * currentDiscount;
            const total = currentPrice - discount;
            
            basePriceElement.textContent = currentPrice.toFixed(2);
            discountAmountElement.textContent = discount.toFixed(2);
            totalPriceElement.textContent = total.toFixed(2);
        }
    }
    
    dateSelect.addEventListener('change', calculatePrice);
    participantsCount.addEventListener('input', calculatePrice);
    
    // Применение промокода
    document.getElementById('applyPromo').addEventListener('click', function() {
        const promoCode = promoCodeInput.value.trim();
        if(promoCode) {
            fetch('<?php echo route_path('includes/apply_promo.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'promo_code=' + encodeURIComponent(promoCode) + '&total_price=' + currentPrice
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    currentDiscount = data.discount_percent / 100;
                    calculatePrice();
                    alert('Промокод применен! Скидка: ' + data.discount_percent + '%');
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ошибка при применении промокода');
            });
        }
    });
    
    // Инициализация расчета цены
    calculatePrice();
});
</script>

<?php require_once base_path('includes/footer.php'); ?>
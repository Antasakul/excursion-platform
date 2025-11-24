<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Получаем избранные экскурсии текущего пользователя
$user_favorites = [];
if(isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT excursion_id FROM favorites WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_favorites = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Получаем экскурсии
$stmt = $pdo->query("
    SELECT e.*, u.full_name as guide_name 
    FROM excursions e 
    JOIN users u ON e.guide_id = u.id 
    WHERE e.is_active = TRUE 
    ORDER BY e.created_at DESC
");
$excursions = $stmt->fetchAll();

require_once base_path('includes/header.php');
?>

<div class="container">
    <h1>Все экскурсии</h1>
    
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
    
    <div class="filters-section">
        <div class="filters-row">
            <input type="text" id="searchInput" placeholder="Поиск по названию или описанию...">
            
            <select id="cityFilter">
                <option value="">Все города</option>
                <?php
                $cities = $pdo->query("SELECT DISTINCT city FROM excursions WHERE is_active = TRUE ORDER BY city")->fetchAll();
                foreach($cities as $city): ?>
                    <option value="<?php echo htmlspecialchars($city['city']); ?>"><?php echo htmlspecialchars($city['city']); ?></option>
                <?php endforeach; ?>
            </select>
            
            <select id="categoryFilter">
                <option value="">Все категории</option>
                <?php
                $categories = $pdo->query("SELECT DISTINCT category FROM excursions WHERE is_active = TRUE ORDER BY category")->fetchAll();
                foreach($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat['category']); ?>"><?php echo htmlspecialchars($cat['category']); ?></option>
                <?php endforeach; ?>
            </select>
            
            <select id="sortBy">
                <option value="date_desc">Сначала новые</option>
                <option value="date_asc">Сначала старые</option>
                <option value="price_asc">Цена: по возрастанию</option>
                <option value="price_desc">Цена: по убыванию</option>
                <option value="title_asc">По алфавиту (А-Я)</option>
                <option value="title_desc">По алфавиту (Я-А)</option>
                <option value="duration_asc">Длительность: короткие</option>
                <option value="duration_desc">Длительность: длинные</option>
            </select>
        </div>
        
        <div class="filters-row">
            <label>
                Цена от: <input type="number" id="priceMin" min="0" placeholder="0" style="width:100px;">
            </label>
            <label>
                до: <input type="number" id="priceMax" min="0" placeholder="∞" style="width:100px;">
            </label>
            
            <label>
                Длительность от: <input type="number" id="durationMin" min="0" placeholder="0" style="width:100px;"> мин
            </label>
            <label>
                до: <input type="number" id="durationMax" min="0" placeholder="∞" style="width:100px;"> мин
            </label>
            
            <button id="resetFilters" class="btn btn-secondary">Сбросить фильтры</button>
        </div>
    </div>

    <div class="excursions-grid" id="excursionsContainer">
        <?php foreach($excursions as $excursion): 
            $isFavorite = in_array($excursion['id'], $user_favorites);
        ?>
        <div class="excursion-card" 
             data-id="<?php echo $excursion['id']; ?>"
             data-city="<?php echo htmlspecialchars($excursion['city']); ?>" 
             data-category="<?php echo htmlspecialchars($excursion['category']); ?>"
             data-price="<?php echo $excursion['price']; ?>"
             data-duration="<?php echo $excursion['duration']; ?>"
             data-title="<?php echo htmlspecialchars($excursion['title']); ?>"
             data-description="<?php echo htmlspecialchars($excursion['short_description']); ?>"
             data-created="<?php echo strtotime($excursion['created_at']); ?>">
            
            <?php if(isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'customer'): ?>
                <form method="POST" action="<?php echo route_path('includes/manage_favorite.php'); ?>" class="favorite-form">
                    <input type="hidden" name="excursion_id" value="<?php echo $excursion['id']; ?>">
                    <input type="hidden" name="action" value="<?php echo $isFavorite ? 'remove' : 'add'; ?>">
                    <button type="submit" class="btn-favorite <?php echo $isFavorite ? 'active' : ''; ?>" title="<?php echo $isFavorite ? 'Убрать из избранного' : 'Добавить в избранное'; ?>">
                        <?php echo $isFavorite ? '❤️' : '🤍'; ?>
                    </button>
                </form>
            <?php endif; ?>
            
            <?php if($excursion['image_url']): ?>
                <img src="<?php echo asset_path($excursion['image_url']); ?>" alt="<?php echo htmlspecialchars($excursion['title']); ?>">
            <?php endif; ?>
            
            <div class="card-content">
                <h3><?php echo htmlspecialchars($excursion['title']); ?></h3>
                <p class="description"><?php echo htmlspecialchars($excursion['short_description']); ?></p>
                <p class="city">📍 <?php echo htmlspecialchars($excursion['city']); ?></p>
                <p class="category">🏷️ <?php echo htmlspecialchars($excursion['category']); ?></p>
                <p class="guide">👤 Гид: <?php echo htmlspecialchars($excursion['guide_name']); ?></p>
                <p class="duration">⏱️ <?php echo $excursion['duration']; ?> мин.</p>
                <p class="price">💰 <?php echo number_format($excursion['price'], 2); ?> руб./чел.</p>
                <a href="<?php echo route_path('pages/booking.php'); ?>?excursion_id=<?php echo $excursion['id']; ?>" class="btn btn-primary">Подробнее и забронировать</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div id="noResults" style="display:none; text-align:center; padding:2rem;">
        <p>По вашему запросу ничего не найдено. Попробуйте изменить фильтры.</p>
    </div>
</div>

<style>
.filters-section {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
}

.filters-row {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
    align-items: center;
}

.filters-row:last-child {
    margin-bottom: 0;
}

.filters-row input[type="text"],
.filters-row select {
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    flex: 1;
    min-width: 150px;
}

.filters-row label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    white-space: nowrap;
}

.btn-favorite {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(255, 255, 255, 0.9);
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    font-size: 1.5rem;
    cursor: pointer;
    transition: transform 0.2s;
    z-index: 10;
}

.btn-favorite:hover {
    transform: scale(1.1);
}

.excursion-card {
    position: relative;
}

.alert {
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
}

.alert-success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.alert-error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}
</style>

<script>
// Все элементы управления
const searchInput = document.getElementById('searchInput');
const cityFilter = document.getElementById('cityFilter');
const categoryFilter = document.getElementById('categoryFilter');
const sortBy = document.getElementById('sortBy');
const priceMin = document.getElementById('priceMin');
const priceMax = document.getElementById('priceMax');
const durationMin = document.getElementById('durationMin');
const durationMax = document.getElementById('durationMax');
const resetBtn = document.getElementById('resetFilters');
const container = document.getElementById('excursionsContainer');
const noResults = document.getElementById('noResults');

// Привязываем обработчики
searchInput.addEventListener('input', filterAndSort);
cityFilter.addEventListener('change', filterAndSort);
categoryFilter.addEventListener('change', filterAndSort);
sortBy.addEventListener('change', filterAndSort);
priceMin.addEventListener('input', filterAndSort);
priceMax.addEventListener('input', filterAndSort);
durationMin.addEventListener('input', filterAndSort);
durationMax.addEventListener('input', filterAndSort);

resetBtn.addEventListener('click', () => {
    searchInput.value = '';
    cityFilter.value = '';
    categoryFilter.value = '';
    sortBy.value = 'date_desc';
    priceMin.value = '';
    priceMax.value = '';
    durationMin.value = '';
    durationMax.value = '';
    filterAndSort();
});

function filterAndSort() {
    const cards = Array.from(document.querySelectorAll('.excursion-card'));
    const search = searchInput.value.toLowerCase();
    const city = cityFilter.value;
    const category = categoryFilter.value;
    const minPrice = parseFloat(priceMin.value) || 0;
    const maxPrice = parseFloat(priceMax.value) || Infinity;
    const minDuration = parseInt(durationMin.value) || 0;
    const maxDuration = parseInt(durationMax.value) || Infinity;
    
    let visibleCount = 0;
    
    // Фильтрация
    cards.forEach(card => {
        const title = card.dataset.title.toLowerCase();
        const description = card.dataset.description.toLowerCase();
        const cardCity = card.dataset.city;
        const cardCategory = card.dataset.category;
        const price = parseFloat(card.dataset.price);
        const duration = parseInt(card.dataset.duration);
        
        const matchesSearch = title.includes(search) || description.includes(search);
        const matchesCity = !city || cardCity === city;
        const matchesCategory = !category || cardCategory === category;
        const matchesPrice = price >= minPrice && price <= maxPrice;
        const matchesDuration = duration >= minDuration && duration <= maxDuration;
        
        if(matchesSearch && matchesCity && matchesCategory && matchesPrice && matchesDuration) {
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    // Сортировка видимых карточек
    const visibleCards = cards.filter(card => card.style.display !== 'none');
    const sort = sortBy.value;
    
    visibleCards.sort((a, b) => {
        switch(sort) {
            case 'price_asc':
                return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
            case 'price_desc':
                return parseFloat(b.dataset.price) - parseFloat(a.dataset.price);
            case 'title_asc':
                return a.dataset.title.localeCompare(b.dataset.title, 'ru');
            case 'title_desc':
                return b.dataset.title.localeCompare(a.dataset.title, 'ru');
            case 'duration_asc':
                return parseInt(a.dataset.duration) - parseInt(b.dataset.duration);
            case 'duration_desc':
                return parseInt(b.dataset.duration) - parseInt(a.dataset.duration);
            case 'date_asc':
                return parseInt(a.dataset.created) - parseInt(b.dataset.created);
            case 'date_desc':
            default:
                return parseInt(b.dataset.created) - parseInt(a.dataset.created);
        }
    });
    
    // Перестраиваем DOM
    visibleCards.forEach(card => container.appendChild(card));
    
    // Показываем/скрываем сообщение "ничего не найдено"
    noResults.style.display = visibleCount === 0 ? 'block' : 'none';
}
</script>

<?php require_once base_path('includes/footer.php'); ?>


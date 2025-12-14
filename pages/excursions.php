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
            SELECT e.*, u.full_name as guide_name, u.avatar_url as guide_avatar 
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
    
    <div class="filters-toggle-wrapper">
        <button class="filters-toggle-btn" id="filtersToggleBtn" onclick="toggleFilters()">
            <i class="bi bi-funnel"></i> Фильтры экскурсий
            <i class="bi bi-chevron-down" id="filtersToggleIcon"></i>
        </button>
    </div>
    
    <div class="filters-card" id="filtersCard" style="display: none;">
        <div class="filters-header">
            <h2><i class="bi bi-funnel"></i> Фильтры экскурсий</h2>
            <p>Найдите идеальную экскурсию с помощью нашей системы фильтрации</p>
            <button class="filters-close-btn" onclick="closeFilters()" title="Закрыть">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        
        <div class="filters-content">
            <!-- Поиск по названию -->
            <div class="filter-group">
                <label class="filter-label">
                    <i class="bi bi-search"></i> Поиск экскурсий
                </label>
                <div class="search-input-wrapper">
                    <i class="bi bi-search"></i>
                    <input type="text" id="searchInput" placeholder="Поиск по названию или описанию..." class="filter-input">
                </div>
            </div>
            
            <!-- Город и Категория -->
            <div class="filter-row">
                <div class="filter-group">
                    <label class="filter-label">
                        <i class="bi bi-geo-alt"></i> Город
                    </label>
                    <select id="cityFilter" class="filter-select">
                        <option value="">Все города</option>
                        <?php
                        $cities = $pdo->query("SELECT DISTINCT city FROM excursions WHERE is_active = TRUE ORDER BY city")->fetchAll();
                        foreach($cities as $city): ?>
                            <option value="<?php echo htmlspecialchars($city['city']); ?>"><?php echo htmlspecialchars($city['city']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">
                        <i class="bi bi-tag"></i> Категория
                    </label>
                    <select id="categoryFilter" class="filter-select">
                        <option value="">Все категории</option>
                        <?php
                        $categories = $pdo->query("SELECT DISTINCT category FROM excursions WHERE is_active = TRUE ORDER BY category")->fetchAll();
                        foreach($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['category']); ?>"><?php echo htmlspecialchars($cat['category']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Цена -->
            <div class="filter-group">
                <label class="filter-label">
                    <i class="bi bi-currency-exchange"></i> Диапазон цены
                </label>
                <div class="price-range">
                    <div class="range-input-wrapper">
                        <input type="number" id="priceMin" min="0" placeholder="От" class="filter-input range-input">
                        <span class="range-separator">—</span>
                        <input type="number" id="priceMax" min="0" placeholder="До" class="filter-input range-input">
                        <span class="range-currency">руб.</span>
                    </div>
                </div>
            </div>
            
            <!-- Длительность -->
            <div class="filter-group">
                <label class="filter-label">
                    <i class="bi bi-clock"></i> Длительность экскурсии
                </label>
                <div class="duration-range">
                    <div class="range-input-wrapper">
                        <input type="number" id="durationMin" min="0" placeholder="От" class="filter-input range-input">
                        <span class="range-separator">—</span>
                        <input type="number" id="durationMax" min="0" placeholder="До" class="filter-input range-input">
                        <span class="range-currency">мин.</span>
                    </div>
                </div>
            </div>
            
            <!-- Сортировка -->
            <div class="filter-group">
                <label class="filter-label">
                    <i class="bi bi-sort-down"></i> Сортировка
                </label>
                <select id="sortBy" class="filter-select">
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
            
            <!-- Кнопки действий -->
            <div class="filters-actions">
                <button id="resetFilters" class="btn btn-primary">
                    <i class="bi bi-arrow-counterclockwise"></i> Сбросить фильтры
                </button>
                 <button id="applyFilters" class="btn btn-secondary">
                    <i class="bi bi-check-circle"></i> Применить фильтры
                </button>
            </div>
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
                        <i class="bi <?php echo $isFavorite ? 'bi-heart-fill' : 'bi-heart'; ?>"></i>
                    </button>
                </form>
            <?php endif; ?>
            
            <?php if($excursion['image_url']): ?>
                <img src="<?php echo asset_path($excursion['image_url']); ?>" alt="<?php echo htmlspecialchars($excursion['title']); ?>">
            <?php endif; ?>
            
            <div class="card-content">
                <h3><?php echo htmlspecialchars($excursion['title']); ?></h3>
                <p class="description"><?php echo htmlspecialchars($excursion['short_description']); ?></p>
                <p class="city"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($excursion['city']); ?></p>
                <p class="category"><i class="bi bi-tag"></i> <?php echo htmlspecialchars($excursion['category']); ?></p>
                <p class="guide" style="display: flex; align-items: center; gap: 8px;">
                    <?php if($excursion['guide_avatar']): ?>
                        <img src="<?php echo asset_path($excursion['guide_avatar']); ?>" alt="Аватар гида" style="width: 20px; height: 20px; border-radius: 50%; object-fit: cover;">
                    <?php endif; ?>
                    <i class="bi bi-person"></i> Гид: <?php echo htmlspecialchars($excursion['guide_name']); ?>
                </p>
                <p class="duration"><i class="bi bi-clock"></i> <?php echo $excursion['duration']; ?> мин.</p>
                <p class="price"><i class="bi bi-currency-exchange"></i> <?php echo number_format($excursion['price'], 2); ?> руб./чел.</p>
                <a href="<?php echo route_path('pages/booking.php'); ?>?excursion_id=<?php echo $excursion['id']; ?>" class="btn btn-secondary">Подробнее и забронировать</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div id="noResults" style="display:none; text-align:center; padding:2rem;">
        <p>По вашему запросу ничего не найдено. Попробуйте изменить фильтры.</p>
    </div>
</div>

<style>
.filters-toggle-wrapper {
    margin-bottom: 24px;
}

.filters-toggle-btn {
    width: 100%;
    padding: 16px 24px;
    background: var(--bg-white);
    border: 2px solid var(--border-color);
    border-radius: 50px;
    font-size: 16px;
    font-weight: 600;
    color: var(--text-dark);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    transition: all 0.2s ease;
    box-shadow: var(--shadow-sm);
}

.filters-toggle-btn:hover {
    border-color: var(--primary-color);
    box-shadow: var(--shadow-md);
    transform: translateY(-1px);
}

.filters-toggle-btn i:first-child {
    color: var(--primary-color);
    font-size: 18px;
}

.filters-toggle-btn i:last-child {
    color: var(--text-light);
    font-size: 14px;
    transition: transform 0.3s ease;
}

.filters-toggle-btn.active i:last-child {
    transform: rotate(180deg);
}

.filters-card {
    background: var(--bg-white);
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-md);
    border: 1px solid var(--border-color);
    margin-bottom: 32px;
    overflow: hidden;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.filters-card.hidden {
    display: none !important;
}

.filters-header {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
    color: white;
    padding: 24px 32px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    position: relative;
}

.filters-close-btn {
    position: absolute;
    top: 20px;
    right: 20px;
    background: rgba(255, 255, 255, 0.2);
    border: none;
    border-radius: 50%;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    color: white;
    font-size: 18px;
}

.filters-close-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
}

.filters-header h2 {
    margin: 0 0 8px 0;
    font-size: 24px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 12px;
    color: white;
}

.filters-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 14px;
    color: white;
}

.filters-content {
    padding: 32px;
}

.filter-group {
    margin-bottom: 24px;
}

.filter-group:last-of-type {
    margin-bottom: 0;
}

.filter-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    font-size: 14px;
    color: var(--text-dark);
    margin-bottom: 10px;
}

.filter-label i {
    color: var(--primary-color);
    font-size: 16px;
}

.search-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.search-input-wrapper i {
    position: absolute;
    left: 16px;
    color: var(--text-light);
    font-size: 18px;
    pointer-events: none;
}

.filter-input {
    width: 100%;
    padding: 14px 24px 14px 48px;
    border: 1px solid var(--border-color);
    border-radius: 50px;
    font-size: 16px;
    font-weight: 500;
    transition: all 0.2s ease;
    height: 48px;
    box-sizing: border-box;
}

.filter-input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(255, 90, 95, 0.1);
}

.filter-select {
    width: 100%;
    padding: 14px 24px;
    border: 1px solid var(--border-color);
    border-radius: 50px;
    font-size: 16px;
    font-weight: 500;
    background: var(--bg-white);
    transition: all 0.2s ease;
    height: 48px;
    box-sizing: border-box;
    cursor: pointer;
}

.filter-select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(255, 90, 95, 0.1);
}

.filter-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.price-range,
.duration-range {
    width: 100%;
}

.range-input-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
}

.range-input {
    flex: 1;
    padding: 14px 20px;
    border: 1px solid var(--border-color);
    border-radius: 50px;
    font-size: 16px;
    font-weight: 500;
    transition: all 0.2s ease;
    height: 48px;
    box-sizing: border-box;
}

.range-input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(255, 90, 95, 0.1);
}

.range-separator {
    color: var(--text-light);
    font-weight: 500;
    font-size: 18px;
}

.range-currency {
    color: var(--text-light);
    font-size: 14px;
    white-space: nowrap;
    min-width: 45px;
}

.filters-actions {
    display: flex;
    gap: 12px;
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid var(--border-color);
}

.filters-actions .btn {
    flex: 1;
    height: 48px;
    justify-content: center;
}

@media (max-width: 768px) {
    .filter-row {
        grid-template-columns: 1fr;
    }
    
    .filters-actions {
        flex-direction: column;
    }
    
    .filters-actions .btn {
        width: 100%;
    }
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
const applyFiltersBtn = document.getElementById('applyFilters');
let filterTimeout;

// Функция для применения фильтров с небольшой задержкой
function applyFilters() {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(filterAndSort, 300);
}

// Автоматическое применение при изменении (с задержкой)
searchInput.addEventListener('input', applyFilters);
cityFilter.addEventListener('change', applyFilters);
categoryFilter.addEventListener('change', applyFilters);
sortBy.addEventListener('change', applyFilters);
priceMin.addEventListener('input', applyFilters);
priceMax.addEventListener('input', applyFilters);
durationMin.addEventListener('input', applyFilters);
durationMax.addEventListener('input', applyFilters);

// Явное применение по кнопке
applyFiltersBtn.addEventListener('click', () => {
    clearTimeout(filterTimeout);
    filterAndSort();
    // Закрываем фильтры после применения
    setTimeout(() => {
        closeFilters();
    }, 300);
});

resetBtn.addEventListener('click', () => {
    searchInput.value = '';
    cityFilter.value = '';
    categoryFilter.value = '';
    sortBy.value = 'date_desc';
    priceMin.value = '';
    priceMax.value = '';
    durationMin.value = '';
    durationMax.value = '';
    clearTimeout(filterTimeout);
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

// Функции для открытия/закрытия фильтров
function toggleFilters() {
    const filtersCard = document.getElementById('filtersCard');
    const toggleBtn = document.getElementById('filtersToggleBtn');
    const toggleIcon = document.getElementById('filtersToggleIcon');
    
    if (filtersCard.style.display === 'none' || !filtersCard.style.display) {
        filtersCard.style.display = 'block';
        toggleBtn.classList.add('active');
    } else {
        closeFilters();
    }
}

function closeFilters() {
    const filtersCard = document.getElementById('filtersCard');
    const toggleBtn = document.getElementById('filtersToggleBtn');
    const toggleIcon = document.getElementById('filtersToggleIcon');
    
    filtersCard.style.display = 'none';
    toggleBtn.classList.remove('active');
}

// Закрытие при клике вне блока
document.addEventListener('click', (e) => {
    const filtersCard = document.getElementById('filtersCard');
    const toggleBtn = document.getElementById('filtersToggleBtn');
    
    if (filtersCard && filtersCard.style.display !== 'none' && filtersCard.style.display) {
        if (!filtersCard.contains(e.target) && !toggleBtn.contains(e.target)) {
            closeFilters();
        }
    }
});
</script>

<?php require_once base_path('includes/footer.php'); ?>


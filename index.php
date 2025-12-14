<?php include 'includes/header.php'; ?>

<div class="hero">
    <div class="hero-carousel">
        <div class="carousel-track">
            <div class="carousel-slide"><img src="imag/imag1.jpg" alt="Экскурсия"></div>
            <div class="carousel-slide"><img src="imag/imag2.jpg" alt="Экскурсия"></div>
            <div class="carousel-slide"><img src="imag/imag3.jpg" alt="Экскурсия"></div>
            <div class="carousel-slide"><img src="imag/imag4.jpg" alt="Экскурсия"></div>
            <div class="carousel-slide"><img src="imag/imag5.jpg" alt="Экскурсия"></div>
            <div class="carousel-slide"><img src="imag/imag6.jpg" alt="Экскурсия"></div>
            <div class="carousel-slide"><img src="imag/imag7.jpg" alt="Экскурсия"></div>
            <div class="carousel-slide"><img src="imag/imag8.jpg" alt="Экскурсия"></div>
            <!-- Дубликаты для бесшовной прокрутки -->
            <div class="carousel-slide"><img src="imag/imag1.jpg" alt="Экскурсия"></div>
            <div class="carousel-slide"><img src="imag/imag2.jpg" alt="Экскурсия"></div>
            <div class="carousel-slide"><img src="imag/imag3.jpg" alt="Экскурсия"></div>
            <div class="carousel-slide"><img src="imag/imag4.jpg" alt="Экскурсия"></div>
        </div>
    </div>
    <div class="container">
        <div class="hero-text-wrapper">
            <h1>Найдите уникальные частные экскурсии</h1>
            <p>Откройте для себя город глазами местных гидов</p>
            <div class="hero-search-container">
                <form action="pages/excursions.php" method="GET" class="hero-search-form">
                    <input type="text" name="search" placeholder="Найдите места и экскурсии" class="hero-search-input">
                    <button type="submit" class="btn btn-primary hero-search-btn">
                        <i class="bi bi-search"></i> Поиск
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const carouselTrack = document.querySelector('.carousel-track');
    if (!carouselTrack) return;
    
    const slides = carouselTrack.querySelectorAll('.carousel-slide');
    const totalSlides = slides.length;
    const visibleSlides = 4; // Показываем 4 слайда одновременно
    let currentIndex = 0;
    let isTransitioning = false;
    
    function moveCarousel() {
        if (isTransitioning) return;
        isTransitioning = true;
        
        // Перемещаем карусель
        const translateX = -(currentIndex * (100 / visibleSlides));
        carouselTrack.style.transition = 'transform 1s ease-in-out';
        carouselTrack.style.transform = `translateX(${translateX}%)`;
        
        // Увеличиваем индекс
        currentIndex++;
        
        // Если достигли конца, возвращаемся к началу (бесшовная прокрутка)
        if (currentIndex >= totalSlides - visibleSlides) {
            setTimeout(() => {
                carouselTrack.style.transition = 'none';
                currentIndex = 0;
                carouselTrack.style.transform = `translateX(0%)`;
                isTransitioning = false;
            }, 1000);
        } else {
            setTimeout(() => {
                isTransitioning = false;
            }, 1000);
        }
    }
    
    // Автоматическая прокрутка каждые 3 секунды
    setInterval(moveCarousel, 3000);
    
    // Начальная позиция
    setTimeout(moveCarousel, 1000);
});
</script>

<div class="container">
    <section class="features">
        <h2>Почему выбирают нас?</h2>
        <div class="features-grid">
            <div class="feature-card">
                <h3><i class="bi bi-geo-alt"></i> Уникальные маршруты</h3>
                <p>Только авторские экскурсии от проверенных гидов</p>
            </div>
            <div class="feature-card">
                <h3><i class="bi bi-currency-exchange"></i> Лучшие цены</h3>
                <p>Прямое сотрудничество с гидами без посредников</p>
            </div>
            <div class="feature-card">
                <h3><i class="bi bi-star-fill"></i> Гарантия качества</h3>
                <p>Реальные отзывы и рейтинги от путешественников</p>
            </div>
        </div>
    </section>

    <section class="popular-excursions">
        <h2>Популярные экскурсии</h2>
        <div class="excursions-grid">
            <?php
            require_once 'config/database.php';
            $stmt = $pdo->query("
            SELECT e.*, u.full_name as guide_name, u.avatar_url as guide_avatar 
            FROM excursions e 
            JOIN users u ON e.guide_id = u.id 
            WHERE e.is_active = TRUE 
            ORDER BY e.created_at DESC 
            LIMIT 3
            ");
            while($excursion = $stmt->fetch()):
            ?>
            <div class="excursion-card">
                <?php if($excursion['image_url']): ?>
                    <img src="<?php echo asset_path($excursion['image_url']); ?>" alt="<?php echo htmlspecialchars($excursion['title']); ?>">
                <?php endif; ?>
                <div class="card-content">
                    <h3><?php echo htmlspecialchars($excursion['title']); ?></h3>
                    <p class="city"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($excursion['city']); ?></p>
                    <p class="guide">Гид: <?php echo htmlspecialchars($excursion['guide_name']); ?></p>
                    <p class="price"><i class="bi bi-currency-exchange"></i> <?php echo $excursion['price']; ?> руб.</p>
                    <a href="pages/booking.php?excursion_id=<?php echo $excursion['id']; ?>" class="btn btn-secondary">Подробнее</a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </section>
</div>

<?php include 'includes/footer.php'; ?>
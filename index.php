<?php include 'includes/header.php'; ?>

<div class="hero">
    <div class="container">
        <h1>Найдите уникальные частные экскурсии</h1>
        <p>Откройте для себя город глазами местных гидов</p>
        <a href="pages/excursions.php" class="btn btn-primary">Найти экскурсии</a>
    </div>
</div>

<div class="container">
    <section class="features">
        <h2>Почему выбирают нас?</h2>
        <div class="features-grid">
            <div class="feature-card">
                <h3>🚀 Уникальные маршруты</h3>
                <p>Только авторские экскурсии от проверенных гидов</p>
            </div>
            <div class="feature-card">
                <h3>💰 Лучшие цены</h3>
                <p>Прямое сотрудничество с гидами без посредников</p>
            </div>
            <div class="feature-card">
                <h3>⭐ Гарантия качества</h3>
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
                SELECT e.*, u.full_name as guide_name 
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
                    <p class="city">📍 <?php echo htmlspecialchars($excursion['city']); ?></p>
                    <p class="guide">Гид: <?php echo htmlspecialchars($excursion['guide_name']); ?></p>
                    <p class="price">💰 <?php echo $excursion['price']; ?> руб.</p>
                    <a href="pages/booking.php?excursion_id=<?php echo $excursion['id']; ?>" class="btn btn-secondary">Подробнее</a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </section>
</div>

<?php include 'includes/footer.php'; ?>
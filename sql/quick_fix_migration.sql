-- Быстрое исправление: добавление недостающих полей
USE excursion_platform;

-- Добавляем поле special_requests в orders (если его нет)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'excursion_platform' 
    AND TABLE_NAME = 'orders' 
    AND COLUMN_NAME = 'special_requests');
    
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE orders ADD COLUMN special_requests TEXT AFTER total_price',
    'SELECT "Column special_requests already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Добавляем поле guide_rating в reviews (если его нет)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'excursion_platform' 
    AND TABLE_NAME = 'reviews' 
    AND COLUMN_NAME = 'guide_rating');
    
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE reviews ADD COLUMN guide_rating INT CHECK (guide_rating >= 1 AND guide_rating <= 5) AFTER rating',
    'SELECT "Column guide_rating already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Добавляем поле updated_at в reviews (если его нет)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'excursion_platform' 
    AND TABLE_NAME = 'reviews' 
    AND COLUMN_NAME = 'updated_at');
    
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE reviews ADD COLUMN updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at',
    'SELECT "Column updated_at already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Migration completed successfully!' AS result;





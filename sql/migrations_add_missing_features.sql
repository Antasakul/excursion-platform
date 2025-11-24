-- Миграция для добавления недостающих функций
USE excursion_platform;

-- 1. Добавляем поля для блокировки пользователей и верификации гидов
ALTER TABLE users 
  ADD COLUMN IF NOT EXISTS is_blocked BOOLEAN DEFAULT FALSE AFTER user_type,
  ADD COLUMN IF NOT EXISTS is_verified BOOLEAN DEFAULT FALSE AFTER is_blocked,
  ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL AFTER created_at;

-- 2. Добавляем модерацию экскурсий
ALTER TABLE excursions 
  ADD COLUMN IF NOT EXISTS is_moderated BOOLEAN DEFAULT FALSE AFTER is_active,
  ADD COLUMN IF NOT EXISTS moderation_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' AFTER is_moderated,
  ADD COLUMN IF NOT EXISTS moderation_comment TEXT AFTER moderation_status,
  ADD COLUMN IF NOT EXISTS moderated_at TIMESTAMP NULL AFTER moderation_comment,
  ADD COLUMN IF NOT EXISTS moderated_by INT NULL AFTER moderated_at,
  ADD INDEX idx_moderation_status (moderation_status);

-- 3. Добавляем специальные пожелания к заказу
ALTER TABLE orders 
  ADD COLUMN IF NOT EXISTS special_requests TEXT AFTER total_price;

-- 4. Добавляем отдельную оценку гида в отзывы
ALTER TABLE reviews 
  ADD COLUMN IF NOT EXISTS guide_rating INT CHECK (guide_rating >= 1 AND guide_rating <= 5) AFTER rating,
  ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- 5. Добавляем блокировку дат
ALTER TABLE excursion_dates 
  ADD COLUMN IF NOT EXISTS is_blocked BOOLEAN DEFAULT FALSE AFTER is_available,
  ADD COLUMN IF NOT EXISTS block_reason TEXT AFTER is_blocked;

-- 6. Создаем таблицу уведомлений
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('order', 'message', 'review', 'reminder', 'system', 'moderation') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_type (type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Создаем таблицу для восстановления пароля
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) UNIQUE NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Создаем таблицу категорий (для управления категориями)
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    parent_id INT NULL,
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_parent (parent_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Создаем таблицу системных настроек
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Вставляем начальные категории (если их еще нет)
INSERT IGNORE INTO categories (name, description, display_order) VALUES
('История', 'Исторические экскурсии', 1),
('Архитектура', 'Архитектурные экскурсии', 2),
('Искусство', 'Экскурсии по искусству', 3),
('Гастрономия', 'Гастрономические туры', 4),
('Природа', 'Природные экскурсии', 5),
('Приключения', 'Приключенческие туры', 6),
('Фотосессия', 'Фотосессии', 7);

-- 11. Вставляем начальные системные настройки
INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, description) VALUES
('platform_commission', '10', 'number', 'Комиссия платформы в процентах'),
('min_booking_hours', '24', 'number', 'Минимальное время бронирования в часах'),
('cancel_hours_before', '48', 'number', 'Минимальное время до экскурсии для отмены в часах'),
('moderation_hours', '24', 'number', 'Время модерации в часах'),
('enable_notifications', 'true', 'boolean', 'Включить уведомления');

-- 12. Обновляем существующие экскурсии: устанавливаем модерацию
UPDATE excursions SET is_moderated = TRUE, moderation_status = 'approved' WHERE is_active = TRUE;


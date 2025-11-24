-- SQL скрипт для добавления роли администратора

-- Шаг 1: Модифицировать тип user_type, добавив 'admin'
ALTER TABLE users
  MODIFY user_type ENUM('guide', 'customer', 'admin') NOT NULL DEFAULT 'customer';

-- Шаг 2: Создать первого администратора (по умолчанию)
-- ВАЖНО: Измените данные на свои перед выполнением!
INSERT INTO users (username, email, password, full_name, user_type, phone)
VALUES (
  'admin',
  'admin@excursion.local',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- пароль: password
  'Администратор',
  'admin',
  '+375 29 000 00 00'
);

-- Либо, если у вас уже есть пользователь и хотите сделать его администратором:
-- UPDATE users SET user_type = 'admin' WHERE id = 1;

-- Проверка
SELECT id, username, email, full_name, user_type FROM users WHERE user_type = 'admin';


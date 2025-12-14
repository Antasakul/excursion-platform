-- Скрипт для добавления промо-кода в базу данных
-- Использование: выполните этот SQL-запрос в вашей базе данных

-- Пример 1: Промо-код на 10% скидку, действует 30 дней, максимум 100 использований
INSERT INTO promo_codes (code, discount_percent, max_uses, valid_until, is_active) 
VALUES ('SUMMER2024', 10.00, 100, DATE_ADD(CURDATE(), INTERVAL 30 DAY), TRUE);

-- Пример 2: Промо-код на 20% скидку, действует 60 дней, максимум 50 использований
INSERT INTO promo_codes (code, discount_percent, max_uses, valid_until, is_active) 
VALUES ('WELCOME20', 20.00, 50, DATE_ADD(CURDATE(), INTERVAL 60 DAY), TRUE);

-- Пример 3: Промо-код на 15% скидку, действует до конкретной даты, неограниченное количество использований
INSERT INTO promo_codes (code, discount_percent, max_uses, valid_until, is_active) 
VALUES ('NEWYEAR2024', 15.00, 999999, '2024-12-31', TRUE);

-- Пример 4: Промо-код на 25% скидку, действует 7 дней, максимум 10 использований
INSERT INTO promo_codes (code, discount_percent, max_uses, valid_until, is_active) 
VALUES ('FLASH25', 25.00, 10, DATE_ADD(CURDATE(), INTERVAL 7 DAY), TRUE);

-- Просмотр всех активных промо-кодов
SELECT * FROM promo_codes WHERE is_active = TRUE AND valid_until >= CURDATE();

-- Просмотр всех промо-кодов (включая неактивные)
SELECT * FROM promo_codes ORDER BY created_at DESC;

-- Деактивация промо-кода
-- UPDATE promo_codes SET is_active = FALSE WHERE code = 'SUMMER2024';

-- Удаление промо-кода
-- DELETE FROM promo_codes WHERE code = 'SUMMER2024';


-- Добавление поля cancelled_by в таблицу orders
-- Это поле будет хранить информацию о том, кто отменил заказ: 'customer' или 'guide'

USE excursion_platform;

ALTER TABLE orders 
ADD COLUMN cancelled_by ENUM('customer', 'guide') NULL DEFAULT NULL 
AFTER status;

-- Обновление существующих отмененных заказов
-- Если заказ отменен, но cancelled_by не установлен, устанавливаем по умолчанию 'customer'
UPDATE orders 
SET cancelled_by = 'customer' 
WHERE status = 'cancelled' AND cancelled_by IS NULL;








# Платформа для продажи билетов на частные экскурсии

Веб-платформа для организации и бронирования частных экскурсий с полным функционалом управления для гидов, клиентов и администраторов.

## Технологии

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Server**: Apache/Nginx + PHP-FPM

## Возможности

### Для клиентов
- Просмотр каталога экскурсий с фильтрацией по городу, категории, цене
- Детальная информация об экскурсии, гиде, отзывах
- Бронирование экскурсий с выбором даты и количества участников
- Применение промо-кодов
- Личный кабинет с историей заказов
- Избранные экскурсии

### Для гидов
- Создание и редактирование экскурсий
- Управление расписанием и доступностью
- Загрузка изображений (основное + галерея)
- Управление бронированиями (подтверждение/отмена)
- Статистика по продажам

### Для администраторов
- Управление пользователями (изменение ролей)
- Модерация экскурсий (активация/деактивация)
- Управление заказами (изменение статусов)
- Удаление отзывов
- Общая аналитика платформы

## Установка

### 1. Требования
- PHP 7.4+ с расширениями: PDO, PDO_MySQL, GD, mbstring
- MySQL 5.7+ или MariaDB 10.2+
- Apache/Nginx с mod_rewrite (для Apache)

### 2. Клонирование и настройка

```bash
# Перейдите в директорию веб-сервера
cd C:\xampp\htdocs\  # для Windows + XAMPP
# или
cd /var/www/html/    # для Linux

# Разместите файлы проекта
# Например: C:\БГУИР\4 курс 1 семестр\ИТиВП\excursion-platform\
```

### 3. Настройка базы данных

Импортируйте схему базы данных:

```sql
mysql -u root -p < sql/database_schema.sql
```

Или выполните вручную через phpMyAdmin/MySQL Workbench скрипты из папки `sql/`.

### 4. Конфигурация подключения

Откройте `config/database.php` и укажите параметры подключения:

```php
$host = 'localhost';
$dbname = 'excursion_platform';
$username = 'root';
$password = 'your_password'; // укажите ваш пароль MySQL
```

### 5. Настройка загрузки файлов

#### Метод 1: Через php.ini (рекомендуется)

Найдите и отредактируйте файл `php.ini`:

```ini
upload_max_filesize = 20M
post_max_size = 20M
max_execution_time = 120
max_input_time = 120
```

#### Метод 2: Через .htaccess (для Apache)

Файл `.htaccess` уже содержит необходимые директивы:

```apache
php_value upload_max_filesize 20M
php_value post_max_size 20M
```

#### Метод 3: Через .user.ini (для некоторых хостингов)

Файл `.user.ini` уже создан в корне проекта.

**ВАЖНО**: После изменения настроек перезапустите веб-сервер!

### 6. Права доступа

Убедитесь, что папка `uploads/` доступна для записи:

```bash
# Linux/Mac
chmod -R 755 uploads/

# Windows: правой кнопкой -> Свойства -> Безопасность -> Разрешить запись
```

### 7. Запуск

#### Вариант A: Встроенный сервер PHP (для разработки)

```bash
cd C:\БГУИР\4 курс 1 семестр\ИТиВП\excursion-platform
php -S localhost:8000
```

Откройте в браузере: `http://localhost:8000`

#### Вариант B: XAMPP/WAMP/OpenServer

1. Разместите проект в `htdocs` (XAMPP) или `www` (OpenServer)
2. Запустите Apache и MySQL
3. Откройте: `http://localhost/excursion-platform/`

### 8. Создание администратора

Выполните SQL-скрипт:

```bash
mysql -u root -p excursion_platform < sql/add_admin_role.sql
```

Или вручную:

```sql
ALTER TABLE users
  MODIFY user_type ENUM('guide', 'customer', 'admin') NOT NULL DEFAULT 'customer';

-- Создать нового администратора
INSERT INTO users (username, email, password, full_name, user_type)
VALUES ('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Администратор', 'admin');

-- Или повысить существующего пользователя
UPDATE users SET user_type = 'admin' WHERE id = 1;
```

**Логин**: `admin`  
**Пароль**: `password` (измените после первого входа!)

## Использование

### Демо-аккаунты

На странице входа указаны тестовые аккаунты:

- **Гид**: `guide1` / `password123`
- **Клиент**: `customer1` / `password123`

### Структура проекта

```
excursion-platform/
├── config/
│   ├── app.php           # Конфигурация приложения, хелперы путей
│   └── database.php      # Подключение к БД
├── includes/
│   ├── header.php        # Общий хедер
│   ├── footer.php        # Общий футер
│   ├── auth.php          # Авторизация и регистрация
│   ├── manage_excursion.php   # Создание/редактирование экскурсий
│   ├── process_booking.php    # Обработка бронирований
│   ├── admin_actions.php      # Административные действия
│   └── ...
├── pages/
│   ├── index.php         # Главная страница
│   ├── login.php         # Вход
│   ├── register.php      # Регистрация
│   ├── dashboard.php     # ЛК гида/клиента
│   ├── excursions.php    # Каталог экскурсий
│   ├── booking.php       # Страница бронирования
│   ├── create_excursions.php  # Создание экскурсии
│   ├── edit_excursion.php     # Редактирование экскурсии
│   └── admin/
│       └── dashboard.php      # Админ-панель
├── css/
│   └── style.css         # Стили
├── js/
│   └── script.js         # JavaScript
├── uploads/              # Загруженные файлы (изображения)
├── sql/                  # SQL-скрипты
├── .htaccess            # Конфигурация Apache
├── .user.ini            # Конфигурация PHP (для некоторых хостингов)
└── README.md            # Этот файл
```

## Решение проблем

### Изображения не отображаются (404)

**Проблема**: `GET /pages/uploads/... 404 Not Found`

**Причина**: Неправильный путь к изображениям

**Решение**: Все изображения теперь используют функцию `asset_path()`, которая автоматически строит корректный путь от корня проекта. Убедитесь, что:

1. Папка `uploads/` находится в корне проекта
2. Файлы реально существуют в `uploads/`
3. Перезапущен сервер после изменений

### Ошибка "POST Content-Length exceeds the limit"

**Проблема**: Файл слишком большой для загрузки

**Решение**:
1. Отредактируйте `php.ini`:
   ```ini
   upload_max_filesize = 20M
   post_max_size = 20M
   ```
2. **Обязательно перезапустите** Apache/Nginx/PHP-FPM
3. Проверьте: `<?php phpinfo(); ?>` → найдите `upload_max_filesize`

### Сессии не работают

**Решение**: Убедитесь, что в `php.ini`:
```ini
session.save_path = "/tmp"  # для Linux
session.save_path = "C:/Windows/Temp"  # для Windows
```

### Ошибки с кодировкой (кириллица)

**Решение**: В `config/database.php` указан charset:
```php
$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", ...);
```

Убедитесь, что таблицы БД используют `utf8mb4_unicode_ci`.

## Безопасность

- Пароли хешируются через `password_hash()` (bcrypt)
- SQL-запросы используют подготовленные выражения (prepared statements)
- XSS-защита через `htmlspecialchars()`
- Проверка типов файлов при загрузке
- Сессии с автоматическим таймаутом

## Лицензия

Учебный проект для курса "ИТиВП", БГУИР, 4 курс 1 семестр.

## Поддержка

При возникновении вопросов обратитесь к преподавателю или проверьте логи:
- PHP errors: `php_error.log` (в зависимости от настройки `error_log` в php.ini)
- Apache: `error.log` в папке логов Apache


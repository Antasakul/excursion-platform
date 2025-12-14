<?php
require_once __DIR__ . '/../config/app.php';
require_once base_path('config/database.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(!isset($_SESSION['user_id'])) {
    header('Location: ' . route_path('pages/login.php'));
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['avatar'])) {
    $user_id = $_SESSION['user_id'];
    $uploadDir = base_path('uploads/avatars');
    $publicUploadPrefix = 'uploads/avatars/';
    $maxUploadSize = 5 * 1024 * 1024; // 5 MB
    
    if(!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $file = $_FILES['avatar'];
    
    try {
        // Проверка ошибок загрузки
        if($file['error'] !== UPLOAD_ERR_OK) {
            $messages = [
                UPLOAD_ERR_INI_SIZE   => 'Размер файла превышает допустимый лимит сервера',
                UPLOAD_ERR_FORM_SIZE  => 'Размер файла превышает установленное ограничение формы',
                UPLOAD_ERR_PARTIAL    => 'Файл был загружен только частично',
                UPLOAD_ERR_NO_FILE    => 'Файл не был загружен',
                UPLOAD_ERR_NO_TMP_DIR => 'Отсутствует временная папка на сервере',
                UPLOAD_ERR_CANT_WRITE => 'Не удалось записать файл на диск',
                UPLOAD_ERR_EXTENSION  => 'Загрузка остановлена расширением PHP'
            ];
            throw new RuntimeException($messages[$file['error']] ?? 'Не удалось загрузить файл');
        }
        
        // Проверка размера
        if($file['size'] > $maxUploadSize) {
            throw new RuntimeException('Размер файла превышает 5 МБ');
        }
        
        // Проверка типа файла
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if(!in_array($mimeType, $allowedTypes)) {
            throw new RuntimeException('Недопустимый тип файла. Разрешены: JPG, PNG, GIF, WEBP');
        }
        
        // Получаем текущий аватар для удаления
        $stmt = $pdo->prepare("SELECT avatar_url FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        $oldAvatar = $user['avatar_url'] ?? null;
        
        // Генерируем уникальное имя файла
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'avatar_' . $user_id . '_' . time() . '_' . uniqid() . '.' . $extension;
        $fullPath = $uploadDir . DIRECTORY_SEPARATOR . $filename;
        
        // Загружаем файл
        if(!move_uploaded_file($file['tmp_name'], $fullPath)) {
            throw new RuntimeException('Не удалось сохранить файл');
        }
        
        // Обновляем БД
        $avatarUrl = $publicUploadPrefix . $filename;
        $stmt = $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
        $stmt->execute([$avatarUrl, $user_id]);
        
        // Удаляем старый аватар
        if($oldAvatar && file_exists(base_path($oldAvatar))) {
            @unlink(base_path($oldAvatar));
        }
        
        $_SESSION['success'] = "Фото профиля успешно загружено";
        $_SESSION['avatar_url'] = $avatarUrl;
        
    } catch(Exception $e) {
        $_SESSION['error'] = "Ошибка загрузки фото: " . $e->getMessage();
    }
}

// Перенаправляем на предыдущую страницу
require_once __DIR__ . '/redirect_helper.php';
$redirectUrl = getRedirectUrl();
header('Location: ' . $redirectUrl);
exit();



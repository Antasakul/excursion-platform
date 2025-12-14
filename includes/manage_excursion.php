<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/app.php';
require_once base_path('config/database.php');

if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'guide') {
    header('Location: ' . route_path('pages/login.php'));
    exit();
}

$userId = $_SESSION['user_id'];
$uploadDir = base_path('uploads');
$publicUploadPrefix = 'uploads/';
$maxUploadSize = 20 * 1024 * 1024; // 20 MB

if(!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Проверка размера POST-запроса
$postMaxSize = ini_get('post_max_size');
$postMaxBytes = parseSize($postMaxSize);
$contentLength = $_SERVER['CONTENT_LENGTH'] ?? 0;

if($contentLength > $postMaxBytes) {
    $_SESSION['error'] = sprintf(
        'Общий размер загружаемых файлов (%.2f МБ) превышает лимит сервера (%.2f МБ). ' .
        'Уменьшите размер файлов или обратитесь к администратору для увеличения лимита.',
        $contentLength / 1024 / 1024,
        $postMaxBytes / 1024 / 1024
    );
    header('Location: ' . route_path('pages/create_excursions.php'));
    exit();
}

function parseSize(string $size): int
{
    $unit = strtolower(substr($size, -1));
    $value = (int)$size;
    switch($unit) {
        case 'g': return $value * 1024 * 1024 * 1024;
        case 'm': return $value * 1024 * 1024;
        case 'k': return $value * 1024;
        default: return $value;
    }
}

function redirectToDashboard(): void
{
    require_once __DIR__ . '/redirect_helper.php';
    $redirectUrl = getRedirectUrl();
    header('Location: ' . $redirectUrl);
    exit();
}

function storeImage(array $file, string $uploadDir, string $publicPrefix, int $maxSize, string $prefix = 'excursion_'): ?string
{
    if(empty($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

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
        $message = $messages[$file['error']] ?? 'Не удалось загрузить файл';
        throw new RuntimeException($message);
    }

    if(($file['size'] ?? 0) > $maxSize) {
        throw new RuntimeException('Файл больше допустимых 20 МБ');
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if(!in_array($extension, $allowedExtensions, true)) {
        throw new RuntimeException('Недопустимый формат изображения');
    }

    $filename = $prefix . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $fullPath = rtrim($uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

    if(!move_uploaded_file($file['tmp_name'], $fullPath)) {
        throw new RuntimeException('Не удалось сохранить файл на сервере');
    }

    return $publicPrefix . $filename;
}

function deleteUploadedFile(?string $relativePath): void
{
    if(empty($relativePath)) {
        return;
    }

    $fullPath = base_path($relativePath);
    if(is_file($fullPath)) {
        @unlink($fullPath);
    }
}

try {
    if($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $shortDescription = trim($_POST['short_description'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $duration = (int)($_POST['duration'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        $maxParticipants = (int)($_POST['max_participants'] ?? 0);
        $meetingPoint = trim($_POST['meeting_point'] ?? '');
        $address = trim($_POST['address'] ?? '');

        $pdo->beginTransaction();

        $imageUrl = null;
        if(isset($_FILES['image'])) {
            $imageUrl = storeImage($_FILES['image'], $uploadDir, $publicUploadPrefix, $maxUploadSize, 'excursion_main_');
        }

        $stmt = $pdo->prepare("
            INSERT INTO excursions (guide_id, title, description, short_description, city, category, duration, price, max_participants, meeting_point, address, image_url) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $title, $description, $shortDescription, $city, $category, $duration, $price, $maxParticipants, $meetingPoint, $address, $imageUrl]);
        $excursionId = (int)$pdo->lastInsertId();

        if(!empty($_POST['dates'])) {
            foreach($_POST['dates'] as $index => $dateValue) {
                $date = $_POST['dates'][$index] ?? null;
                $time = $_POST['times'][$index] ?? null;
                $slots = (int)($_POST['slots'][$index] ?? 0);

                if($date && $time && $slots > 0) {
                    $stmt = $pdo->prepare("
                        INSERT INTO excursion_dates (excursion_id, available_date, available_time, available_slots) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$excursionId, $date, $time, $slots]);
                }
            }
        }

        // Обработка дополнительных изображений (галерея)
        $uploadedCount = 0;
        $failedCount = 0;
        $errors = [];
        
        if(isset($_FILES['additional_images']) && is_array($_FILES['additional_images']['name'])) {
            foreach($_FILES['additional_images']['name'] as $idx => $fileName) {
                // Пропускаем пустые поля
                if(empty($fileName) || $_FILES['additional_images']['error'][$idx] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                
                $file = [
                    'name'     => $_FILES['additional_images']['name'][$idx],
                    'type'     => $_FILES['additional_images']['type'][$idx],
                    'tmp_name' => $_FILES['additional_images']['tmp_name'][$idx],
                    'error'    => $_FILES['additional_images']['error'][$idx],
                    'size'     => $_FILES['additional_images']['size'][$idx],
                ];

                try {
                    $stored = storeImage($file, $uploadDir, $publicUploadPrefix, $maxUploadSize, 'excursion_gallery_');
                    if($stored) {
                        $stmt = $pdo->prepare("INSERT INTO excursion_images (excursion_id, image_url) VALUES (?, ?)");
                        $stmt->execute([$excursionId, $stored]);
                        $uploadedCount++;
                    }
                } catch(RuntimeException $e) {
                    $failedCount++;
                    $errors[] = $fileName . ': ' . $e->getMessage();
                }
            }
        }
        
        // Информация о загрузке галереи
        if($uploadedCount > 0 || $failedCount > 0) {
            $galleryMessage = "Загружено изображений в галерею: $uploadedCount";
            if($failedCount > 0) {
                $galleryMessage .= ". Ошибок: $failedCount";
                if(count($errors) > 0) {
                    $galleryMessage .= " (" . implode(', ', array_slice($errors, 0, 3)) . ")";
                }
            }
            $_SESSION['info'] = $galleryMessage;
        }

        $pdo->commit();
        $_SESSION['success'] = 'Экскурсия успешно создана!';
        redirectToDashboard();
    }

    if($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
        $excursionId = (int)($_POST['excursion_id'] ?? 0);

        $stmt = $pdo->prepare("SELECT * FROM excursions WHERE id = ? AND guide_id = ?");
        $stmt->execute([$excursionId, $userId]);
        $excursion = $stmt->fetch();

        if(!$excursion) {
            $_SESSION['error'] = 'Экскурсия не найдена';
            redirectToDashboard();
        }

        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $shortDescription = trim($_POST['short_description'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $duration = (int)($_POST['duration'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        $maxParticipants = (int)($_POST['max_participants'] ?? 0);
        $meetingPoint = trim($_POST['meeting_point'] ?? '');
        $address = trim($_POST['address'] ?? '');

        $pdo->beginTransaction();

        $imageUrl = $excursion['image_url'];
        if(isset($_FILES['image'])) {
            $newImage = storeImage($_FILES['image'], $uploadDir, $publicUploadPrefix, $maxUploadSize, 'excursion_main_');
            if($newImage) {
                deleteUploadedFile($excursion['image_url']);
                $imageUrl = $newImage;
            }
        }

        $stmt = $pdo->prepare("
            UPDATE excursions 
            SET title = ?, description = ?, short_description = ?, city = ?, category = ?, duration = ?, price = ?, max_participants = ?, meeting_point = ?, address = ?, image_url = ?
            WHERE id = ? AND guide_id = ?
        ");
        $stmt->execute([$title, $description, $shortDescription, $city, $category, $duration, $price, $maxParticipants, $meetingPoint, $address, $imageUrl, $excursionId, $userId]);

        if(!empty($_POST['existing_dates'])) {
            foreach($_POST['existing_dates'] as $dateId => $dateData) {
                $date = $dateData['date'] ?? null;
                $time = $dateData['time'] ?? null;
                $slots = (int)($dateData['slots'] ?? 0);
                $isAvailable = isset($dateData['is_available']) ? 1 : 0;

                if($date && $time && $slots > 0) {
        $stmt = $pdo->prepare("
                        UPDATE excursion_dates 
                        SET available_date = ?, available_time = ?, available_slots = ?, is_available = ?
                        WHERE id = ? AND excursion_id = ?
                    ");
                    $stmt->execute([$date, $time, $slots, $isAvailable, $dateId, $excursionId]);
                }
            }
        }

        if(!empty($_POST['remove_date_ids'])) {
            $in = implode(',', array_fill(0, count($_POST['remove_date_ids']), '?'));
            $params = array_map('intval', $_POST['remove_date_ids']);
            $params[] = $excursionId;

            $stmt = $pdo->prepare("DELETE FROM excursion_dates WHERE id IN ($in) AND excursion_id = ?");
            $stmt->execute($params);
        }

        if(!empty($_POST['new_dates'])) {
            foreach($_POST['new_dates'] as $index => $dateValue) {
                $date = $_POST['new_dates'][$index] ?? null;
                $time = $_POST['new_times'][$index] ?? null;
                $slots = (int)($_POST['new_slots'][$index] ?? 0);

                if($date && $time && $slots > 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO excursion_dates (excursion_id, available_date, available_time, available_slots) 
                    VALUES (?, ?, ?, ?)
                ");
                    $stmt->execute([$excursionId, $date, $time, $slots]);
                }
            }
        }

        if(!empty($_POST['remove_image_ids'])) {
            $in = implode(',', array_fill(0, count($_POST['remove_image_ids']), '?'));
            $params = array_map('intval', $_POST['remove_image_ids']);
            $params[] = $excursionId;

            $stmt = $pdo->prepare("SELECT id, image_url FROM excursion_images WHERE id IN ($in) AND excursion_id = ?");
            $stmt->execute($params);
            $images = $stmt->fetchAll();

            foreach($images as $image) {
                deleteUploadedFile($image['image_url']);
            }

            $stmt = $pdo->prepare("DELETE FROM excursion_images WHERE id IN ($in) AND excursion_id = ?");
            $stmt->execute($params);
        }

        if(isset($_FILES['additional_images'])) {
            foreach($_FILES['additional_images']['tmp_name'] as $idx => $tmpName) {
                $file = [
                    'name'     => $_FILES['additional_images']['name'][$idx],
                    'type'     => $_FILES['additional_images']['type'][$idx],
                    'tmp_name' => $_FILES['additional_images']['tmp_name'][$idx],
                    'error'    => $_FILES['additional_images']['error'][$idx],
                    'size'     => $_FILES['additional_images']['size'][$idx],
                ];

                $stored = storeImage($file, $uploadDir, $publicUploadPrefix, $maxUploadSize, 'excursion_gallery_');
                if($stored) {
                        $stmt = $pdo->prepare("INSERT INTO excursion_images (excursion_id, image_url) VALUES (?, ?)");
                    $stmt->execute([$excursionId, $stored]);
                }
            }
        }

        $pdo->commit();
        $_SESSION['success'] = 'Изменения сохранены';
        redirectToDashboard();
    }

    if(isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
        $excursionId = (int)$_GET['id'];

    $stmt = $pdo->prepare("SELECT is_active FROM excursions WHERE id = ? AND guide_id = ?");
        $stmt->execute([$excursionId, $userId]);
    $excursion = $stmt->fetch();
    
    if($excursion) {
            $newStatus = $excursion['is_active'] ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE excursions SET is_active = ? WHERE id = ?");
            $stmt->execute([$newStatus, $excursionId]);
            $_SESSION['success'] = $newStatus ? 'Экскурсия активирована' : 'Экскурсия деактивирована';
    } else {
            $_SESSION['error'] = 'Экскурсия не найдена';
        }

        redirectToDashboard();
    }
} catch(Throwable $e) {
    if($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error'] = 'Ошибка: ' . $e->getMessage();
    redirectToDashboard();
}
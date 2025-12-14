<?php
/**
 * Функция для получения URL для редиректа
 * Приоритет: POST redirect > GET redirect > HTTP_REFERER > dashboard по умолчанию
 * Изменено: всегда приоритетно используем HTTP_REFERER для остаться на текущей странице
 */
function getRedirectUrl($defaultDashboard = null) {
    // Убеждаемся, что config/app.php загружен
    if(!function_exists('route_path')) {
        require_once __DIR__ . '/../config/app.php';
    }
    
    // Проверяем параметр redirect в POST (явно указанный redirect имеет наивысший приоритет)
    if(isset($_POST['redirect']) && !empty($_POST['redirect'])) {
        return $_POST['redirect'];
    }
    
    // Проверяем параметр redirect в GET (явно указанный redirect имеет высокий приоритет)
    if(isset($_GET['redirect']) && !empty($_GET['redirect'])) {
        return $_GET['redirect'];
    }
    
    // ВСЕГДА используем HTTP_REFERER если он есть и это наш сайт (чтобы остаться на текущей странице)
    if(isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
        $referer = $_SERVER['HTTP_REFERER'];
        // Проверяем, что referer с нашего домена (базовая проверка безопасности)
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        if(strpos($referer, $baseUrl) === 0) {
            return $referer;
        }
    }
    
    // Используем dashboard по умолчанию только если нет HTTP_REFERER
    if($defaultDashboard) {
        return $defaultDashboard;
    }
    
    // Определяем dashboard по роли пользователя (только если нет HTTP_REFERER)
    if(isset($_SESSION['user_type'])) {
        $userType = $_SESSION['user_type'];
        if($userType === 'admin') {
            return route_path('pages/admin/dashboard.php');
        } elseif($userType === 'guide') {
            return route_path('pages/guide/dashboard.php');
        } else {
            return route_path('pages/customer/dashboard.php');
        }
    }
    
    // Последний вариант - главная страница
    return route_path('index.php');
}


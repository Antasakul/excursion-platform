<?php

ini_set('upload_max_filesize', '20M');
ini_set('post_max_size', '20M');
ini_set('max_execution_time', '120');
ini_set('max_input_time', '120');

if (!defined('BASE_PATH')) {
    $projectRoot = str_replace('\\', '/', realpath(dirname(__DIR__)));
    define('BASE_PATH', $projectRoot);
}

if (!defined('BASE_URI')) {
    $documentRoot = isset($_SERVER['DOCUMENT_ROOT'])
        ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']))
        : '';

    $relativePath = $documentRoot && strpos(BASE_PATH, $documentRoot) === 0
        ? trim(str_replace($documentRoot, '', BASE_PATH), '/')
        : '';

    $baseUri = $relativePath === '' ? '' : '/' . $relativePath;

    define('BASE_URI', $baseUri);
}

if (!function_exists('route_path')) {
    function route_path(string $path = ''): string
    {
        $cleanPath = ltrim($path, '/');
        $base = BASE_URI;
        $prefix = $base === '' ? '' : $base;

        return rtrim($prefix, '/') . ($cleanPath === '' ? '' : '/' . $cleanPath);
    }
}

if (!function_exists('asset_path')) {
    function asset_path(string $path = ''): string
    {
        $cleanPath = ltrim($path, '/');
        $base = BASE_URI;
        $prefix = $base === '' ? '' : $base;

        return rtrim($prefix, '/') . ($cleanPath === '' ? '' : '/' . $cleanPath);
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $cleanPath = ltrim($path, '/');
        $root = rtrim(BASE_PATH, '/');

        return $cleanPath === '' ? $root : $root . '/' . $cleanPath;
    }
}


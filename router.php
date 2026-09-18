<?php
/**
 * PHP Built-in Server Router for Work & HR Management
 *
 * Cách chạy:
 *   php -S localhost:8000 router.php
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Bỏ tiền tố dự án /work-hr-management nếu có
$cleanPath = $uri;
$projectPrefix = '/work-hr-management';
if (strpos($cleanPath, $projectPrefix) === 0) {
    $cleanPath = substr($cleanPath, strlen($projectPrefix));
}
$cleanPath = '/' . ltrim($cleanPath, '/');

// Xử lý Favicon nhanh nếu không có file thực tế
if ($cleanPath === '/favicon.ico') {
    $favicon = __DIR__ . '/public/favicon.ico';
    if (file_exists($favicon)) {
        header('Content-Type: image/x-icon');
        readfile($favicon);
        return true;
    }
    http_response_code(204);
    return true;
}

// 1. Kiểm tra file tĩnh hoặc file PHP trực tiếp (assets, uploads, app/View/...)
$candidates = [
    __DIR__ . $cleanPath,
    __DIR__ . '/public' . $cleanPath,
];

foreach ($candidates as $filePath) {
    if (is_file($filePath)) {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // Nếu là file PHP (ví dụ các trang trong app/View/...)
        if ($ext === 'php') {
            $_SERVER['SCRIPT_FILENAME'] = $filePath;
            chdir(dirname($filePath));
            require $filePath;
            return true;
        }

        // Định dạng MIME type chuẩn cho file tĩnh
        $mimes = [
            'css'   => 'text/css; charset=utf-8',
            'js'    => 'application/javascript; charset=utf-8',
            'json'  => 'application/json; charset=utf-8',
            'png'   => 'image/png',
            'jpg'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'gif'   => 'image/gif',
            'svg'   => 'image/svg+xml',
            'ico'   => 'image/x-icon',
            'woff'  => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf'   => 'font/ttf',
            'eot'   => 'application/vnd.ms-fontobject',
            'pdf'   => 'application/pdf',
            'txt'   => 'text/plain; charset=utf-8',
        ];

        $contentType = $mimes[$ext] ?? (function_exists('mime_content_type') ? mime_content_type($filePath) : 'application/octet-stream');
        header('Content-Type: ' . $contentType);
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        return true;
    }
}

// 2. Chuyển tiếp toàn bộ request còn lại vào public/index.php
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/public/index.php';
$_SERVER['SCRIPT_NAME'] = '/public/index.php';
require __DIR__ . '/public/index.php';
return true;

<?php
/**
 * File kết nối PDO (giữ lại cho Chương 2 & Chương 4 - Issue #20).
 * Không tự tạo PDO riêng để tránh có 2 connection song song trong app —
 * mọi kết nối đều lấy từ Core\Database (singleton), đảm bảo toàn bộ
 * ứng dụng luôn dùng chung 1 transaction context.
 */

date_default_timezone_set('Asia/Ho_Chi_Minh');

require_once __DIR__ . '/../core/Database.php';

use Core\Database;

try {
    $pdo = Database::getConnection(); // Đã bật PDO::ATTR_ERRMODE_EXCEPTION bên trong Database.php
} catch (\Throwable $e) {
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}
<?php

// Thiết lập header nếu chạy trên web browser
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/html; charset=utf-8');
    echo '<pre style="font-family: Consolas, monospace; font-size: 14px; background: #0f172a; color: #f8fafc; padding: 24px; border-radius: 8px; line-height: 1.6;">';
}

require_once __DIR__ . '/../core/Database.php';

use Core\Database;

function logMessage($msg, $type = 'info') {
    $timestamp = date('H:i:s');
    $color = '';
    $reset = "\033[0m";

    if (php_sapi_name() === 'cli') {
        switch ($type) {
            case 'success': $color = "\033[1;32m"; break;
            case 'warning': $color = "\033[1;33m"; break;
            case 'error':   $color = "\033[1;31m"; break;
            case 'cyan':    $color = "\033[1;36m"; break;
            default:        $color = "\033[0m"; break;
        }
        echo "{$color}[{$timestamp}] {$msg}{$reset}\n";
    } else {
        $htmlColors = [
            'success' => '#4ade80',
            'warning' => '#facc15',
            'error'   => '#f87171',
            'cyan'    => '#38bdf8',
            'info'    => '#e2e8f0',
        ];
        $c = $htmlColors[$type] ?? '#e2e8f0';
        echo "<span style=\"color: {$c}; font-weight: 600;\">[{$timestamp}] " . htmlspecialchars($msg) . "</span>\n";
    }
}

echo "\n====================================================================\n";
logMessage("BẮT ĐẦU DEMO: Transaction Wrapper & Tự động Retry khi gặp Deadlock", 'cyan');
logMessage("Thành viên phụ trách: Trọng Phú (Core & System)", 'cyan');
echo "====================================================================\n\n";

logMessage("1. Kiểm tra kết nối Cơ sở dữ liệu qua Core\\Database::getConnection()...");
try {
    $pdo = Database::getConnection();
    logMessage("-> Kết nối CSDL MySQL thành công!", 'success');
} catch (Throwable $e) {
    logMessage("-> Lỗi kết nối CSDL: " . $e->getMessage(), 'error');
    exit(1);
}

logMessage("\n2. Khởi tạo Giao dịch thông qua Core\\Database::transaction(callable, maxRetries=3)...");
logMessage("   Kịch bản kiểm thử: Giả lập 2 lần đầu bị xung đột Deadlock (SQLSTATE 40001 / Error 1213).");
logMessage("   Mục tiêu: Chứng minh hệ thống tự động Backoff, Rollback và Retry mà KHÔNG bị crash ứng dụng.\n");

$attemptCounter = 0;

try {
    $result = Database::transaction(function (PDO $conn) use (&$attemptCounter) {
        $attemptCounter++;

        logMessage("   [Attempt #{$attemptCounter}] Bắt đầu giao dịch con (beginTransaction)...");

        if ($attemptCounter < 3) {
            logMessage("   [Attempt #{$attemptCounter}] ⚠️ Giả lập xung đột Deadlock (Wait-For Graph phát hiện chu trình)!", 'warning');
            
            // Khởi tạo ngoại lệ PDOException đúng chuẩn lỗi Deadlock của MySQL
            $deadlockException = new PDOException(
                "Deadlock found when trying to get lock; try restarting transaction",
                40001
            );
            $deadlockException->errorInfo = [
                '40001',
                1213,
                'Deadlock found when trying to get lock; try restarting transaction'
            ];

            logMessage("   [Attempt #{$attemptCounter}] Ném ngoại lệ PDOException (SQLSTATE: 40001, Driver Error: 1213)", 'error');
            throw $deadlockException;
        }

        // Lần thứ 3: Xử lý thành công
        logMessage("   [Attempt #{$attemptCounter}] 🎉 Không còn xung đột khóa. Thực thi logic nghiệp vụ...", 'success');
        
        $stmt = $conn->query("SELECT COUNT(*) AS total_employees FROM employees WHERE deleted_at IS NULL");
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        logMessage("   [Attempt #{$attemptCounter}] Đã đọc dữ liệu an toàn (Tổng số nhân viên active: {$data['total_employees']}).", 'info');
        logMessage("   [Attempt #{$attemptCounter}] Chuẩn bị Commit giao dịch...", 'success');

        return [
            'status' => 'success',
            'attempts_needed' => $attemptCounter,
            'total_active_employees' => $data['total_employees']
        ];
    }, 3, 100);

    echo "\n====================================================================\n";
    logMessage("KẾT QUẢ CUỐI CÙNG:", 'cyan');
    logMessage("✔ Giao dịch thành công sau: {$result['attempts_needed']} lần thực thi (2 lần retry tự động)", 'success');
    logMessage("✔ Hệ thống KHÔNG bị dừng đột ngột (crash), toàn vẹn dữ liệu được đảm bảo 100%!", 'success');
    logMessage("✔ Đúng chuẩn yêu cầu kỹ thuật của kịch bản thuyết trình!", 'success');
    echo "====================================================================\n\n";

} catch (Throwable $e) {
    logMessage("❌ Giao dịch thất bại: " . $e->getMessage(), 'error');
}

if (php_sapi_name() !== 'cli') {
    echo '</pre>';
}
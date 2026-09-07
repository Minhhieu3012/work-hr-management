<?php

namespace Core;

use PDO;
use PDOException;
use Throwable;

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
        $port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';
        $db   = $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: 'work_hr_management';
        $user = $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?: 'root';
        $pass = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '';

        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->connection = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            error_log("DB Connection Error: " . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            die(json_encode([
                "status" => "error",
                "message" => "Database connection failed: " . $e->getMessage()
            ]));
        }
    }

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance->connection;
    }

    /**
     * Transaction Wrapper với cơ chế tự động Retry khi gặp Deadlock.
     *
     * Cách dùng:
     *   $result = Database::transaction(function (PDO $pdo) {
     *       $pdo->prepare(...)->execute(...);
     *       return $someValue;
     *   });
     *
     * @param callable $callback    Nhận PDO làm tham số, trả về giá trị bất kỳ.
     * @param int $maxRetries       Số lần thử lại tối đa khi gặp deadlock (mặc định 3).
     * @param int $baseDelayMs      Thời gian chờ cơ bản giữa các lần retry (ms), tăng gấp đôi mỗi lần (exponential backoff).
     * @return mixed                Giá trị trả về từ $callback.
     * @throws Throwable            Ném lại lỗi gốc nếu không phải deadlock, hoặc đã hết số lần retry.
     */
    public static function transaction(callable $callback, int $maxRetries = 3, int $baseDelayMs = 100) {
        $pdo = self::getConnection();

        // Nếu đang ở trong 1 transaction khác gọi lồng vào (nested call) ->
        // không mở transaction mới (PDO không hỗ trợ nested transaction thật sự),
        // chạy trực tiếp trong transaction cha đang có.
        if ($pdo->inTransaction()) {
            return $callback($pdo);
        }

        $attempt = 0;

        while (true) {
            $attempt++;

            try {
                $pdo->beginTransaction();

                $result = $callback($pdo);

                $pdo->commit();

                return $result;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                if (self::isDeadlock($e) && $attempt < $maxRetries) {
                    $delayMicroseconds = $baseDelayMs * 1000 * (2 ** ($attempt - 1));
                    usleep($delayMicroseconds);
                    continue; // thử lại toàn bộ $callback từ đầu
                }

                throw $e; // không phải deadlock, hoặc hết lượt retry -> ném lại lỗi gốc
            }
        }
    }

    /**
     * Kiểm tra 1 exception có phải do MySQL deadlock / lock wait timeout không.
     * - SQLSTATE 40001: Deadlock found when trying to get lock.
     * - Mã lỗi 1213: ER_LOCK_DEADLOCK.
     * - Mã lỗi 1205: ER_LOCK_WAIT_TIMEOUT (chờ khoá quá lâu, cũng nên retry).
     */
    private static function isDeadlock(Throwable $e): bool {
        if (!$e instanceof PDOException) {
            return false;
        }

        $sqlState = $e->errorInfo[0] ?? (string)$e->getCode();
        $driverErrorCode = isset($e->errorInfo[1]) ? (int)$e->errorInfo[1] : null;

        return $sqlState === '40001' || in_array($driverErrorCode, [1213, 1205], true);
    }
}
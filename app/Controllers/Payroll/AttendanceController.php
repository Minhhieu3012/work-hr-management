<?php

namespace App\Controllers\Payroll;

use Core\Database;
use App\Middleware\AuthMiddleware;
use Exception;
use PDO;

class AttendanceController
{
    private $authUser;
    private $pdo;

    public function __construct($authUser = null)
    {
        $this->pdo = Database::getConnection();

        if ($authUser) {
            $this->authUser = $authUser;
        } else {
            try {
                $this->authUser = AuthMiddleware::check();
            } catch (Exception $e) {
                $this->authUser = null;
            }
        }
    }

    public function index()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $this->ensureAuth();

            $emp_id = $this->authUser['id']
                ?? $this->authUser['employee_id']
                ?? null;

            if (!$emp_id) {
                throw new Exception(
                    'Không tìm thấy ID người dùng.'
                );
            }

            $month = date('m');
            $year = date('Y');

            $stmt = $this->pdo->prepare("
                SELECT *
                FROM attendances
                WHERE employee_id = ?
                  AND MONTH(work_date) = ?
                  AND YEAR(work_date) = ?
                ORDER BY work_date DESC
            ");

            $stmt->execute([
                $emp_id,
                $month,
                $year
            ]);

            $history = $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );

            $stats = [
                'total_days' => count($history),
                'on_time' => 0,
                'late' => 0,
                'missing_out' => 0
            ];

            foreach ($history as $row) {
                if ($row['status'] === 'Present') {
                    $stats['on_time']++;
                }

                if ($row['status'] === 'Late') {
                    $stats['late']++;
                }

                if (empty($row['check_out_time'])) {
                    $stats['missing_out']++;
                }
            }

            $today_date = date('Y-m-d');
            $today_record = null;

            foreach ($history as $record) {
                if (
                    $record['work_date']
                    === $today_date
                ) {
                    $today_record = $record;
                    break;
                }
            }

            echo json_encode([
                'status' => 'success',
                'data' => [
                    'history' => $history,
                    'stats' => $stats,
                    'today' => $today_record
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(401);

            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function checkin()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $this->ensureAuth();

            $emp_id = $this->authUser['id']
                ?? $this->authUser['employee_id']
                ?? null;

            if (!$emp_id) {
                throw new Exception(
                    'Không tìm thấy ID người dùng!'
                );
            }

            date_default_timezone_set(
                'Asia/Ho_Chi_Minh'
            );

            $today = date('Y-m-d');
            $now_datetime = date('Y-m-d H:i:s');

            $stmtCheck = $this->pdo->prepare("
                SELECT id
                FROM attendances
                WHERE employee_id = ?
                  AND work_date = ?
            ");

            $stmtCheck->execute([
                $emp_id,
                $today
            ]);

            if ($stmtCheck->fetch()) {
                throw new Exception(
                    'Bạn đã check-in ngày hôm nay rồi!'
                );
            }

            $start_time_limit =
                date('Y-m-d 08:30:00');

            $status =
                ($now_datetime > $start_time_limit)
                ? 'Late'
                : 'Present';

            $stmt = $this->pdo->prepare("
                INSERT INTO attendances
                (
                    employee_id,
                    work_date,
                    check_in_time,
                    status
                )
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([
                $emp_id,
                $today,
                $now_datetime,
                $status
            ]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Check-in thành công!',
                'data' => [
                    'status' => $status
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(400);

            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function checkout()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $this->ensureAuth();

            $emp_id = $this->authUser['id']
                ?? $this->authUser['employee_id']
                ?? null;

            if (!$emp_id) {
                throw new Exception(
                    'Không tìm thấy ID người dùng!'
                );
            }

            date_default_timezone_set(
                'Asia/Ho_Chi_Minh'
            );

            $today = date('Y-m-d');
            $now_datetime = date('Y-m-d H:i:s');

            $stmtCheck = $this->pdo->prepare("
                SELECT
                    id,
                    check_in_time,
                    check_out_time
                FROM attendances
                WHERE employee_id = ?
                  AND work_date = ?
            ");

            $stmtCheck->execute([
                $emp_id,
                $today
            ]);

            $record = $stmtCheck->fetch(
                PDO::FETCH_ASSOC
            );

            if (!$record) {
                throw new Exception(
                    'Bạn chưa check-in ngày hôm nay!'
                );
            }

            if (!empty($record['check_out_time'])) {
                throw new Exception(
                    'Bạn đã check-out rồi!'
                );
            }

            $in_time = strtotime(
                $record['check_in_time']
            );

            $out_time = strtotime(
                $now_datetime
            );

            $work_hours = max(
                0,
                round(
                    (
                        $out_time
                        - $in_time
                        - 5400
                    ) / 3600,
                    2
                )
            );

            $stmtUpdate = $this->pdo->prepare("
                UPDATE attendances
                SET check_out_time = ?
                WHERE id = ?
            ");

            $stmtUpdate->execute([
                $now_datetime,
                $record['id']
            ]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Check-out thành công!',
                'data' => [
                    'work_hours' => $work_hours
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(400);

            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function calculateMonthlyPayroll()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $this->ensureAuth();

            $role = strtolower(
                $this->authUser['role'] ?? 'employee'
            );

            if (
                $role !== 'admin'
                && $role !== 'manager'
            ) {
                throw new Exception(
                    'Bạn không có quyền tính bảng lương.'
                );
            }

            $data = json_decode(
                file_get_contents('php://input'),
                true
            );

            $month = intval(
                $data['month'] ?? date('m')
            );

            $year = intval(
                $data['year'] ?? date('Y')
            );

            if ($month < 1 || $month > 12) {
                throw new Exception(
                    'Tháng không hợp lệ.'
                );
            }

            if ($year < 2000 || $year > 2100) {
                throw new Exception(
                    'Năm không hợp lệ.'
                );
            }

            $stmt = $this->pdo->prepare(
                'CALL sp_CalculateMonthlyPayroll(?, ?)'
            );

            $stmt->execute([
                $month,
                $year
            ]);

            $result = $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );

            while ($stmt->nextRowset()) {
            }

            $stmt->closeCursor();

            echo json_encode([
                'status' => 'success',
                'message' =>
                    "Đã tính bảng lương tháng "
                    . "{$month}/{$year}.",
                'data' => $result ?: []
            ]);
        } catch (Exception $e) {
            http_response_code(400);

            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    private function ensureAuth()
    {
        if (!$this->authUser) {
            throw new Exception(
                'Vui lòng đăng nhập lại.'
            );
        }
    }
}
<?php

namespace App\Controllers\Payroll;

use Core\Database;
use App\Middleware\AuthMiddleware;
use App\Models\Payroll\EmployeeLeaveAdjustment;
use Exception;
use PDO;

class LeaveController
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
                throw new Exception('Không tìm thấy nhân viên.');
            }

            $stmtEmp = $this->pdo->prepare("
                SELECT remaining_leave_days
                FROM employees
                WHERE id = ?
            ");

            $stmtEmp->execute([$emp_id]);
            $balance = $stmtEmp->fetchColumn();

            $stmtHistory = $this->pdo->prepare("
                SELECT *
                FROM leave_requests
                WHERE employee_id = ?
                ORDER BY created_at DESC
            ");

            $stmtHistory->execute([$emp_id]);
            $history = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'data' => [
                    'balance' => $balance !== false ? floatval($balance) : 0,
                    'history' => $history ?: []
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

    public function adminIndex()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $this->ensureAuth();

            $role = strtolower(
                $this->authUser['role'] ?? 'employee'
            );

            if ($role === 'employee') {
                throw new Exception(
                    'Bạn không có quyền truy cập trung tâm phê duyệt.'
                );
            }

            $stmt = $this->pdo->prepare("
                SELECT lr.*, e.full_name AS employee_name
                FROM leave_requests lr
                JOIN employees e ON lr.employee_id = e.id
                WHERE lr.status = 'Pending'
                ORDER BY lr.created_at ASC
            ");

            $stmt->execute();

            $pendingLeaves = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'data' => $pendingLeaves ?: []
            ]);
        } catch (Exception $e) {
            http_response_code(403);

            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function store()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $this->ensureAuth();

            $emp_id = $this->authUser['id']
                ?? $this->authUser['employee_id']
                ?? null;

            if (!$emp_id) {
                throw new Exception(
                    'Không tìm thấy ID người dùng trong phiên làm việc.'
                );
            }

            $data = json_decode(
                file_get_contents('php://input'),
                true
            );

            $start_date = $data['start_date'] ?? '';
            $end_date = $data['end_date'] ?? '';
            $leave_type = $data['leave_type'] ?? '';
            $duration = floatval($data['duration'] ?? 0);
            $reason = trim($data['reason'] ?? '');

            if (
                empty($start_date)
                || empty($end_date)
                || empty($leave_type)
                || empty($reason)
            ) {
                throw new Exception(
                    'Vui lòng điền đầy đủ thông tin đơn nghỉ phép.'
                );
            }

            if (strtotime($end_date) < strtotime($start_date)) {
                throw new Exception(
                    'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.'
                );
            }

            if ($duration <= 0) {
                throw new Exception(
                    'Số ngày nghỉ phải lớn hơn 0.'
                );
            }

            $allowedTypes = [
                'annual',
                'sick',
                'personal',
                'half_day'
            ];

            if (!in_array($leave_type, $allowedTypes, true)) {
                throw new Exception(
                    'Loại nghỉ phép không hợp lệ.'
                );
            }

            $stmtBalance = $this->pdo->prepare("
                SELECT remaining_leave_days
                FROM employees
                WHERE id = ?
            ");

            $stmtBalance->execute([$emp_id]);

            $current_balance = floatval(
                $stmtBalance->fetchColumn()
            );

            if ($duration > $current_balance) {
                throw new Exception(
                    "Quỹ phép không đủ. Bạn xin nghỉ {$duration} ngày, "
                    . "nhưng chỉ còn {$current_balance} ngày."
                );
            }

            $stmtInsert = $this->pdo->prepare("
                INSERT INTO leave_requests
                (
                    employee_id,
                    leave_type,
                    start_date,
                    end_date,
                    duration,
                    reason,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?, 'Pending')
            ");

            $stmtInsert->execute([
                $emp_id,
                $leave_type,
                $start_date,
                $end_date,
                $duration,
                $reason
            ]);

            echo json_encode([
                'status' => 'success',
                'message' =>
                    'Gửi đơn nghỉ phép thành công. '
                    . 'Vui lòng chờ quản lý duyệt.'
            ]);
        } catch (Exception $e) {
            http_response_code(400);

            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function approve($id)
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $this->ensureAuth();

            $manager_id = $this->authUser['id']
                ?? $this->authUser['employee_id']
                ?? null;

            if (!$manager_id) {
                throw new Exception(
                    'Không tìm thấy thông tin người duyệt.'
                );
            }

            $role = strtolower(
                $this->authUser['role'] ?? 'employee'
            );

            if ($role === 'employee') {
                throw new Exception(
                    'Bạn không có quyền duyệt đơn.'
                );
            }

            $data = json_decode(
                file_get_contents('php://input'),
                true
            );

            $action = $data['action'] ?? '';

            if (!in_array(
                $action,
                ['Approved', 'Rejected'],
                true
            )) {
                throw new Exception(
                    'Trạng thái xử lý không hợp lệ.'
                );
            }

            $this->pdo->beginTransaction();

            $stmtReq = $this->pdo->prepare("
                SELECT *
                FROM leave_requests
                WHERE id = ?
                FOR UPDATE
            ");

            $stmtReq->execute([$id]);

            $request = $stmtReq->fetch(PDO::FETCH_ASSOC);

            if (!$request) {
                throw new Exception(
                    'Đơn nghỉ phép không tồn tại.'
                );
            }

            if ($request['status'] !== 'Pending') {
                throw new Exception(
                    'Đơn nghỉ phép đã được xử lý trước đó.'
                );
            }

            if ($action === 'Rejected') {
                $stmtReject = $this->pdo->prepare("
                    UPDATE leave_requests
                    SET status = 'Rejected',
                        approved_by = ?
                    WHERE id = ?
                ");

                $stmtReject->execute([
                    $manager_id,
                    $id
                ]);

                $this->pdo->commit();

                echo json_encode([
                    'status' => 'success',
                    'message' =>
                        'Đã từ chối đơn nghỉ phép.'
                ]);

                return;
            }

            $emp_id = $request['employee_id'];
            $days = floatval($request['duration']);

            $stmtEmp = $this->pdo->prepare("
                SELECT remaining_leave_days
                FROM employees
                WHERE id = ?
                FOR UPDATE
            ");

            $stmtEmp->execute([$emp_id]);

            $old_days = $stmtEmp->fetchColumn();

            if ($old_days === false) {
                throw new Exception(
                    'Không tìm thấy nhân viên.'
                );
            }

            $old_days = floatval($old_days);

            if ($old_days < $days) {
                throw new Exception(
                    'Nhân viên hiện không đủ ngày phép '
                    . 'để thực hiện phê duyệt.'
                );
            }

            $new_days = $old_days - $days;

            $stmtApprove = $this->pdo->prepare("
                UPDATE leave_requests
                SET status = 'Approved',
                    approved_by = ?
                WHERE id = ?
            ");

            $stmtApprove->execute([
                $manager_id,
                $id
            ]);

            $stmtEmployee = $this->pdo->prepare("
                UPDATE employees
                SET remaining_leave_days = ?
                WHERE id = ?
            ");

            $stmtEmployee->execute([
                $new_days,
                $emp_id
            ]);

            $adjustmentModel =
                new EmployeeLeaveAdjustment($this->pdo);

            $adjustmentModel->create(
                $emp_id,
                -$days,
                $old_days,
                $new_days,
                "Hệ thống trừ phép do duyệt đơn #{$id}",
                $manager_id
            );

            $this->pdo->commit();

            echo json_encode([
                'status' => 'success',
                'message' =>
                    "Đã phê duyệt và khấu trừ "
                    . "{$days} ngày phép thành công."
            ]);
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

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
                'Phiên đăng nhập đã hết hạn hoặc không hợp lệ.'
            );
        }
    }
}
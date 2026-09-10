<?php

namespace App\Controllers\HRM;

use App\Models\HRM\Employee;

class EmployeeActionController
{
    protected $employeeModel;

    public function __construct()
    {
        $this->employeeModel = new Employee();
    }

    public function terminate($id): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $reason = $input['reason'] ?? 'Nghỉ việc';

        try {
            $success = $this->employeeModel->softDeleteWithContractTermination((int)$id, $reason);

            header('Content-Type: application/json');
            if ($success) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Đã chuyển nhân viên sang trạng thái nghỉ việc và chấm dứt các hợp đồng liên quan.'
                ]);
                return;
            }

            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Xử lý sa thải thất bại.']);
        } catch (\Throwable $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
<?php
namespace App\Controllers\HRM;

use App\Models\HRM\EmployeeContract;
use PDO;

class EmployeeContractController {
    private $contractModel;

    public function __construct(PDO $db) {
        $this->contractModel = new EmployeeContract($db);
    }

    public function index() {
        $contracts = $this->contractModel->getAllWithDynamicStatus();
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 200,
            'message' => 'Lấy danh sách hợp đồng thành công',
            'data' => $contracts
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public function destroy($id) {
        header('Content-Type: application/json');

        if (!is_numeric($id) || $id <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'error' => 'ID hợp đồng không hợp lệ']);
            exit;
        }

        $isDeleted = $this->contractModel->softDelete($id);

        if ($isDeleted) {
            http_response_code(200);
            echo json_encode(['status' => 200, 'message' => 'Đã xóa/chấm dứt hợp đồng thành công'], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode(['status' => 404, 'error' => 'Không tìm thấy hợp đồng'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

public function updateDepartmentAndRenew($employeeId): void
{
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $departmentId = $input['department_id'] ?? null;
    $contractType = $input['contract_type'] ?? 'fixed_term'; // Hợp lệ: probation, fixed_term, indefinite
    $salary = (float)($input['salary'] ?? 0);

    if (empty($departmentId) || $salary <= 0) {
        header('Content-Type: application/json');
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Dữ liệu phòng ban hoặc mức lương không hợp lệ.']);
        return;
    }

    $contractData = [
        'contract_type' => $contractType,
        'start_date'    => $input['start_date'] ?? date('Y-m-d'),
        'end_date'      => $input['end_date'] ?? null,
        'salary'        => $salary,
    ];

    try {
        $employeeModel = new \App\Models\HRM\Employee();
        $success = $employeeModel->updateDepartmentAndRenewContract((int)$employeeId, (int)$departmentId, $contractData);

        header('Content-Type: application/json');
        if ($success) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Đã cập nhật phòng ban mới và tái ký hợp đồng thành công.'
            ]);
            return;
        }

        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Tái ký hợp đồng thất bại.']);
    } catch (\Throwable $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
}
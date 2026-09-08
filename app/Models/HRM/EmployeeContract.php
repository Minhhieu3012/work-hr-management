<?php
namespace App\Models\HRM;

use Core\Database;
use Exception;
use PDO;

class EmployeeContract {
    private PDO $db;

    public function __construct(?PDO $dbConnection = null) {
        // Cho phép truyền PDO từ bên ngoài (khi đang chạy trong 1 transaction lớn hơn),
        // nếu không sẽ lấy connection singleton mặc định - giữ tương thích ngược với
        // các nơi cũ đang gọi `new EmployeeContract($db)`.
        $this->db = $dbConnection ?? Database::getConnection();
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    private function generateContractCode(int $employeeId): string {
        return 'HD' . date('ymdHis') . '-' . $employeeId . '-' . random_int(100, 999);
    }

    private function validateContractData(array $data): array {
        $type = $data['contract_type'] ?? 'probation';
        if (!in_array($type, ['probation', 'fixed_term', 'indefinite'], true)) {
            throw new Exception('Loại hợp đồng không hợp lệ.');
        }

        $salary = (float)($data['salary'] ?? 0);
        if ($salary <= 0) {
            throw new Exception('Mức lương hợp đồng phải lớn hơn 0.');
        }

        $startDate = !empty($data['start_date']) ? $data['start_date'] : date('Y-m-d');
        $endDate = !empty($data['end_date']) ? $data['end_date'] : null;

        if ($endDate !== null && $endDate < $startDate) {
            throw new Exception('Ngày kết thúc hợp đồng không được trước ngày bắt đầu.');
        }

        return [
            'contract_type' => $type,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'salary' => $salary,
            'note' => isset($data['note']) && trim((string)$data['note']) !== ''
                ? trim((string)$data['note'])
                : null,
        ];
    }

    // ---------------------------------------------------------------------
    // Internal building blocks - LUÔN nhận $pdo từ bên ngoài, KHÔNG tự mở
    // transaction, để có thể tái sử dụng an toàn bên trong 1 transaction lớn hơn
    // (vd Employee::createWithInitialContract, EmployeeController::update()).
    // ---------------------------------------------------------------------

    public function insertContract(PDO $pdo, int $employeeId, array $data): int {
        $prepared = $this->validateContractData($data);
        $contractCode = trim((string)($data['contract_code'] ?? '')) ?: $this->generateContractCode($employeeId);

        $stmt = $pdo->prepare("
            INSERT INTO employee_contracts
                (employee_id, contract_code, contract_type, start_date, end_date, salary, status, note)
            VALUES
                (:employee_id, :contract_code, :contract_type, :start_date, :end_date, :salary, 'active', :note)
        ");

        $stmt->execute([
            ':employee_id'    => $employeeId,
            ':contract_code'  => $contractCode,
            ':contract_type'  => $prepared['contract_type'],
            ':start_date'     => $prepared['start_date'],
            ':end_date'       => $prepared['end_date'],
            ':salary'         => $prepared['salary'],
            ':note'           => $prepared['note'],
        ]);

        return (int)$pdo->lastInsertId();
    }

    /**
     * Khoá (FOR UPDATE) toàn bộ hợp đồng đang active của 1 nhân viên.
     * Bắt buộc phải gọi bên trong 1 transaction đang mở, để tránh 2 giao tác
     * đồng thời (vd Sa thải + Tái ký hợp đồng cùng lúc) cùng sửa 1 bản ghi.
     */
    public function lockActiveContractsForEmployee(PDO $pdo, int $employeeId): array {
        $stmt = $pdo->prepare("
            SELECT id, contract_code, status
            FROM employee_contracts
            WHERE employee_id = :employee_id
              AND status = 'active'
              AND deleted_at IS NULL
            FOR UPDATE
        ");
        $stmt->execute([':employee_id' => $employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function terminateActiveContracts(PDO $pdo, int $employeeId, ?string $reason = null): int {
        $activeContracts = $this->lockActiveContractsForEmployee($pdo, $employeeId);

        if (empty($activeContracts)) {
            return 0;
        }

        $noteSuffix = ' | Chấm dứt: ' . ($reason ?: 'Không rõ lý do');

        $stmt = $pdo->prepare("
            UPDATE employee_contracts
            SET status = 'terminated',
                end_date = COALESCE(end_date, CURRENT_DATE),
                note = CONCAT(COALESCE(note, ''), :note_suffix),
                updated_at = NOW()
            WHERE id = :id
        ");

        $count = 0;
        foreach ($activeContracts as $contract) {
            $stmt->execute([
                ':note_suffix' => $noteSuffix,
                ':id' => $contract['id'],
            ]);
            $count++;
        }

        return $count;
    }

    // ---------------------------------------------------------------------
    // Public entrypoints - tự mở Database::transaction(). Vì wrapper hỗ trợ
    // gọi lồng (nested), các hàm này vẫn AN TOÀN khi được gọi từ bên trong
    // 1 transaction lớn hơn đang mở sẵn (sẽ tự dùng chung transaction đó).
    // ---------------------------------------------------------------------

    /**
     * #23 - Tạo hợp đồng đầu tiên khi tiếp nhận nhân sự mới.
     */
    public function createInitial(int $employeeId, array $contractData): int {
        return Database::transaction(function (PDO $pdo) use ($employeeId, $contractData) {
            return $this->insertContract($pdo, $employeeId, $contractData);
        });
    }

    /**
     * #24 - Chấm dứt toàn bộ hợp đồng active khi nhân viên sa thải/nghỉ việc.
     */
    public function terminateForResignation(int $employeeId, ?string $reason = null): int {
        return Database::transaction(function (PDO $pdo) use ($employeeId, $reason) {
            return $this->terminateActiveContracts($pdo, $employeeId, $reason ?? 'Nhân viên nghỉ việc/bị sa thải');
        });
    }

    /**
     * #25 - Tái ký hợp đồng: chấm dứt hợp đồng active hiện tại (nếu có) rồi tạo hợp đồng mới.
     */
    public function createRenewal(int $employeeId, array $newContractData, ?string $renewalNote = null): int {
        return Database::transaction(function (PDO $pdo) use ($employeeId, $newContractData, $renewalNote) {
            $this->terminateActiveContracts($pdo, $employeeId, $renewalNote ?? 'Tái ký hợp đồng mới');
            return $this->insertContract($pdo, $employeeId, $newContractData);
        });
    }

    // ---------------------------------------------------------------------
    // Các hàm sẵn có - giữ nguyên hành vi cũ
    // ---------------------------------------------------------------------

    // Lấy danh sách hợp đồng kèm thông tin nhân viên và trạng thái động (Dynamic Status)
    public function getAllWithDynamicStatus() {
        $sql = "
            SELECT 
                c.id, c.contract_code, c.contract_type, c.start_date, c.end_date, c.salary, c.status AS original_status, c.note,
                e.full_name, e.employee_code,
                CASE
                    WHEN c.status = 'terminated' THEN 'terminated'
                    WHEN c.end_date IS NOT NULL AND c.end_date < CURRENT_DATE THEN 'expired'
                    WHEN c.end_date IS NOT NULL AND DATEDIFF(c.end_date, CURRENT_DATE) <= 30 THEN 'expiring_soon'
                    ELSE 'active'
                END as dynamic_status
            FROM employee_contracts c
            JOIN employees e ON c.employee_id = e.id
            WHERE c.deleted_at IS NULL
            ORDER BY c.end_date ASC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Xóa mềm hợp đồng không làm Cascade (Bảo toàn lịch sử theo đúng Acceptance Criteria)
    public function softDelete($id) {
        // Đổi mã hợp đồng để giải phóng UNIQUE
        $stmt = $this->db->prepare("SELECT contract_code FROM employee_contracts WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $contract = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$contract) return false;

        $newCode = $contract['contract_code'] . '_del_' . time();

        $deleteStmt = $this->db->prepare("
            UPDATE employee_contracts 
            SET deleted_at = CURRENT_TIMESTAMP, contract_code = :new_code, status = 'terminated' 
            WHERE id = :id
        ");
        return $deleteStmt->execute([
            ':new_code' => $newCode,
            ':id' => $id
        ]);
    }
}

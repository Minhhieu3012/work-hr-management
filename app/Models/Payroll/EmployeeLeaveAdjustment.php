<?php

namespace App\Models\Payroll;

use Core\Database;

class EmployeeLeaveAdjustment
{
    private $pdo;

    public function __construct($pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    public function create(
        $employeeId,
        $adjustmentDays,
        $oldRemainingDays,
        $newRemainingDays,
        $reason,
        $createdBy
    ) {
        $stmt = $this->pdo->prepare("
            INSERT INTO employee_leave_adjustments
            (
                employee_id,
                adjustment_days,
                old_remaining_days,
                new_remaining_days,
                reason,
                created_by
            )
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $employeeId,
            $adjustmentDays,
            $oldRemainingDays,
            $newRemainingDays,
            $reason,
            $createdBy
        ]);
    }
}
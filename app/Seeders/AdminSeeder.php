<?php
namespace App\Seeders;

use Core\Database;
use PDO;
use Throwable;

class AdminSeeder {
    public static function run() {
        try {
            $db = Database::getConnection();

            // 1. Khởi tạo phòng ban mẫu nếu chưa có
            $deptCheck = $db->query("SELECT id FROM departments WHERE deleted_at IS NULL LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            if (!$deptCheck) {
                $db->exec("
                    INSERT INTO departments (id, name, description, status) VALUES 
                    (1, 'Ban Quản Trị Hệ Thống', 'Khối quản trị điều hành và nhân sự', 'active'),
                    (2, 'Phòng Kỹ Thuật & Phần Mềm', 'Khối kỹ thuật và dự án', 'active'),
                    (3, 'Phòng Thử Nghiệm Xóa Mềm', 'Phòng ban trống (để test xóa mềm thành công)', 'active')
                    ON DUPLICATE KEY UPDATE name = VALUES(name)
                ");
                $deptAdminId = 1;
                $deptDevId = 2;
            } else {
                $deptAdminId = (int)$deptCheck['id'];
                $deptDevId = (int)$deptCheck['id'];
            }

            // 2. Khởi tạo chức vụ mẫu nếu chưa có
            $posCheck = $db->query("SELECT id FROM positions WHERE deleted_at IS NULL LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            if (!$posCheck) {
                $db->exec("
                    INSERT INTO positions (id, name, description, status) VALUES 
                    (1, 'System Administrator', 'Quản trị viên toàn hệ thống', 'active'),
                    (2, 'Project Manager', 'Quản lý dự án', 'active'),
                    (3, 'Developer', 'Kỹ sư phần mềm', 'active')
                    ON DUPLICATE KEY UPDATE name = VALUES(name)
                ");
                $posAdminId = 1;
                $posStaffId = 2;
            } else {
                $posAdminId = (int)$posCheck['id'];
                $posStaffId = (int)$posCheck['id'];
            }

            // 3. Danh sách tài khoản mẫu cần đảm bảo tồn tại (Bao gồm admin@gmail.com và các tài khoản demo)
            $accounts = [
                [
                    'code'      => 'ADM-001',
                    'name'      => 'Super Admin',
                    'email'     => 'admin@gmail.com',
                    'password'  => '123456',
                    'role'      => 'admin',
                    'dept_id'   => $deptAdminId,
                    'pos_id'    => $posAdminId,
                ],
                [
                    'code'      => 'DIR-001',
                    'name'      => 'John Director',
                    'email'     => 'director@agency.com',
                    'password'  => 'password123',
                    'role'      => 'admin',
                    'dept_id'   => $deptAdminId,
                    'pos_id'    => $posAdminId,
                ],
                [
                    'code'      => 'ADM-002',
                    'name'      => 'Admin WorkHR',
                    'email'     => 'admin@workhr.vn',
                    'password'  => 'password123',
                    'role'      => 'admin',
                    'dept_id'   => $deptAdminId,
                    'pos_id'    => $posAdminId,
                ],
                [
                    'code'      => 'MNG-001',
                    'name'      => 'Alice Manager',
                    'email'     => 'manager@agency.com',
                    'password'  => 'password123',
                    'role'      => 'manager',
                    'dept_id'   => $deptDevId,
                    'pos_id'    => $posStaffId,
                ],
                [
                    'code'      => 'DEV-001',
                    'name'      => 'Bob Developer',
                    'email'     => 'designer@agency.com',
                    'password'  => 'password123',
                    'role'      => 'employee',
                    'dept_id'   => $deptDevId,
                    'pos_id'    => $posStaffId,
                ],
            ];

            $stmtCheck = $db->prepare("SELECT id FROM employees WHERE email = :email LIMIT 1");
            $stmtInsert = $db->prepare("
                INSERT INTO employees 
                (department_id, position_id, employee_code, full_name, email, password, role, phone, gender, date_of_birth, total_leave_days, remaining_leave_days, status, hire_date)
                VALUES 
                (:dept_id, :pos_id, :code, :name, :email, :password, :role, '0901234567', 'male', '1995-01-01', 12, 12.00, 'active', CURDATE())
            ");
            $stmtUpdate = $db->prepare("
                UPDATE employees 
                SET password = :password, role = :role, status = 'active', deleted_at = NULL 
                WHERE email = :email
            ");

            foreach ($accounts as $acc) {
                $stmtCheck->execute([':email' => $acc['email']]);
                $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                $hash = password_hash($acc['password'], PASSWORD_BCRYPT);

                if (!$existing) {
                    $stmtInsert->execute([
                        ':dept_id'  => $acc['dept_id'],
                        ':pos_id'   => $acc['pos_id'],
                        ':code'     => $acc['code'],
                        ':name'     => $acc['name'],
                        ':email'    => $acc['email'],
                        ':password' => $hash,
                        ':role'     => $acc['role'],
                    ]);
                } else {
                    $stmtUpdate->execute([
                        ':password' => $hash,
                        ':role'     => $acc['role'],
                        ':email'    => $acc['email'],
                    ]);
                }
            }
        } catch (Throwable $e) {
            error_log("AdminSeeder Error: " . $e->getMessage());
        }
    }
}
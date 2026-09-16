USE work_hr_management;

-- 1. Tạo phòng ban mẫu (để thỏa mãn foreign key department_id)
INSERT IGNORE INTO departments (id, name, description, status) VALUES
(1, 'Ban Quản Trị & Công Nghệ', 'Khối quản trị hệ thống và vận hành', 'active'),
(2, 'Phòng Phát Triển Phần Mềm', 'Khối kỹ thuật và dự án', 'active'),
(3, 'Đối Tác & Khách Hàng', 'Dành riêng cho đối tác liên kết', 'active');

-- 2. Tạo chức vụ mẫu (để thỏa mãn foreign key position_id)
INSERT IGNORE INTO positions (id, name, description, status) VALUES
(1, 'System Administrator', 'Quản trị viên toàn hệ thống', 'active'),
(2, 'Project Manager', 'Quản lý dự án và nhân sự', 'active'),
(3, 'Senior Software Engineer', 'Kỹ sư phát triển phần mềm', 'active'),
(4, 'Client Representative', 'Đại diện đối tác/khách hàng', 'active');

-- 3. Tạo 4 tài khoản tương ứng với 4 Role
-- Mật khẩu hash bên dưới tương ứng với chuỗi: password123
INSERT INTO employees (
    id, department_id, position_id, manager_id, employee_code, 
    full_name, email, password, role, phone, 
    gender, date_of_birth, address, total_leave_days, 
    remaining_leave_days, status, hire_date, version
) VALUES
-- Role 1: ADMIN
(
    1, 1, 1, NULL, 'ADM001', 
    'Quản Trị Viên Hệ Thống', 'admin@workhr.vn', 
    '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'admin', '0901000001', 
    'male', '1995-01-01', 'Hồ Chí Minh', 12, 
    12.00, 'active', '2024-01-01', 1
),

-- Role 2: MANAGER (Báo cáo trực tiếp cho Admin id = 1)
(
    2, 2, 2, 1, 'MNG001', 
    'Trần Quản Lý', 'manager@workhr.vn', 
    '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'manager', '0901000002', 
    'male', '1996-05-15', 'Hà Nội', 12, 
    12.00, 'active', '2024-06-01', 1
),

-- Role 3: EMPLOYEE (Báo cáo trực tiếp cho Manager id = 2)
(
    3, 2, 3, 2, 'EMP001', 
    'Nguyễn Nhân Viên', 'employee@workhr.vn', 
    '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'employee', '0901000003', 
    'female', '2000-08-20', 'Đà Nẵng', 12, 
    12.00, 'active', '2025-01-15', 1
),

-- Role 4: CLIENT (Khách hàng truy cập Client Portal)
(
    4, 3, 4, NULL, 'CLI001', 
    'Công Ty Đối Tác A', 'client@workhr.vn', 
    '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'client', '0901000004', 
    'other', '1990-12-12', 'Hồ Chí Minh', 0, 
    0.00, 'active', '2025-03-01', 1
)
ON DUPLICATE KEY UPDATE 
    role = VALUES(role),
    password = VALUES(password);
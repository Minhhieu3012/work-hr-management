USE work_hr_management;

-- Lấy bảng Tiến độ dự án
SELECT * FROM vw_ProjectTaskProgress;

-- Lấy bảng Hồ sơ nhân sự (Lấy 5 dòng đầu)
SELECT * FROM vw_EmployeeProfiles LIMIT 5;

-- Lấy bảng Thống kê nghỉ phép
SELECT * FROM vw_LeaveSummary;



-- 1. Kích hoạt Trigger bằng cách giả lập đổi trạng thái 1 Task có sẵn (VD: task_id = 1)
UPDATE tasks SET status = 'Doing' WHERE id = 1;
UPDATE tasks SET status = 'Review' WHERE id = 1;
UPDATE tasks SET status = 'Done' WHERE id = 1;

-- 2. Truy vấn bảng log để xem kết quả Trigger đã ghi lại
SELECT 
    id, 
    task_id, 
    user_id, 
    action, 
    description, 
    DATE_FORMAT(created_at, '%d/%m/%Y %H:%i:%s') AS created_at 
FROM task_activity_logs 
WHERE action = 'status_change'
ORDER BY id DESC 
LIMIT 5;



-- Bơm dữ liệu giả lập cho bảng leave_requests (Đơn nghỉ phép)
INSERT INTO leave_requests (employee_id, approved_by, start_date, end_date, reason, status)
VALUES 
-- Nhân viên 3 có 2 đơn đã duyệt (Tổng 3 ngày)
(3, 2, DATE_ADD(CURDATE(), INTERVAL -10 DAY), DATE_ADD(CURDATE(), INTERVAL -9 DAY), 'Nghỉ ốm', 'Approved'),
(3, 2, DATE_ADD(CURDATE(), INTERVAL -5 DAY), DATE_ADD(CURDATE(), INTERVAL -5 DAY), 'Việc gia đình', 'Approved'),

-- Nhân viên 4 có 1 đơn đang chờ duyệt (Pending) và 1 đơn đã duyệt (3 ngày)
(4, 2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'Đi du lịch', 'Pending'),
(4, 2, DATE_ADD(CURDATE(), INTERVAL -20 DAY), DATE_ADD(CURDATE(), INTERVAL -18 DAY), 'Khám sức khỏe', 'Approved');
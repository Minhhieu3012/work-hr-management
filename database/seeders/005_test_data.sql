USE work_hr_management;

-- Bơm dữ liệu giả lập cho bảng leave_requests (Đơn nghỉ phép)
INSERT INTO leave_requests
    (employee_id, approved_by, start_date, end_date, reason, status)
VALUES
    (3, 2, DATE_ADD(CURDATE(), INTERVAL -10 DAY), DATE_ADD(CURDATE(), INTERVAL -9 DAY), 'Nghỉ ốm', 'Approved'),
    (3, 2, DATE_ADD(CURDATE(), INTERVAL -7 DAY), DATE_ADD(CURDATE(), INTERVAL -5 DAY), 'Việc gia đình', 'Approved'),
    (3, 2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'Đi du lịch', 'Pending'),
    (3, 2, DATE_ADD(CURDATE(), INTERVAL -20 DAY), DATE_ADD(CURDATE(), INTERVAL -18 DAY), 'Khám sức khoẻ', 'Approved');

SELECT * FROM vw_EmployeeProfiles;
SELECT * FROM vw_LeaveSummary;
SELECT * FROM vw_ProjectTaskProgress;

SELECT id, full_name, hire_date, fn_GetEmployeeSeniority(hire_date) AS seniority_months FROM employees WHERE id = 1;

SELECT fn_CalculateIncomeTax(12000000) AS tax_level_3, fn_CalculateIncomeTax(7000000) AS tax_level_2, fn_CalculateIncomeTax(4000000) AS tax_level_1;

SELECT fn_IsValidDateRange('2026-09-01', '2026-09-30') AS valid_range, fn_IsValidDateRange('2026-09-30', '2026-09-01') AS invalid_range;

INSERT INTO employee_contracts (employee_id, contract_code, contract_type, start_date, end_date, salary, status) VALUES (1, 'HD_FAIL_01', 'probation', '2026-09-30', '2026-09-01', 5000000, 'active');

UPDATE employees SET remaining_leave_days = 20 WHERE id = 1;
UPDATE tasks SET status = 'Doing' WHERE id = 1;
UPDATE tasks SET status = 'Review' WHERE id = 1;
UPDATE tasks SET status = 'Done' WHERE id = 1;
SELECT id, task_id, user_id, action, description, DATE_FORMAT(created_at, '%d/%m/%Y %H:%i:%s') AS created_at FROM task_activity_logs WHERE action = 'status_change' ORDER BY id DESC LIMIT 5;
UPDATE departments SET deleted_at = NOW() WHERE id = 1;
UPDATE positions SET deleted_at = NOW() WHERE id = 1;

CALL sp_OnboardNewEmployee(1, 1, 'EMP_TEST_01', 'Nguyen Van Test', 'test.emp@workhr.vn', 'password_hash_sample', '2026-09-15', 10000000.00);
SELECT * FROM employees WHERE employee_code = 'EMP_TEST_01';
SELECT * FROM employee_contracts WHERE contract_code = 'HD-EMP_TEST_01';
CALL sp_MonthlyLeaveAccrual();
SELECT id, full_name, remaining_leave_days, version FROM employees WHERE status = 'active';
CALL sp_CalculateMonthlyPayroll(9, 2026);
CALL sp_TerminateEmployee(1, '2026-09-16');
SELECT id, status, resigned_date, version FROM employees WHERE id = 1;
SELECT * FROM employee_contracts WHERE employee_id = 1;
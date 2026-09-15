-- view 1: Hồ sơ nhân viên tổng hợp 
CREATE OR REPLACE VIEW vw_EmployeeProfiles AS 
SELECT
    e.id, e.employee_code, e.full_name, e.email, e.phone, 
    d.name AS department_name, p.name AS position_name, 
    CASE e.status
        WHEN 'active' THEN 'Dang lam viec'
        WHEN 'inactive' THEN 'Tam Khoa'
        WHEN 'resigned' THEN 'Da nghi viec'
        ELSE 'Khac'
    END AS status_vi, 
    e.hire_date, e.remaining_leave_days
FROM employee e 
JOIN departments d ON e.department_id = d.id 
JOIN positions p ON e.position_id = p.id; 

-- view 2: Tổng hợp nghỉ phép 
CREATE OR REPLACE VIEW vw_LeaveSummary AS 
SELECT 
    employee_id, 
    COUNT(id) AS total_requests,
    SUM(DATEDIFF(end_date, start_date)+1) AS total_days_requested 
FROM leave_requests 
WHERE status = 'Approved'
GROUP BY employee_id;

-- view 3: Tiến độ dự án 
CREATE OR REPLACE VIEW vw_ProjectTaskProgress AS 
SELECT 
    p.id AS project_id, p.name, 
    COUNT(t.id) AS total_tasks, 
    SUM(CASE WHEN t.status = 'Done' THEN 1 ELSE 0 END) AS completed_tasks, 
    (SUM(CASE WHEN t.status = 'Done' THEN 1 ELSE 0 END) / COUNT(t.id)) * 100 AS progress_percent
FROM projects p 
LEFT JOIN task t ON p.id = t.project_id
GROUP BY p.id, p.name;

-- ========================================
-- ========================================

DELIMITER $$ 

-- Func 1: Tính thâm niêm làm việc (tháng)
CREATE FUNCTION fn_GetEmployeeSeniority(emp_hire_date DATE)
RETURNS INT 
DETERMINISTIC
BEGIN 
    DECLARE seniority INT; -- Khai báo biến cục bộ 
    IF emp_hire_date IS NULL THEN -- Cấu trúc điều khiển IF 
        SET seniority = 0; 
    ELSE 
        SET seniority = TIMESTAMPDIFF(MONTH, emp_hire_date, CURDATE());
    END IF; 
    RETURN seniority;
END $$ 

-- Func 2: Tính thuế thu nhập cá nhân (Minh họa)
CREATE FUNCTION fn_CalculateIncomeTax(taxable_income DECIMAL(15,2))
RETURNS DECIMAL(15,2)
DETERMINISTIC
BEGIN 
    DECLARE tax_amount DECIMAL(15,2);
    -- Cấu trúc rẽ nhánh Searched CASE
    SET tax_amount = CASE
		WHEN taxable_income <= 5000000 THEN taxable_income * 0.05
        WHEN taxable_income <= 10000000 THEN (taxable_income * 0.10) - 250000
        ELSE (taxable_income * 0.15) - 750000 
	END;
    RETURN tax_amount; 
END $$

-- Func 3: Kiểm tra hợp lệ chuỗi ngày 
CREATE FUNCTION fn_IsValidDateRange(start_d DATE, end_d DATE)
RETURNS BOOLEAN 
DETERMINISTIC 
BEGIN 
	IF end_d IS NULL OR end_d >= start_d THEN 
		RETURN TRUE; 
	ELSE 
		RETURN FALSE; 
	END IF;
END $$ 

DELIMITER ;
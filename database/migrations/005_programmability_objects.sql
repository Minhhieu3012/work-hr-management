-- View 1: Hồ sơ nhân viên tổng hợp 
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
FROM employees e 
JOIN departments d ON e.department_id = d.id 
JOIN positions p ON e.position_id = p.id; 

-- View 2: Tổng hợp nghỉ phép 
CREATE OR REPLACE VIEW vw_LeaveSummary AS 
SELECT 
    employee_id, 
    COUNT(id) AS total_requests,
    SUM(DATEDIFF(end_date, start_date)+1) AS total_days_requested 
FROM leave_requests 
WHERE status = 'Approved'
GROUP BY employee_id;

-- View 3: Tiến độ dự án 
CREATE OR REPLACE VIEW vw_ProjectTaskProgress AS 
SELECT 
    p.id AS project_id, p.name, 
    COUNT(t.id) AS total_tasks, 
    SUM(CASE WHEN t.status = 'Done' THEN 1 ELSE 0 END) AS completed_tasks, 
    (SUM(CASE WHEN t.status = 'Done' THEN 1 ELSE 0 END) / COUNT(t.id)) * 100 AS progress_percent
FROM projects p 
LEFT JOIN tasks t ON p.id = t.project_id
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

-- ======================================
-- ======================================

DELIMITER $$

-- Stored Procedures
-- SP 1: Tiếp nhận nhân sự mới 
CREATE PROCEDURE sp_OnboardNewEmployee(
    IN p_dept_id INT, IN p_pos_id INT, IN p_emp_code VARCHAR(50), 
    IN p_full_name VARCHAR(100), IN p_email VARCHAR(100), 
    IN p_password VARCHAR(255), IN p_hire_date DATE
)
BEGIN
    DECLARE v_emp_id INT;
    
    -- CHƯƠNG 2: Khai báo Handler bắt ngoại lệ và tự động ROLLBACK
    DECLARE EXIT HANDLER FOR SQLEXCEPTION 
    BEGIN
        ROLLBACK;
        RESIGNAL; -- Ném lỗi ra ngoài cho tầng PHP (Controller) xử lý
    END;
    
    IF EXISTS (SELECT 1 FROM employees WHERE email = p_email OR employee_code = p_emp_code) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Email hoặc Mã NV đã tồn tại!';
    ELSE
        -- CHƯƠNG 2: Bắt đầu Giao tác để đảm bảo tính Atomicity (Nguyên tố)
        START TRANSACTION;
        
        -- CHƯƠNG 4: Tránh Deadlock bằng Ordering Protocol (Luôn insert bảng cha trước)
        INSERT INTO employees (department_id, position_id, employee_code, full_name, email, password, hire_date)
        VALUES (p_dept_id, p_pos_id, p_emp_code, p_full_name, p_email, p_password, p_hire_date);
        
        SET v_emp_id = LAST_INSERT_ID();
        
        -- (Sau đó mới insert bảng con)
        INSERT INTO employee_contracts (employee_id, contract_code, contract_type, start_date, salary, status)
        VALUES (v_emp_id, CONCAT('HD-', p_emp_code), 'probation', p_hire_date, 5000000, 'active');
        
        -- Chốt Giao tác
        COMMIT;
    END IF;
END$$

-- SP 2: Cấp nhật ngày phép định kỳ (Sử dụng Cursor)
CREATE PROCEDURE sp_MonthlyLeaveAccrual()
BEGIN
    DECLARE v_done INT DEFAULT FALSE;
    DECLARE v_emp_id INT;
    DECLARE v_current_leave DECIMAL(5,2);
    
    -- Khai báo Cursor
    DECLARE cur_emp CURSOR FOR 
        SELECT id, remaining_leave_days FROM employees WHERE status = 'active';
        
    -- Xử lý khi duyệt hết danh sách (Thay thế @@fetch_status)
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = TRUE;
    
    OPEN cur_emp;
    
    read_loop: LOOP
        -- Lấy dữ liệu từ Cursor
        FETCH cur_emp INTO v_emp_id, v_current_leave;
        IF v_done THEN
            LEAVE read_loop;
        END IF;
        
        -- Cập nhật +1 ngày phép mỗi tháng, khóa dòng để tránh Lost Update (Chương 3)
        UPDATE employees 
        SET remaining_leave_days = remaining_leave_days + 1,
            version = version + 1
        WHERE id = v_emp_id AND remaining_leave_days < total_leave_days;
    END LOOP;
    
    CLOSE cur_emp;
END$$

-- SP 3: Sa thải nhân viên
CREATE PROCEDURE sp_TerminateEmployee(IN p_emp_id INT, IN p_resign_date DATE)
BEGIN
    -- CHƯƠNG 2: Khai báo Handler tự động ROLLBACK nếu có lỗi
    DECLARE EXIT HANDLER FOR SQLEXCEPTION 
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    -- CHƯƠNG 2: Bắt đầu giao tác
    START TRANSACTION;
    
    -- CHƯƠNG 4: Áp dụng Strict Ordering Protocol chống Deadlock
    -- BƯỚC 1: Luôn khóa và cập nhật bảng 'employees' trước
    UPDATE employees 
    SET status = 'resigned', resigned_date = p_resign_date, version = version + 1
    WHERE id = p_emp_id AND status != 'resigned';
    
    -- BƯỚC 2: Khóa và cập nhật bảng 'employee_contracts' sau
    UPDATE employee_contracts 
    SET status = 'terminated', end_date = p_resign_date
    WHERE employee_id = p_emp_id AND status = 'active';
    
    -- Hoàn tất
    COMMIT;
END$$

DELIMITER ;

-- =====================================
-- =====================================

DELIMITER $$

-- Trigger 1: Bắt lỗi logic ngày hợp đồng trước khi Insert
CREATE TRIGGER trg_ValidateContractDates_Insert
BEFORE INSERT ON employee_contracts
FOR EACH ROW
BEGIN
    IF NOT fn_IsValidDateRange(NEW.start_date, NEW.end_date) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ngày kết thúc hợp đồng phải lớn hơn ngày bắt đầu!';
    END IF;
END$$

-- Trigger 2: Ngăn chặn cập nhật sai số ngày phép
CREATE TRIGGER trg_CheckLeaveDays
BEFORE UPDATE ON employees
FOR EACH ROW
BEGIN
    IF NEW.remaining_leave_days > NEW.total_leave_days THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Số ngày phép còn lại không được vượt quá tổng ngày phép!';
    END IF;
END$$

-- Trigger 3: Ghi nhận lịch sử tự động khi cập nhật trạng thái Task
CREATE TRIGGER trg_TaskStatusHistory
AFTER UPDATE ON tasks
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO task_activity_logs (task_id, user_id, action, description)
        VALUES (NEW.id, NEW.assigner_id, 'status_change', CONCAT('Trạng thái chuyển từ ', OLD.status, ' sang ', NEW.status));
    END IF;
END$$

DELIMITER ;
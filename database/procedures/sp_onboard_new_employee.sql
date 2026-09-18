DROP PROCEDURE IF EXISTS sp_OnboardNewEmployee;

DELIMITER $$

CREATE PROCEDURE sp_OnboardNewEmployee(
    IN p_dept_id INT, 
    IN p_pos_id INT, 
    IN p_emp_code VARCHAR(50), 
    IN p_full_name VARCHAR(100), 
    IN p_email VARCHAR(100), 
    IN p_password VARCHAR(255), 
    IN p_hire_date DATE,
    IN p_salary DECIMAL(15,2)
)
BEGIN
    DECLARE v_emp_id INT;
    
    -- Xử lý ngoại lệ để đảm bảo Tính nguyên tố (Atomicity)
    -- Nếu trùng lặp Unique Key (mã lỗi 1062 - ví dụ trùng email hoặc mã NV)
    DECLARE EXIT HANDLER FOR 1062
    BEGIN
        ROLLBACK; -- Quay lui, hủy toàn bộ thao tác trước đó
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Email hoặc Mã NV đã tồn tại!';
    END;
    
    -- Bắt toàn bộ các lỗi ngoại lệ SQL khác
    DECLARE EXIT HANDLER FOR SQLEXCEPTION 
    BEGIN
        ROLLBACK;
        RESIGNAL; 
    END;
    
    -- Bắt đầu giao tác
    START TRANSACTION;
    
    -- Kiểm tra điều kiện đầu vào (hoặc để ràng buộc chk_contract_salary tự kích hoạt)
    IF p_salary IS NULL OR p_salary <= 0 THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Lương hợp đồng phải lớn hơn 0 (Vi phạm ràng buộc chk_contract_salary)! Giao dịch tự động ROLLBACK.';
    END IF;
    
    -- Bước 1: Tạo nhân viên
    INSERT INTO employees (department_id, position_id, employee_code, full_name, email, password, hire_date)
    VALUES (p_dept_id, p_pos_id, p_emp_code, p_full_name, p_email, p_password, p_hire_date);
    
    SET v_emp_id = LAST_INSERT_ID();
    
    -- Bước 2: Tạo hợp đồng thử việc tự động
    INSERT INTO employee_contracts (employee_id, contract_code, contract_type, start_date, salary, status)
    VALUES (v_emp_id, CONCAT('HD-', p_emp_code), 'probation', p_hire_date, p_salary, 'active');
    
    -- Chốt giao tác thành công
    COMMIT;
END$$

DELIMITER ;
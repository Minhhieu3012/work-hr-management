-- =======================================================
-- Object: Stored Procedure sp_CalculateMonthlyPayroll
-- Mô tả: Tính lương hàng tháng theo số ngày công thực tế bằng Cursor
-- Áp dụng: Chương 1 (Toàn bộ chu trình CURSOR từ Slide 24 đến 27)
-- =======================================================

DROP PROCEDURE IF EXISTS sp_CalculateMonthlyPayroll;

DELIMITER $$

CREATE PROCEDURE sp_CalculateMonthlyPayroll(IN p_month INT, IN p_year INT)
BEGIN
    -- 1. Khai báo biến cờ kết thúc duyệt
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_emp_id INT;
    DECLARE v_base_salary DECIMAL(15,2);
    DECLARE v_total_present INT;
    DECLARE v_calculated_salary DECIMAL(15,2);
    
    -- ÁP DỤNG CHƯƠNG 1 (Slide 25): Khai báo Cursor
    DECLARE cur_employees CURSOR FOR 
        SELECT e.id, c.salary 
        FROM employees e
        JOIN employee_contracts c ON e.id = c.employee_id
        WHERE e.status = 'active' AND c.status = 'active';
        
    -- Xử lý ngoại lệ khi Cursor duyệt hết dòng (Tương đương @@fetch_status <> 0 ở Slide 26)
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    -- ÁP DỤNG CHƯƠNG 1 (Slide 26): Mở Cursor
    OPEN cur_employees;
    
    -- Bắt đầu vòng lặp duyệt từng dòng dữ liệu
    read_loop: LOOP
        -- ÁP DỤNG CHƯƠNG 1 (Slide 26): Đọc dữ liệu từ Cursor vào biến
        FETCH cur_employees INTO v_emp_id, v_base_salary;
        
        IF done THEN
            LEAVE read_loop; -- Thoát vòng lặp
        END IF;
        
        -- Tính số ngày đi làm thực tế trong tháng từ bảng chấm công
        SELECT COUNT(id) INTO v_total_present
        FROM attendances
        WHERE employee_id = v_emp_id 
          AND MONTH(work_date) = p_month 
          AND YEAR(work_date) = p_year
          AND status = 'Present';
          
        -- Nghiệp vụ: Công chuẩn 22 ngày/tháng
        SET v_calculated_salary = (v_base_salary / 22) * v_total_present;
        
        -- Trả về bảng kết quả tính toán
        SELECT v_emp_id AS employee_id, 
               v_base_salary AS base_salary, 
               v_total_present AS worked_days, 
               v_calculated_salary AS final_salary;
               
    END LOOP;
    
    -- ÁP DỤNG CHƯƠNG 1 (Slide 26): Đóng Cursor để giải phóng tài nguyên
    CLOSE cur_employees;
END$$

DELIMITER ;
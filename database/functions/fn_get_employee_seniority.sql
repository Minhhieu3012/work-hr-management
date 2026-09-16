-- =======================================================
-- Object: Function fn_GetEmployeeSeniority
-- Mô tả: Tính thâm niên làm việc của nhân viên ra số tháng
-- Áp dụng: Chương 1 (Khai báo biến DECLARE, Rẽ nhánh IF...ELSE)
-- =======================================================

DROP FUNCTION IF EXISTS fn_GetEmployeeSeniority;

DELIMITER $$ 

CREATE FUNCTION fn_GetEmployeeSeniority(emp_hire_date DATE)
RETURNS INT 
NOT DETERMINISTIC NO SQL
BEGIN 
    -- ÁP DỤNG CHƯƠNG 1 (Slide 4): Khai báo biến cục bộ
    DECLARE seniority INT; 
    
    -- ÁP DỤNG CHƯƠNG 1 (Slide 10): Cấu trúc rẽ nhánh IF...ELSE
    IF emp_hire_date IS NULL THEN 
        SET seniority = 0; 
    ELSE 
        SET seniority = TIMESTAMPDIFF(MONTH, emp_hire_date, CURDATE());
    END IF; 
    
    RETURN seniority;
END $$ 

DELIMITER ;
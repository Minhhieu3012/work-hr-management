DROP FUNCTION IF EXISTS fn_GetEmployeeSeniority;

DELIMITER $$ 

CREATE FUNCTION fn_GetEmployeeSeniority(emp_hire_date DATE)
RETURNS INT 
NOT DETERMINISTIC NO SQL
BEGIN 
    -- Khai báo biến cục bộ
    DECLARE seniority INT; 
    
    -- Cấu trúc rẽ nhánh IF...ELSE
    IF emp_hire_date IS NULL THEN 
        SET seniority = 0; 
    ELSE 
        SET seniority = TIMESTAMPDIFF(MONTH, emp_hire_date, CURDATE());
    END IF; 
    
    RETURN seniority;
END $$ 

DELIMITER ;
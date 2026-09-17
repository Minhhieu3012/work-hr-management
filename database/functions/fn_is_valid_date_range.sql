DROP FUNCTION IF EXISTS fn_IsValidDateRange;

DELIMITER $$ 

CREATE FUNCTION fn_IsValidDateRange(start_d DATE, end_d DATE)
RETURNS BOOLEAN 
DETERMINISTIC 
BEGIN 
    -- Toán tử so sánh logic kết hợp IF...ELSE
    IF end_d IS NULL OR end_d >= start_d THEN 
        RETURN TRUE; 
    ELSE 
        RETURN FALSE; 
    END IF;
END $$ 

DELIMITER ;
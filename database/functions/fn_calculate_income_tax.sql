-- =======================================================
-- Object: Function fn_CalculateIncomeTax
-- Mô tả: Tính thuế thu nhập cá nhân theo biểu thuế lũy tiến từng phần
-- Áp dụng: Chương 1 (Cấu trúc rẽ nhánh Searched CASE - Slide 16)
-- =======================================================

DROP FUNCTION IF EXISTS fn_CalculateIncomeTax;

DELIMITER $$ 

CREATE FUNCTION fn_CalculateIncomeTax(taxable_income DECIMAL(15,2))
RETURNS DECIMAL(15,2)
DETERMINISTIC
BEGIN 
    DECLARE tax_amount DECIMAL(15,2);
    
    -- ÁP DỤNG CHƯƠNG 1 (Slide 16): Searched CASE gán kết quả tính toán vào biến
    SET tax_amount = CASE
        WHEN taxable_income <= 5000000 THEN taxable_income * 0.05
        WHEN taxable_income <= 10000000 THEN (taxable_income * 0.10) - 250000
        ELSE (taxable_income * 0.15) - 750000 
    END;
    
    RETURN tax_amount; 
END $$

DELIMITER ;
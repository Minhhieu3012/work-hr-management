-- =======================================================
-- Object: Trigger trg_ValidateContractDates_Insert
-- Thời điểm: BEFORE INSERT trên bảng employee_contracts
-- Áp dụng: Chương 1 & Chương 2 (Bảo vệ tính nhất quán logic ngày tháng)
-- =======================================================

DROP TRIGGER IF EXISTS trg_ValidateContractDates_Insert;

DELIMITER $$

CREATE TRIGGER trg_ValidateContractDates_Insert
BEFORE INSERT ON employee_contracts
FOR EACH ROW
BEGIN
    -- Sử dụng lại Function fn_IsValidDateRange đã tạo
    IF NOT fn_IsValidDateRange(NEW.start_date, NEW.end_date) THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Ngày kết thúc hợp đồng phải lớn hơn hoặc bằng ngày bắt đầu!';
    END IF;
END$$

DELIMITER ;
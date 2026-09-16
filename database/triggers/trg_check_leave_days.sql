-- =======================================================
-- Object: Trigger trg_CheckLeaveDays
-- Thời điểm: BEFORE UPDATE trên bảng employees
-- Áp dụng: Chương 2 (Tính nhất quán Consistency - Chặn dữ liệu vô lý)
-- =======================================================

DROP TRIGGER IF EXISTS trg_CheckLeaveDays;

DELIMITER $$

CREATE TRIGGER trg_CheckLeaveDays
BEFORE UPDATE ON employees
FOR EACH ROW
BEGIN
    -- Ngăn chặn cập nhật sai số ngày phép: còn lại không được lớn hơn tổng số được cấp
    IF NEW.remaining_leave_days > NEW.total_leave_days THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Số ngày phép còn lại không được vượt quá tổng ngày phép được hưởng!';
    END IF;
END$$

DELIMITER ;
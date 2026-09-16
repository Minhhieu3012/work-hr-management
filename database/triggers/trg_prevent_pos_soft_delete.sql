-- =======================================================
-- Object: Trigger trg_PreventPosSoftDeleteIfActiveStaff
-- Thời điểm: BEFORE UPDATE trên bảng positions
-- Áp dụng: Tương tự phòng ban, bảo vệ toàn vẹn cho vị trí chức vụ
-- =======================================================

DROP TRIGGER IF EXISTS trg_PreventPosSoftDeleteIfActiveStaff;

DELIMITER $$

CREATE TRIGGER trg_PreventPosSoftDeleteIfActiveStaff
BEFORE UPDATE ON positions
FOR EACH ROW
BEGIN
    IF NEW.deleted_at IS NOT NULL AND OLD.deleted_at IS NULL THEN
        IF EXISTS (
            SELECT 1 FROM employees 
            WHERE position_id = OLD.id 
              AND status = 'active' 
              AND deleted_at IS NULL
        ) THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'Không thể xóa vị trí còn nhân viên đang làm việc!';
        END IF;
    END IF;
END$$

DELIMITER ;
-- =======================================================
-- Object: Trigger trg_PreventDeptSoftDeleteIfActiveStaff
-- Thời điểm: BEFORE UPDATE trên bảng departments
-- Áp dụng: Chương 1 (Toán tử IF EXISTS) & Chương 2 (Toàn vẹn quan hệ khi Xóa mềm)
-- =======================================================

DROP TRIGGER IF EXISTS trg_PreventDeptSoftDeleteIfActiveStaff;

DELIMITER $$

CREATE TRIGGER trg_PreventDeptSoftDeleteIfActiveStaff
BEFORE UPDATE ON departments
FOR EACH ROW
BEGIN
    -- Kiểm tra nếu phòng ban đang chuyển từ "chưa xóa" sang "bị xóa mềm"
    IF NEW.deleted_at IS NOT NULL AND OLD.deleted_at IS NULL THEN
        -- ÁP DỤNG CHƯƠNG 1 (Slide 14): Kết hợp IF với truy vấn EXISTS
        IF EXISTS (
            SELECT 1 FROM employees 
            WHERE department_id = OLD.id 
              AND status = 'active' 
              AND deleted_at IS NULL
        ) THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'Không thể xóa phòng ban còn nhân viên đang làm việc!';
        END IF;
    END IF;
END$$

DELIMITER ;
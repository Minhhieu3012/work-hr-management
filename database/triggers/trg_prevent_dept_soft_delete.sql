DROP TRIGGER IF EXISTS trg_PreventDeptSoftDeleteIfActiveStaff;

DELIMITER $$

CREATE TRIGGER trg_PreventDeptSoftDeleteIfActiveStaff
BEFORE UPDATE ON departments
FOR EACH ROW
BEGIN
    -- Kiểm tra nếu phòng ban đang chuyển từ "chưa xóa" sang "bị xóa mềm"
    IF NEW.deleted_at IS NOT NULL AND OLD.deleted_at IS NULL THEN
        -- Kết hợp IF với truy vấn EXISTS
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
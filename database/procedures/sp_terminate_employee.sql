-- =======================================================
-- Object: Stored Procedure sp_TerminateEmployee
-- Mô tả: Sa thải/Cho nhân viên thôi việc và đóng hợp đồng lao động
-- Áp dụng: 
--   - Chương 1 (Slide 5): Sử dụng biến hệ thống/hàm kiểm tra dòng ảnh hưởng ROW_COUNT()
--   - Chương 2 (Slide 14): Điều khiển giao tác liên bảng
-- =======================================================

DROP PROCEDURE IF EXISTS sp_TerminateEmployee;

DELIMITER $$

CREATE PROCEDURE sp_TerminateEmployee(IN p_emp_id INT, IN p_resign_date DATE)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION 
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;
    
    -- Bước 1: Cập nhật trạng thái nhân viên sang 'resigned'
    UPDATE employees 
    SET status = 'resigned', resigned_date = p_resign_date, version = version + 1
    WHERE id = p_emp_id AND status != 'resigned' AND deleted_at IS NULL;
    
    -- ÁP DỤNG CHƯƠNG 1: Biến @@rowcount (trong MySQL là hàm ROW_COUNT())
    -- Kiểm tra xem lệnh UPDATE trước đó có thực sự tác động lên dòng nào không
    IF ROW_COUNT() = 0 THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Nhân viên không tồn tại hoặc đã nghỉ việc!';
    END IF;
    
    -- Bước 2: Chấm dứt hợp đồng lao động hiện tại
    UPDATE employee_contracts 
    SET status = 'terminated', end_date = p_resign_date
    WHERE employee_id = p_emp_id AND status = 'active' AND deleted_at IS NULL;
    
    COMMIT;
END$$

DELIMITER ;
-- =======================================================
-- Object: Stored Procedure sp_MonthlyLeaveAccrual
-- Mô tả: Tự động cộng 1 ngày phép hàng tháng cho tất cả nhân viên active
-- Áp dụng: Chương 3 (Hỗ trợ Optimistic Locking qua việc tăng version)
-- =======================================================

DROP PROCEDURE IF EXISTS sp_MonthlyLeaveAccrual;

DELIMITER $$

CREATE PROCEDURE sp_MonthlyLeaveAccrual()
BEGIN
    UPDATE employees 
    SET remaining_leave_days = remaining_leave_days + 1,
        -- ÁP DỤNG CHƯƠNG 3_3 (Slide 5-7): Tăng version để thông báo trạng thái bản ghi đã đổi
        version = version + 1
    WHERE status = 'active' 
      AND remaining_leave_days < total_leave_days 
      AND deleted_at IS NULL;
END$$

DELIMITER ;
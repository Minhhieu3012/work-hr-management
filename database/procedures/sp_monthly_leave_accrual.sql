DROP PROCEDURE IF EXISTS sp_MonthlyLeaveAccrual;

DELIMITER $$

CREATE PROCEDURE sp_MonthlyLeaveAccrual()
BEGIN
    UPDATE employees 
    SET remaining_leave_days = remaining_leave_days + 1,
        -- Tăng version để thông báo trạng thái bản ghi đã đổi
        version = version + 1
    WHERE status = 'active' 
      AND remaining_leave_days < total_leave_days 
      AND deleted_at IS NULL;
END$$

DELIMITER ;
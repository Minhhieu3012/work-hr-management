DROP TRIGGER IF EXISTS trg_TaskStatusHistory;

DELIMITER $$

CREATE TRIGGER trg_TaskStatusHistory
AFTER UPDATE ON tasks
FOR EACH ROW
BEGIN
    -- Chỉ ghi log khi trạng thái thực sự thay đổi
    IF OLD.status != NEW.status THEN
        INSERT INTO task_activity_logs (task_id, user_id, action, description)
        VALUES (
            NEW.id, 
            -- Dùng COALESCE lấy người giao việc hoặc người nhận việc làm tác nhân ghi log
            COALESCE(NEW.assigner_id, NEW.assignee_id), 
            'status_change', 
            CONCAT('Trạng thái chuyển từ "', OLD.status, '" sang "', NEW.status, '"')
        );
    END IF;
END$$

DELIMITER ;
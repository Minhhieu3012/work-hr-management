DROP PROCEDURE IF EXISTS sp_Demo_Deadlock_Tx1;
DROP PROCEDURE IF EXISTS sp_Demo_Deadlock_Tx2;
DROP PROCEDURE IF EXISTS sp_Demo_Deadlock_Fix;

DELIMITER $$

-- 1. Giao tác 1: Khóa Task A trước, rồi khóa Task B
CREATE PROCEDURE sp_Demo_Deadlock_Tx1(IN p_task_a INT, IN p_task_b INT)
BEGIN
    START TRANSACTION;
    
    -- Chiếm khóa trên Task A
    UPDATE tasks SET status = 'Doing' WHERE id = p_task_a;
    
    -- Chờ 3 giây để Giao tác 2 kịp chiếm khóa Task B
    DO SLEEP(3);
    
    -- Đòi xin thêm khóa trên Task B (Bị chặn vì Tx2 đang giữ Task B)
    UPDATE tasks SET status = 'Doing' WHERE id = p_task_b;
    
    COMMIT;
END$$

-- 2. Giao tác 2: Khóa Task B trước, rồi khóa Task A (Gây ra Circular Wait)
CREATE PROCEDURE sp_Demo_Deadlock_Tx2(IN p_task_a INT, IN p_task_b INT)
BEGIN
    START TRANSACTION;
    
    -- Chiếm khóa trên Task B
    UPDATE tasks SET status = 'Review' WHERE id = p_task_b;
    
    DO SLEEP(3);
    
    -- Đòi xin thêm khóa trên Task A (Bị chặn vì Tx1 đang giữ Task A)
    -- XUẤT HIỆN CHU TRÌNH DEADLOCK TRÊN ĐỒ THỊ CHỜ!
    UPDATE tasks SET status = 'Review' WHERE id = p_task_a;
    
    COMMIT;
END$$

-- 3. THỦ TỤC KHẮC PHỤC (FIX): Áp dụng Giao thức sắp xếp thứ tự dữ liệu (Chương 4 Slide 5)
-- Dù người dùng truyền id nào trước, ta luôn ép hệ thống khóa ID NHỎ TRƯỚC, ID LỚN SAU!
CREATE PROCEDURE sp_Demo_Deadlock_Fix(
    IN p_task_1 INT, 
    IN p_task_2 INT, 
    IN p_new_status VARCHAR(50)
)
BEGIN
    DECLARE v_first_id INT;
    DECLARE v_second_id INT;
    
    -- ÁP DỤNG CHƯƠNG 4 (Slide 5): Ordering All the Items Protocol
    IF p_task_1 < p_task_2 THEN
        SET v_first_id = p_task_1;
        SET v_second_id = p_task_2;
    ELSE
        SET v_first_id = p_task_2;
        SET v_second_id = p_task_1;
    END IF;
    
    START TRANSACTION;
    
    -- Luôn luôn khóa theo cùng một thứ tự toàn cục cố định
    UPDATE tasks SET status = p_new_status WHERE id = v_first_id;
    DO SLEEP(2);
    UPDATE tasks SET status = p_new_status WHERE id = v_second_id;
    
    COMMIT;
END$$

DELIMITER ;
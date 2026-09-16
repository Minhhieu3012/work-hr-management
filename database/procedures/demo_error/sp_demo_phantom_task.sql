-- ====================================================================
-- Object: Demo Phantom Read & Cách khắc phục
-- Nghiệp vụ: Thống kê số lượng Task trong dự án
-- Áp dụng: Chương 3_1 (Hiện tượng Phantom Slide 23) & Chương 3_3 (Next-Key Lock / SERIALIZABLE)
-- ====================================================================

DROP PROCEDURE IF EXISTS sp_Demo_Phantom_Inserter;
DROP PROCEDURE IF EXISTS sp_Demo_Phantom_Reader_Unsafe;
DROP PROCEDURE IF EXISTS sp_Demo_Phantom_Reader_Fix;

DELIMITER $$

-- 1. Giao tác chèn thêm bản ghi mới giữa chừng
CREATE PROCEDURE sp_Demo_Phantom_Inserter(
    IN p_project_id INT,
    IN p_title VARCHAR(255)
)
BEGIN
    START TRANSACTION;
    INSERT INTO tasks (project_id, title, status, priority, deadline)
    VALUES (p_project_id, p_title, 'To do', 'Medium', CURDATE());
    COMMIT; -- Chèn thành công bản ghi mới
END$$

-- 2. Giao tác đọc lỗi (UNSAFE): Dùng READ COMMITTED thấy xuất hiện dòng "bóng ma"
CREATE PROCEDURE sp_Demo_Phantom_Reader_Unsafe(IN p_project_id INT)
BEGIN
    SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED;
    START TRANSACTION;
    
    -- Lần đếm 1
    SELECT COUNT(id) AS initial_task_count, 'Lan dem 1' AS step
    FROM tasks 
    WHERE project_id = p_project_id;
    
    -- Tạm dừng 5 giây để Session khác thực hiện Insert
    DO SLEEP(5);
    
    -- Lần đếm 2: Xuất hiện dòng "bóng ma" mới được commit!
    SELECT COUNT(id) AS phantom_task_count, 'Lan dem 2 (Xuat hien Phantom!)' AS step
    FROM tasks 
    WHERE project_id = p_project_id;
    
    COMMIT;
END$$

-- 3. Giao tác khắc phục (FIX): Dùng SERIALIZABLE hoặc khóa khoảng trống (Gap Lock / Next-Key Lock)
CREATE PROCEDURE sp_Demo_Phantom_Reader_Fix(IN p_project_id INT)
BEGIN
    -- Mức cô lập cao nhất: Khóa toàn bộ khoảng dữ liệu quét được
    SET SESSION TRANSACTION ISOLATION LEVEL SERIALIZABLE;
    START TRANSACTION;
    
    -- Lần đếm 1: Đặt Shared Lock trên toàn bộ dải index của project_id
    -- Bất kỳ Transaction nào định INSERT vào project_id này đều bị BLOCKED (chờ)
    SELECT COUNT(id) AS initial_task_count, 'Lan dem 1 (SERIALIZABLE)' AS step
    FROM tasks 
    WHERE project_id = p_project_id;
    
    DO SLEEP(5);
    
    -- Lần đếm 2: Tuyệt đối không có bóng ma nào lọt vào
    SELECT COUNT(id) AS safe_task_count, 'Lan dem 2 (Nhat quan tuyet doi)' AS step
    FROM tasks 
    WHERE project_id = p_project_id;
    
    COMMIT;
END$$

DELIMITER ;

DROP PROCEDURE IF EXISTS sp_Demo_NonRepeatable_Reader_Unsafe;
DROP PROCEDURE IF EXISTS sp_Demo_NonRepeatable_Reader_Fix;
DROP PROCEDURE IF EXISTS sp_Demo_NonRepeatable_Modifier;

DELIMITER $$

-- 1. Giao tác sửa dữ liệu giữa chừng
CREATE PROCEDURE sp_Demo_NonRepeatable_Modifier(IN p_emp_id INT, IN p_new_position_id INT)
BEGIN
    START TRANSACTION;
    UPDATE employees 
    SET position_id = p_new_position_id 
    WHERE id = p_emp_id;
    COMMIT; -- Đã commit thành công!
END$$

-- 2. Giao tác đọc lỗi (UNSAFE): Dùng READ COMMITTED
CREATE PROCEDURE sp_Demo_NonRepeatable_Reader_Unsafe(IN p_emp_id INT)
BEGIN
    SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED;
    START TRANSACTION;
    
    -- Lần đọc 1
    SELECT id, full_name, position_id AS first_read_position, 'Lan doc 1' AS step 
    FROM employees WHERE id = p_emp_id;
    
    -- Tạm dừng 5 giây (Trong 5 giây này, Session khác sẽ nhảy vào Update và Commit)
    DO SLEEP(5);
    
    -- Lần đọc 2: Dữ liệu đã bị thay đổi dù cùng nằm trong 1 Transaction!
    SELECT id, full_name, position_id AS second_read_position, 'Lan doc 2 (Bi thay doi!)' AS step 
    FROM employees WHERE id = p_emp_id;
    
    COMMIT;
END$$

-- 3. Giao tác đọc chuẩn (FIX): Dùng REPEATABLE READ (Cơ chế MVCC - Chương 3_3)
CREATE PROCEDURE sp_Demo_NonRepeatable_Reader_Fix(IN p_emp_id INT)
BEGIN
    -- ÁP DỤNG CHƯƠNG 3_3: Sử dụng Snapshot Read của cơ chế Đa phiên bản (MVTO)
    SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ;
    START TRANSACTION;
    
    -- Lần đọc 1: Tạo bản chụp (Snapshot) phiên bản dữ liệu tại thời điểm bắt đầu
    SELECT id, full_name, position_id AS first_read_position, 'Lan doc 1' AS step 
    FROM employees WHERE id = p_emp_id;
    
    DO SLEEP(5);
    
    -- Lần đọc 2: InnoDB luôn đọc từ bản sao cũ, đảm bảo giá trị không bao giờ đổi
    SELECT id, full_name, position_id AS second_read_position, 'Lan doc 2 (Nhat quan tuyet doi)' AS step 
    FROM employees WHERE id = p_emp_id;
    
    COMMIT;
END$$

DELIMITER ;
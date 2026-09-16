DROP PROCEDURE IF EXISTS sp_Demo_DirtyRead_Writer;
DROP PROCEDURE IF EXISTS sp_Demo_DirtyRead_Reader_Unsafe;
DROP PROCEDURE IF EXISTS sp_Demo_DirtyRead_Reader_Fix;

DELIMITER $$

-- 1. Giao tác ghi: Sửa lương nhưng sau đó chủ động ROLLBACK
CREATE PROCEDURE sp_Demo_DirtyRead_Writer(
    IN p_contract_id INT,
    IN p_new_salary DECIMAL(15,2),
    IN p_hold_seconds INT
)
BEGIN
    START TRANSACTION;
    
    -- Cập nhật lương mới nhưng CHƯA commit
    UPDATE employee_contracts 
    SET salary = p_new_salary 
    WHERE id = p_contract_id;
    
    -- Giữ giao tác chưa chốt trong vài giây
    DO SLEEP(p_hold_seconds);
    
    -- ÁP DỤNG CHƯƠNG 2: Quay lui giao tác, hủy toàn bộ thay đổi
    ROLLBACK;
END$$

-- 2. Giao tác đọc lỗi (UNSAFE): Thiết lập mức cô lập READ UNCOMMITTED
CREATE PROCEDURE sp_Demo_DirtyRead_Reader_Unsafe(IN p_contract_id INT)
BEGIN
    -- Hạ mức cô lập xuống thấp nhất: Cho phép đọc dữ liệu chưa commit
    SET SESSION TRANSACTION ISOLATION LEVEL READ UNCOMMITTED;
    
    START TRANSACTION;
    
    -- Đọc dữ liệu "bóng ma" chưa được xác nhận
    SELECT id, contract_code, salary AS dirty_salary_read, 'READ UNCOMMITTED - Nguy co Dirty Read' AS note
    FROM employee_contracts
    WHERE id = p_contract_id;
    
    COMMIT;
END$$

-- 3. Giao tác đọc an toàn (FIX): Thiết lập mức cô lập chuẩn READ COMMITTED
CREATE PROCEDURE sp_Demo_DirtyRead_Reader_Fix(IN p_contract_id INT)
BEGIN
    -- ÁP DỤNG CHƯƠNG 2 & 3: Chỉ đọc dữ liệu từ các giao tác đã COMMIT thành công
    SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED;
    
    START TRANSACTION;
    
    SELECT id, contract_code, salary AS consistent_salary_read, 'READ COMMITTED - An toan' AS note
    FROM employee_contracts
    WHERE id = p_contract_id;
    
    COMMIT;
END$$

DELIMITER ;
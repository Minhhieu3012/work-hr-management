DROP PROCEDURE IF EXISTS sp_Demo_LostUpdate_Unsafe;
DROP PROCEDURE IF EXISTS sp_Demo_LostUpdate_Fix;

DELIMITER $$

-- 1. THỦ TỤC GÂY LỖI (UNSAFE): Đọc và ghi không có cơ chế khóa
CREATE PROCEDURE sp_Demo_LostUpdate_Unsafe(
    IN p_emp_id INT,
    IN p_deduct_days DECIMAL(5,2),
    IN p_delay_seconds INT -- Dùng SLEEP để giả lập độ trễ xử lý đan xen
)
BEGIN
    DECLARE v_current_leave DECIMAL(5,2);
    
    START TRANSACTION;
    
    -- Bước 1: Đọc dữ liệu ra biến (Không khóa)
    SELECT remaining_leave_days INTO v_current_leave
    FROM employees
    WHERE id = p_emp_id;
    
    -- Giả lập độ trễ xử lý nghiệp vụ hoặc mạng
    DO SLEEP(p_delay_seconds);
    
    -- Bước 2: Tính toán và ghi đè dữ liệu
    UPDATE employees
    SET remaining_leave_days = v_current_leave - p_deduct_days
    WHERE id = p_emp_id;
    
    COMMIT;
END$$

-- 2. THỦ TỤC KHẮC PHỤC (FIX): Áp dụng Khóa độc quyền (Exclusive Lock - X)
CREATE PROCEDURE sp_Demo_LostUpdate_Fix(
    IN p_emp_id INT,
    IN p_deduct_days DECIMAL(5,2),
    IN p_delay_seconds INT
)
BEGIN
    DECLARE v_current_leave DECIMAL(5,2);
    
    START TRANSACTION;
    
    -- ÁP DỤNG CHƯƠNG 3_2: Xin cấp khóa Write_Lock(X) ngay khi đọc
    -- Trong MySQL InnoDB, cú pháp chính là SELECT ... FOR UPDATE
    SELECT remaining_leave_days INTO v_current_leave
    FROM employees
    WHERE id = p_emp_id
    FOR UPDATE; -- Dòng dữ liệu này bị phong tỏa hoàn toàn đối với các giao tác khác
    
    DO SLEEP(p_delay_seconds);
    
    UPDATE employees
    SET remaining_leave_days = v_current_leave - p_deduct_days
    WHERE id = p_emp_id;
    
    COMMIT; -- Giải phóng khóa (Shrinking Phase của 2PL - Chương 3_2 Slide 14)
END$$

DELIMITER ;
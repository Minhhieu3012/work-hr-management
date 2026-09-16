-- =======================================================
-- Object: View vw_LeaveSummary
-- Mô tả: Thống kê số lượng đơn và tổng ngày nghỉ đã duyệt theo nhân viên
-- Áp dụng: Chương 1 (Hàm tổng hợp COUNT, SUM & hàm xử lý ngày DATEDIFF)
-- =======================================================

CREATE OR REPLACE VIEW vw_LeaveSummary AS 
SELECT 
    employee_id, 
    COUNT(id) AS total_requests,
    -- Tính tổng số ngày nghỉ (cộng thêm 1 vì DATEDIFF chỉ tính khoảng cách)
    SUM(DATEDIFF(end_date, start_date) + 1) AS total_days_requested 
FROM leave_requests 
WHERE status = 'Approved'
GROUP BY employee_id;
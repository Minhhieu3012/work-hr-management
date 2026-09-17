CREATE OR REPLACE VIEW vw_EmployeeProfiles AS 
SELECT
    e.id, 
    e.employee_code, 
    e.full_name, 
    e.email, 
    e.phone, 
    d.name AS department_name, 
    p.name AS position_name, 
    -- Cấu trúc rẽ nhánh Simple CASE để chuyển đổi mã trạng thái
    CASE e.status
        WHEN 'active' THEN 'Dang lam viec'
        WHEN 'inactive' THEN 'Tam Khoa'
        WHEN 'resigned' THEN 'Da nghi viec'
        ELSE 'Khac'
    END AS status_vi, 
    e.hire_date, 
    e.remaining_leave_days
FROM employees e 
JOIN departments d ON e.department_id = d.id 
JOIN positions p ON e.position_id = p.id
-- BẢO VỆ TÍNH ĐÚNG ĐẮN: Lọc bỏ các bản ghi đã bị xóa mềm (Soft-delete)
WHERE e.deleted_at IS NULL 
  AND d.deleted_at IS NULL 
  AND p.deleted_at IS NULL;
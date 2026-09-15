-- view 1: Hồ sơ nhân viên tổng hợp 
CREATE OR REPLACE VIEW vw_EmployeeProfiles AS 
SELECT
    e.id, e.employee_code, e.full_name, e.email, e.phone, 
    d.name AS department_name, p.name AS position_name, 
    CASE e.status
        WHEN 'active' THEN 'Dang lam viec'
        WHEN 'inactive' THEN 'Tam Khoa'
        WHEN 'resigned' THEN 'Da nghi viec'
        ELSE 'Khac'
    END AS status_vi, 
    e.hire_date, e.remaining_leave_days
FROM employee e 
JOIN departments d ON e.department_id = d.id 
JOIN positions p ON e.position_id = p.id; 

-- view 2: Tổng hợp nghỉ phép 
CREATE OR REPLACE VIEW vw_LeaveSummary AS 
SELECT 
    employee_id, 
    COUNT(id) AS total_requests,
    SUM(DATEDIFF(end_date, start_date)+1) AS total_days_requested 
FROM leave_requests 
WHERE status = 'Approved'
GROUP BY employee_id;

-- view 3: Tiến độ dự án 
CREATE ON REPLACE VIEW vw_ProjectTaskProgress AS 
SELECT 
    p.id AS project_id, p.name, 
    COUNT(t.id) AS total_tasks, 
    SUM(CASE WHEN t.status = 'Done' THEN 1 ELSE 0 END) AS completed_tasks, 
    (SUM(CASE WHEN t.status = 'Done' THEN 1 ELSE 0 END) / COUNT(t.id)) * 100 AS progress_percent
FROM projects p 
LEFT JOIN task t ON p.id = t.project_id
GROUP BY p.id, p.name;
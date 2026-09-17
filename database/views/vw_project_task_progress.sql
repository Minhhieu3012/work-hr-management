CREATE OR REPLACE VIEW vw_ProjectTaskProgress AS 
SELECT 
    p.id AS project_id, 
    p.name, 
    COUNT(t.id) AS total_tasks, 
    SUM(CASE WHEN t.status = 'Done' THEN 1 ELSE 0 END) AS completed_tasks, 
    -- Dùng NULLIF để tránh lỗi Division by Zero (chia cho 0 nếu dự án chưa có task nào)
    -- Bọc COALESCE để trả về 0% thay vì trả về NULL
    COALESCE(
        (SUM(CASE WHEN t.status = 'Done' THEN 1 ELSE 0 END) / NULLIF(COUNT(t.id), 0)) * 100, 
        0
    ) AS progress_percent
FROM projects p 
LEFT JOIN tasks t ON p.id = t.project_id
GROUP BY p.id, p.name;
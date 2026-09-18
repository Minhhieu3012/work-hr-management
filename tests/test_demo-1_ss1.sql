

SELECT CONNECTION_ID();

USE work_hr_management;

SELECT id, full_name, remaining_leave_days FROM employees WHERE id = 1;

CALL sp_Demo_LostUpdate_Unsafe(1, 2.00, 5);

SELECT remaining_leave_days FROM employees WHERE id = 1;

UPDATE employees SET remaining_leave_days = 12 WHERE id = 1;

CALL sp_Demo_LostUpdate_Fix(1, 2.00, 5);
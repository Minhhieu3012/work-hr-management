USE work_hr_management; 

CALL sp_Demo_Deadlock_Tx2(1, 2);

-- Output: Error Code: 1213. Deadlock found when trying to get lock; try restarting transaction

CALL sp_Demo_Deadlock_Fix(2, 1, 'Review');
<?php
namespace App\Services\Task;

use Core\Database;
use Exception;
use PDO;
use App\Services\Task\TaskActivityService;
use App\Enums\TaskAction;

class TaskAssignService
{
    public static function assign(
        $taskId,
        $assignerId,
        $assigneeId = null,
        $watcherId = null
    ) {
        $taskId = (int)$taskId;
        $assignerId = (int)$assignerId;
        $assigneeId = $assigneeId ? (int)$assigneeId : null;
        $watcherId = $watcherId ? (int)$watcherId : null;

        if ($taskId <= 0) {
            throw new Exception("Task not found");
        }

        if ($assignerId <= 0) {
            throw new Exception("Permission denied");
        }

        return Database::transaction(function (PDO $conn) use (
            $taskId,
            $assignerId,
            $assigneeId,
            $watcherId
        ) {
            // Kiểm tra người thực hiện thao tác
            $stmt = $conn->prepare("
                SELECT
                    id,
                    full_name,
                    role,
                    status
                FROM employees
                WHERE id = ?
                  AND deleted_at IS NULL
                LIMIT 1
            ");

            $stmt->execute([$assignerId]);
            $assigner = $stmt->fetch(PDO::FETCH_ASSOC);

            if (
                !$assigner ||
                !in_array($assigner['role'], ['admin', 'manager'], true) ||
                $assigner['status'] !== 'active'
            ) {
                throw new Exception("Permission denied");
            }

            // Khóa task trước khi đọc trạng thái
            $stmt = $conn->prepare("
                SELECT
                    id,
                    project_id,
                    title,
                    status,
                    assignee_id,
                    watcher_id
                FROM tasks
                WHERE id = ?
                LIMIT 1
                FOR UPDATE
            ");

            $stmt->execute([$taskId]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$task) {
                throw new Exception("Task not found");
            }

            $projectId = !empty($task['project_id'])
                ? (int)$task['project_id']
                : null;

            $title = (string)$task['title'];

            $oldAssigneeId = !empty($task['assignee_id'])
                ? (int)$task['assignee_id']
                : null;

            $oldWatcherId = !empty($task['watcher_id'])
                ? (int)$task['watcher_id']
                : null;

            // Manager chỉ được thao tác task thuộc project của mình
            if ($assigner['role'] === 'manager') {
                if (!$projectId) {
                    throw new Exception("Task không thuộc project hợp lệ");
                }

                $stmt = $conn->prepare("
                    SELECT manager_id
                    FROM projects
                    WHERE id = ?
                    LIMIT 1
                ");

                $stmt->execute([$projectId]);
                $project = $stmt->fetch(PDO::FETCH_ASSOC);

                if (
                    !$project ||
                    (int)$project['manager_id'] !== $assignerId
                ) {
                    throw new Exception(
                        "Bạn chỉ được phân công task thuộc dự án do mình quản lý"
                    );
                }
            }

            // Task đã hoàn thành hoặc đang Review thì không được phân công lại
            if ($task['status'] === 'Done') {
                throw new Exception("Cannot modify completed task");
            }

            if ($task['status'] === 'Review') {
                throw new Exception("Cannot modify task in review");
            }

            // ==================================================
            // CHỈ THAY WATCHER
            // ==================================================
            if (!$assigneeId && $watcherId) {
                $stmt = $conn->prepare("
                    SELECT
                        id,
                        full_name,
                        role,
                        status
                    FROM employees
                    WHERE id = ?
                      AND deleted_at IS NULL
                    LIMIT 1
                ");

                $stmt->execute([$watcherId]);
                $watcher = $stmt->fetch(PDO::FETCH_ASSOC);

                if (
                    !$watcher ||
                    $watcher['status'] !== 'active' ||
                    !in_array(
                        $watcher['role'],
                        ['admin', 'manager', 'employee'],
                        true
                    )
                ) {
                    throw new Exception("Watcher not found");
                }

                if ($oldWatcherId === $watcherId) {
                    throw new Exception("Watcher already assigned");
                }

                $stmt = $conn->prepare("
                    UPDATE tasks
                    SET watcher_id = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $watcherId,
                    $taskId
                ]);

                TaskActivityService::log(
                    $taskId,
                    $assignerId,
                    TaskAction::ASSIGN,
                    "Added watcher to task \"$title\""
                );

                if ($oldWatcherId) {
                    $stmt = $conn->prepare("
                        INSERT INTO notifications
                        (
                            user_id,
                            message,
                            is_read
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            0
                        )
                    ");

                    $stmt->execute([
                        $oldWatcherId,
                        "Bạn không còn theo dõi task \"$title\""
                    ]);
                }

                $stmt = $conn->prepare("
                    INSERT INTO notifications
                    (
                        user_id,
                        message,
                        is_read
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        0
                    )
                ");

                $stmt->execute([
                    $watcherId,
                    "Bạn được thêm làm watcher cho task \"$title\""
                ]);

                return [
                    'task_id' => $taskId,
                    'watcher_id' => $watcherId
                ];
            }

            // ==================================================
            // ASSIGN / REASSIGN
            // ==================================================
            if (!$assigneeId) {
                throw new Exception("Assignee is required");
            }

            // Kiểm tra assignee
            $stmt = $conn->prepare("
                SELECT
                    id,
                    full_name,
                    role,
                    status
                FROM employees
                WHERE id = ?
                  AND deleted_at IS NULL
                LIMIT 1
            ");

            $stmt->execute([$assigneeId]);
            $assignee = $stmt->fetch(PDO::FETCH_ASSOC);

            if (
                !$assignee ||
                $assignee['role'] !== 'employee' ||
                $assignee['status'] !== 'active'
            ) {
                throw new Exception("Assignee not found");
            }

            if ($oldAssigneeId === $assigneeId) {
                throw new Exception(
                    "Task already assigned for this employee"
                );
            }

            // Nếu có project_members thì assignee phải thuộc project
            if ($projectId) {
                $stmt = $conn->prepare("
                    SELECT COUNT(*)
                    FROM INFORMATION_SCHEMA.TABLES
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = 'project_members'
                ");

                $stmt->execute();
                $projectMembersEnabled =
                    (int)$stmt->fetchColumn() > 0;

                if ($projectMembersEnabled) {
                    $stmt = $conn->prepare("
                        SELECT id
                        FROM project_members
                        WHERE project_id = ?
                          AND employee_id = ?
                          AND status = 'active'
                        LIMIT 1
                    ");

                    $stmt->execute([
                        $projectId,
                        $assigneeId
                    ]);

                    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                        throw new Exception(
                            "Nhân viên được giao phải là thành viên của dự án"
                        );
                    }
                }
            }

            // Kiểm tra watcher mới nếu được truyền vào
            $newWatcherId = $oldWatcherId;

            if ($watcherId) {
                $stmt = $conn->prepare("
                    SELECT
                        id,
                        full_name,
                        role,
                        status
                    FROM employees
                    WHERE id = ?
                      AND deleted_at IS NULL
                    LIMIT 1
                ");

                $stmt->execute([$watcherId]);
                $watcher = $stmt->fetch(PDO::FETCH_ASSOC);

                if (
                    !$watcher ||
                    $watcher['status'] !== 'active' ||
                    !in_array(
                        $watcher['role'],
                        ['admin', 'manager', 'employee'],
                        true
                    )
                ) {
                    throw new Exception("Watcher not found");
                }

                $newWatcherId = $watcherId;
            }

            $isReassign = $oldAssigneeId !== null;

            // Nếu reassign thì báo cho người cũ
            if ($isReassign) {
                $stmt = $conn->prepare("
                    INSERT INTO notifications
                    (
                        user_id,
                        message,
                        is_read
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        0
                    )
                ");

                $stmt->execute([
                    $oldAssigneeId,
                    "Task \"$title\" đã được chuyển cho người khác"
                ]);
            }

            // Cập nhật task
            $stmt = $conn->prepare("
                UPDATE tasks
                SET
                    assignee_id = ?,
                    assigner_id = ?,
                    watcher_id = ?,
                    status = 'Doing'
                WHERE id = ?
            ");

            $stmt->execute([
                $assigneeId,
                $assignerId,
                $newWatcherId,
                $taskId
            ]);

            // Ghi lịch sử
            TaskActivityService::log(
                $taskId,
                $assignerId,
                $isReassign
                    ? TaskAction::REASSIGN
                    : TaskAction::ASSIGN,
                $isReassign
                    ? "Reassigned task \"$title\""
                    : "Assigned task \"$title\""
            );

            // Thông báo assignee mới
            $stmt = $conn->prepare("
                INSERT INTO notifications
                (
                    user_id,
                    message,
                    is_read
                )
                VALUES
                (
                    ?,
                    ?,
                    0
                )
            ");

            $stmt->execute([
                $assigneeId,
                $isReassign
                    ? "Bạn được giao lại task \"$title\""
                    : "Bạn được giao task \"$title\""
            ]);

            // Watcher cũ bị thay
            if (
                $oldWatcherId &&
                $newWatcherId &&
                $oldWatcherId !== $newWatcherId
            ) {
                $stmt = $conn->prepare("
                    INSERT INTO notifications
                    (
                        user_id,
                        message,
                        is_read
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        0
                    )
                ");

                $stmt->execute([
                    $oldWatcherId,
                    "Bạn không còn theo dõi task \"$title\""
                ]);
            }

            // Có watcher mới
            if (
                $newWatcherId &&
                $newWatcherId !== $oldWatcherId &&
                $newWatcherId !== $assigneeId
            ) {
                $stmt = $conn->prepare("
                    INSERT INTO notifications
                    (
                        user_id,
                        message,
                        is_read
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        0
                    )
                ");

                $stmt->execute([
                    $newWatcherId,
                    $isReassign
                        ? "Task \"$title\" vừa được reassign"
                        : "Task \"$title\" vừa được assign"
                ]);
            }

            // Watcher không đổi nhưng task được reassign
            if (
                $isReassign &&
                $newWatcherId &&
                $newWatcherId === $oldWatcherId &&
                $newWatcherId !== $assigneeId
            ) {
                $stmt = $conn->prepare("
                    INSERT INTO notifications
                    (
                        user_id,
                        message,
                        is_read
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        0
                    )
                ");

                $stmt->execute([
                    $newWatcherId,
                    "Task \"$title\" vừa được bàn giao lại cho \"{$assignee['full_name']}\""
                ]);
            }

            return [
                'task_id' => $taskId,
                'assigned_to' => $assigneeId,
                'watcher_id' => $newWatcherId
            ];
        });
    }
}
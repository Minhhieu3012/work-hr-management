<?php
namespace App\Services\Task;

use Exception;
use PDO;
use Core\Database;
use App\Enums\TaskAction;
use App\Services\Task\TaskActivityService;
use App\Services\Core\NotificationService;
use App\Models\Task\TaskModel;

class TaskApprovalService {

    public static function submit($taskId, $userId) {

        return Database::transaction(function (PDO $conn) use ($taskId, $userId) {

            // khóa task ở tầng Model để toàn bộ flow dùng chung cơ chế locking
            $taskModel = new TaskModel();
            $task = $taskModel->findByIdForUpdate((int)$taskId);

            if (!$task) {
                throw new Exception("Task not found");
            }

            if ($task['assignee_id'] != $userId) {
                throw new Exception("You are not assigned to this task");
            }

            if ($task['status'] !== 'Doing') {
                throw new Exception("Only Doing tasks can be submitted");
            }

            $stmt = $conn->prepare("SELECT full_name FROM employees WHERE id = ?");
            $stmt->execute([$userId]);
            $actor = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$actor) {
                throw new Exception("User not found");
            }

            // update
            $stmt = $conn->prepare("UPDATE tasks SET status = 'Review' WHERE id = ?");
            $stmt->execute([$taskId]);

            // activity log dùng cùng transaction
            TaskActivityService::log(
                $taskId,
                $userId,
                TaskAction::STATUS_CHANGE,
                "{$actor['full_name']} submitted task \"{$task['title']}\" for review",
                $conn
            );

            // notify assigner dùng cùng transaction
            if ($task['assigner_id']) {
                NotificationService::send(
                    $task['assigner_id'],
                    "Task \"{$task['title']}\" đã được submit bởi \"{$actor['full_name']}\" để review",
                    $conn
                );
            }

            return [
                "task_id" => $taskId,
                "status" => "Review"
            ];
        });
    }

    public static function approve($taskId, $userId) {

        return Database::transaction(function (PDO $conn) use ($taskId, $userId) {

            // check role
            $stmt = $conn->prepare("SELECT role, full_name FROM employees WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !in_array($user['role'], ['admin', 'manager'])) {
                throw new Exception("Permission denied");
            }

            // khóa task ở tầng Model trước khi kiểm tra trạng thái
            $taskModel = new TaskModel();
            $task = $taskModel->findByIdForUpdate((int)$taskId);

            if (!$task) {
                throw new Exception("Task not found");
            }

            if ($task['status'] !== 'Review') {
                throw new Exception("Task must be in Review state");
            }

            $assigneeName = null;

            if ($task['assignee_id']) {
                $stmt = $conn->prepare("SELECT full_name FROM employees WHERE id = ?");
                $stmt->execute([$task['assignee_id']]);
                $assignee = $stmt->fetch(PDO::FETCH_ASSOC);
                $assigneeName = $assignee['full_name'] ?? null;
            }

            // update
            $stmt = $conn->prepare("UPDATE tasks SET status = 'Done' WHERE id = ?");
            $stmt->execute([$taskId]);

            TaskActivityService::log(
                $taskId,
                $userId,
                TaskAction::STATUS_CHANGE,
                "{$user['full_name']} approved task \"{$task['title']}\" → Done (Assignee: {$assigneeName})",
                $conn
            );

            if ($task['assignee_id']) {
                NotificationService::send(
                    $task['assignee_id'],
                    "Task \"{$task['title']}\" đã được duyệt bởi manager. DONE!",
                    $conn
                );
            }

            return [
                "task_id" => $taskId,
                "status" => "Done"
            ];
        });
    }

    public static function reject($taskId, $userId) {

        return Database::transaction(function (PDO $conn) use ($taskId, $userId) {

            // check role
            $stmt = $conn->prepare("SELECT role, full_name FROM employees WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !in_array($user['role'], ['admin', 'manager'])) {
                throw new Exception("Permission denied");
            }

            // khóa task ở tầng Model trước khi kiểm tra trạng thái
            $taskModel = new TaskModel();
            $task = $taskModel->findByIdForUpdate((int)$taskId);

            if (!$task) {
                throw new Exception("Task not found");
            }

            if ($task['status'] !== 'Review') {
                throw new Exception("Task must be in Review state");
            }

            $assigneeName = null;

            if ($task['assignee_id']) {
                $stmt = $conn->prepare("SELECT full_name FROM employees WHERE id = ?");
                $stmt->execute([$task['assignee_id']]);
                $assignee = $stmt->fetch(PDO::FETCH_ASSOC);
                $assigneeName = $assignee['full_name'] ?? null;
            }

            // update
            $stmt = $conn->prepare("UPDATE tasks SET status = 'Doing' WHERE id = ?");
            $stmt->execute([$taskId]);

            TaskActivityService::log(
                $taskId,
                $userId,
                TaskAction::STATUS_CHANGE,
                "{$user['full_name']} rejected task \"{$task['title']}\" → Back to Doing (Assignee: {$assigneeName})",
                $conn
            );

            if ($task['assignee_id']) {
                NotificationService::send(
                    $task['assignee_id'],
                    "Task \"{$task['title']}\" bị từ chối bởi manager \"{$user['full_name']}\". Yêu cầu làm lại!",
                    $conn
                );
            }

            return [
                "task_id" => $taskId,
                "status" => "Doing"
            ];
        });
    }

    public static function getTasksInReview($userId) {

        $conn = Database::getConnection();

        $stmt = $conn->prepare("SELECT role FROM employees WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !in_array($user['role'], ['admin', 'manager'])) {
            throw new Exception("Permission denied");
        }

        $stmt = $conn->prepare("
            SELECT 
                t.id,
                t.title,
                t.description,
                t.status,
                t.priority,
                t.deadline,
                assigner.full_name AS assigner_name,
                assignee.full_name AS assignee_name,
                t.created_at,
                t.updated_at
            FROM tasks t
            LEFT JOIN employees assigner ON t.assigner_id = assigner.id
            LEFT JOIN employees assignee ON t.assignee_id = assignee.id
            WHERE t.status = 'Review'
            ORDER BY t.updated_at DESC
        ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

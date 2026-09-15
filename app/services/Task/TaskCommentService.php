<?php
namespace App\Services\Task;

use PDO;
use Core\Database;
use App\Services\Task\TaskActivityService;
use App\Services\Core\NotificationService;
use App\Enums\TaskAction;
use Exception;

class TaskCommentService {

    public static function getAll($taskId = null) {
        $conn = Database::getConnection();

        $sql = "
            SELECT tc.*, e.full_name
            FROM task_comments tc
            JOIN employees e ON tc.user_id = e.id
        ";

        if ($taskId) {
            $sql .= " WHERE tc.task_id = ?";
        }

        $sql .= " ORDER BY tc.created_at DESC";

        $stmt = $conn->prepare($sql);
        $taskId ? $stmt->execute([$taskId]) : $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getById($commentId) {
        $conn = Database::getConnection();

        $stmt = $conn->prepare("
            SELECT tc.*, e.full_name
            FROM task_comments tc
            JOIN employees e ON tc.user_id = e.id
            WHERE tc.id = ?
        ");

        $stmt->execute([$commentId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function getByTask($taskId) {
        $conn = Database::getConnection();

        $stmt = $conn->prepare("
            SELECT
                tc.id,
                tc.task_id,
                tc.comment_text,
                tc.created_at,
                tc.updated_at,
                tc.user_id,
                e.full_name
            FROM task_comments tc
            JOIN employees e ON tc.user_id = e.id
            WHERE tc.task_id = ?
            ORDER BY tc.created_at ASC
        ");

        $stmt->execute([$taskId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create($taskId, $userId, $data) {
        $content = trim((string)($data['content'] ?? ''));

        if ($content === '') {
            throw new Exception("Comment content is required");
        }

        return Database::transaction(function (PDO $conn) use (
            $taskId,
            $userId,
            $content
        ) {
            $stmt = $conn->prepare("
                SELECT assigner_id, assignee_id, watcher_id
                FROM tasks
                WHERE id = ?
                FOR UPDATE
            ");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$task) {
                throw new Exception("Task not found");
            }

            $stmt = $conn->prepare("
                SELECT full_name, role
                FROM employees
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $actor = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$actor) {
                throw new Exception("User not found");
            }

            $stmt = $conn->prepare("
                INSERT INTO task_comments
                (
                    task_id,
                    user_id,
                    comment_text
                )
                VALUES (?, ?, ?)
            ");

            $stmt->execute([
                $taskId,
                $userId,
                $content
            ]);

            $commentId = (int)$conn->lastInsertId();

            TaskActivityService::log(
                $taskId,
                $userId,
                TaskAction::COMMENT,
                "{$actor['full_name']} added comment",
                $conn
            );

            $userIds = array_unique(array_filter([
                $task['assigner_id'],
                $task['assignee_id'],
                $task['watcher_id']
            ]));

            $userIds = array_filter(
                $userIds,
                fn($id) => (int)$id !== (int)$userId
            );

            $who = in_array($actor['role'], ['admin', 'manager'], true)
                ? "Manager {$actor['full_name']}"
                : $actor['full_name'];

            NotificationService::sendToMany(
                $userIds,
                "{$who} đã thêm bình luận mới vào task",
                $conn
            );

            return [
                "id" => $commentId,
                "comment_text" => $content
            ];
        });
    }

    public static function update($commentId, $userId, $data) {
        $content = trim((string)($data['content'] ?? ''));

        if ($content === '') {
            throw new Exception("Comment content is required");
        }

        return Database::transaction(function (PDO $conn) use (
            $commentId,
            $userId,
            $content
        ) {
            // lấy và khóa comment
            $stmt = $conn->prepare("
                SELECT user_id, task_id
                FROM task_comments
                WHERE id = ?
                FOR UPDATE
            ");
            $stmt->execute([$commentId]);
            $comment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$comment) {
                throw new Exception("Comment not found");
            }

            // lấy role
            $stmt = $conn->prepare("
                SELECT role, full_name
                FROM employees
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                throw new Exception("User not found");
            }

            // check quyền
            if (
                $comment['user_id'] != $userId &&
                !in_array($user['role'], ['admin', 'manager'], true)
            ) {
                throw new Exception("Permission denied");
            }

            $stmt = $conn->prepare("
                UPDATE task_comments
                SET comment_text = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$content, $commentId]);

            TaskActivityService::log(
                $comment['task_id'],
                $userId,
                TaskAction::UPDATE,
                "{$user['full_name']} updated a comment",
                $conn
            );

            $stmt = $conn->prepare("
                SELECT assigner_id, assignee_id, watcher_id
                FROM tasks
                WHERE id = ?
            ");
            $stmt->execute([$comment['task_id']]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$task) {
                throw new Exception("Task not found");
            }

            $who = in_array($user['role'], ['admin', 'manager'], true)
                ? "manager {$user['full_name']}"
                : $user['full_name'];

            if ((int)$comment['user_id'] !== (int)$userId) {
                NotificationService::send(
                    $comment['user_id'],
                    "Comment của bạn đã được cập nhật bởi $who",
                    $conn
                );
            }

            $userIds = array_unique(array_filter([
                $task['assigner_id'],
                $task['assignee_id'],
                $task['watcher_id']
            ]));

            $userIds = array_filter(
                $userIds,
                fn($id) =>
                    (int)$id !== (int)$userId &&
                    (int)$id !== (int)$comment['user_id']
            );

            NotificationService::sendToMany(
                $userIds,
                "Comment của task đã được cập nhật bởi $who",
                $conn
            );

            return [
                "id" => $commentId,
                "comment_text" => $content
            ];
        });
    }

    public static function delete($commentId, $userId) {
        return Database::transaction(function (PDO $conn) use (
            $commentId,
            $userId
        ) {
            // lấy và khóa comment
            $stmt = $conn->prepare("
                SELECT user_id, task_id, comment_text
                FROM task_comments
                WHERE id = ?
                FOR UPDATE
            ");
            $stmt->execute([$commentId]);
            $comment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$comment) {
                throw new Exception("Comment not found");
            }

            // lấy user actor
            $stmt = $conn->prepare("
                SELECT role, full_name
                FROM employees
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                throw new Exception("User not found");
            }

            if (
                $comment['user_id'] != $userId &&
                !in_array($user['role'], ['admin', 'manager'], true)
            ) {
                throw new Exception("Permission denied");
            }

            // lấy task info trước khi xóa comment
            $stmt = $conn->prepare("
                SELECT assigner_id, assignee_id, watcher_id
                FROM tasks
                WHERE id = ?
            ");
            $stmt->execute([$comment['task_id']]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$task) {
                throw new Exception("Task not found");
            }

            $stmt = $conn->prepare("
                DELETE FROM task_comments
                WHERE id = ?
            ");
            $stmt->execute([$commentId]);

            TaskActivityService::log(
                $comment['task_id'],
                $userId,
                TaskAction::DELETE,
                "{$user['full_name']} deleted a comment",
                $conn
            );

            $who = in_array($user['role'], ['admin', 'manager'], true)
                ? "manager {$user['full_name']}"
                : $user['full_name'];

            if ((int)$comment['user_id'] !== (int)$userId) {
                NotificationService::send(
                    $comment['user_id'],
                    "Comment của bạn đã bị xoá bởi $who",
                    $conn
                );
            }

            $userIds = array_unique(array_filter([
                $task['assigner_id'],
                $task['assignee_id'],
                $task['watcher_id']
            ]));

            $userIds = array_filter(
                $userIds,
                fn($id) =>
                    (int)$id !== (int)$userId &&
                    (int)$id !== (int)$comment['user_id']
            );

            NotificationService::sendToMany(
                $userIds,
                "Một comment của task đã bị xoá bởi $who",
                $conn
            );

            return [
                "id" => (int)$commentId,
                "deleted" => true
            ];
        });
    }
}

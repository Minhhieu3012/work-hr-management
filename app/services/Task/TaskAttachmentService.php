<?php
namespace App\Services\Task;

use App\Services\Task\TaskActivityService;
use Core\Database;
use Exception;
use PDO;
use Throwable;
use App\Enums\TaskAction;
use App\Services\Core\NotificationService;

class TaskAttachmentService {

    private static function uploadDir(): string {
        return __DIR__ . '/../../public/uploads/tasks/';
    }

    public static function upload($taskId, $userId, $file) {
        $conn = Database::getConnection();

        // 1. check task tồn tại
        $stmt = $conn->prepare("
            SELECT assignee_id, assigner_id, watcher_id
            FROM tasks
            WHERE id = ?
        ");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$task) {
            throw new Exception("Task not found");
        }

        // 2. check quyền
        $stmt = $conn->prepare("
            SELECT role
            FROM employees
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new Exception("User not found");
        }

        if (
            $task['assignee_id'] != $userId &&
            $task['assigner_id'] != $userId &&
            $task['watcher_id'] != $userId &&
            !in_array($user['role'], ['admin', 'manager'], true)
        ) {
            throw new Exception("Permission denied");
        }

        // 3. check upload
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Upload failed");
        }

        // 4. validate size (5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception("File too large (max 5MB)");
        }

        // 5. validate extension
        $allowedExt = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExt, true)) {
            throw new Exception("File type not allowed");
        }

        // 6. validate MIME
        $allowedMime = [
            'image/jpeg',
            'image/png',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        $mime = mime_content_type($file['tmp_name']);

        if (!in_array($mime, $allowedMime, true)) {
            throw new Exception("Invalid file content");
        }

        // 7. generate unique name
        $fileName = uniqid('task_', true) . "." . $ext;

        $uploadDir = self::uploadDir();

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
            throw new Exception("Cannot create upload directory");
        }

        $filePath = $uploadDir . $fileName;

        // 8. save file vật lý trước
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new Exception("Cannot save file");
        }

        try {
            $result = Database::transaction(function (PDO $tx) use (
                $taskId,
                $userId,
                $file,
                $fileName,
                $task
            ) {
                $stmt = $tx->prepare("
                    INSERT INTO task_attachments
                    (
                        task_id,
                        user_id,
                        file_name,
                        file_path
                    )
                    VALUES (?, ?, ?, ?)
                ");

                $stmt->execute([
                    $taskId,
                    $userId,
                    $file['name'],
                    $fileName
                ]);

                $attachmentId = (int)$tx->lastInsertId();

                $stmt = $tx->prepare("
                    SELECT full_name
                    FROM employees
                    WHERE id = ?
                ");
                $stmt->execute([$userId]);
                $actor = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$actor) {
                    throw new Exception("User not found");
                }

                TaskActivityService::log(
                    $taskId,
                    $userId,
                    TaskAction::UPLOAD,
                    "{$actor['full_name']} uploaded file \"{$file['name']}\"",
                    $tx
                );

                $notifyMsg = "{$actor['full_name']} đã upload file \"{$file['name']}\"";

                $userIds = array_unique(array_filter([
                    $task['assignee_id'],
                    $task['assigner_id'],
                    $task['watcher_id']
                ]));

                $userIds = array_filter(
                    $userIds,
                    fn($id) => (int)$id !== (int)$userId
                );

                NotificationService::sendToMany(
                    $userIds,
                    $notifyMsg,
                    $tx
                );

                return [
                    "id" => $attachmentId,
                    "file_name" => $file['name'],
                    "url" => "/uploads/tasks/" . $fileName
                ];
            });

            return $result;
        } catch (Throwable $e) {
            if (is_file($filePath)) {
                @unlink($filePath);
            }

            throw $e;
        }
    }

    public static function getByTask($taskId) {
        $conn = Database::getConnection();

        $stmt = $conn->prepare("
            SELECT id, file_name, file_path, uploaded_at
            FROM task_attachments
            WHERE task_id = ?
            ORDER BY uploaded_at DESC
        ");

        $stmt->execute([$taskId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function download($fileId, $userId) {
        $conn = Database::getConnection();

        // 1. Lấy file
        $stmt = $conn->prepare("
            SELECT
                ta.file_name,
                ta.file_path,
                ta.task_id
            FROM task_attachments ta
            WHERE ta.id = ?
        ");
        $stmt->execute([$fileId]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$file) {
            throw new Exception("File not found");
        }

        // 2. Lấy task + role
        $stmt = $conn->prepare("
            SELECT
                t.assignee_id,
                t.assigner_id,
                t.watcher_id,
                e.role
            FROM tasks t
            JOIN employees e ON e.id = ?
            WHERE t.id = ?
        ");
        $stmt->execute([$userId, $file['task_id']]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            throw new Exception("Task not found");
        }

        // 3. Check quyền
        if (
            $data['assignee_id'] != $userId &&
            $data['assigner_id'] != $userId &&
            $data['watcher_id'] != $userId &&
            !in_array($data['role'], ['admin', 'manager'], true)
        ) {
            throw new Exception("Permission denied");
        }

        // 4. Check file tồn tại
        $path = self::uploadDir() . basename($file['file_path']);

        if (!file_exists($path)) {
            throw new Exception("File missing on server");
        }

        // 5. Clean filename
        $safeName = basename($file['file_name']);

        // 6. Ghi activity + notification trước khi output
        $stmt = $conn->prepare("
            SELECT full_name
            FROM employees
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $actor = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$actor) {
            throw new Exception("User not found");
        }

        TaskActivityService::log(
            $file['task_id'],
            $userId,
            TaskAction::DOWNLOAD,
            "{$actor['full_name']} downloaded file \"{$file['file_name']}\""
        );

        $notifyMsg = "{$actor['full_name']} đã tải file \"{$file['file_name']}\"";

        if (!empty($data['assigner_id']) && (int)$data['assigner_id'] !== (int)$userId) {
            NotificationService::send(
                $data['assigner_id'],
                $notifyMsg
            );
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $safeName . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: public');

        readfile($path);
        exit;
    }

    public static function getFilePathsByProject(
        $projectId,
        ?PDO $pdo = null
    ): array {
        $conn = $pdo ?? Database::getConnection();

        $stmt = $conn->prepare("
            SELECT ta.file_path
            FROM task_attachments ta
            INNER JOIN tasks t ON t.id = ta.task_id
            WHERE t.project_id = ?
            ORDER BY ta.id ASC
        ");

        $stmt->execute([(int)$projectId]);

        return array_values(array_filter(
            $stmt->fetchAll(PDO::FETCH_COLUMN)
        ));
    }

    public static function deletePhysicalFiles(array $filePaths): void {
        $uploadDir = self::uploadDir();

        foreach (array_unique($filePaths) as $filePath) {
            $safeName = basename((string)$filePath);

            if ($safeName === '') {
                continue;
            }

            $fullPath = $uploadDir . $safeName;

            if (is_file($fullPath)) {
                @unlink($fullPath);
            }
        }
    }
}

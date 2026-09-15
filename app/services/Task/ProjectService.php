<?php
namespace App\Services\Task;

use App\Models\Task\ProjectModel;
use Core\Database;
use Exception;
use PDO;

class ProjectService {
    private $model;
    private $authUser;

    public function __construct($authUser) {
        $this->model = new ProjectModel();
        $this->authUser = $authUser;
    }

    private function validate($data, $isUpdate = false) {
        if (!$isUpdate || array_key_exists('name', $data)) {
            if (empty($data['name'])) {
                throw new Exception("Tên project không được để trống");
            }

            if (strlen($data['name']) > 255) {
                throw new Exception("Tên project tối đa 255 ký tự");
            }
        }

        $validStatus = ['Active', 'Completed', 'Archived'];

        if (!empty($data['status']) && !in_array($data['status'], $validStatus, true)) {
            throw new Exception("Status không hợp lệ");
        }

        if (!empty($data['manager_id'])) {
            if (!is_numeric($data['manager_id'])) {
                throw new Exception("manager_id phải là số");
            }

            if (!$this->managerExists((int)$data['manager_id'])) {
                throw new Exception("Manager không tồn tại");
            }
        }

        return true;
    }

    private function managerExists(int $managerId): bool {
        $conn = Database::getConnection();

        $stmt = $conn->prepare("
            SELECT id
            FROM employees
            WHERE id = ?
              AND role = 'manager'
              AND status = 'active'
              AND deleted_at IS NULL
            LIMIT 1
        ");

        $stmt->execute([$managerId]);

        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }

    // =========================
    // GET ALL
    // =========================
    public function getAll() {
        if (!in_array($this->authUser['role'], ['admin', 'manager'], true)) {
            throw new Exception("Bạn không có quyền xem project");
        }

        return $this->model->all([], $this->authUser);
    }

    // =========================
    // GET BY ID
    // =========================
    public function getById($id) {
        $project = $this->model->findById((int)$id);

        if (!$project) {
            throw new Exception("Project không tồn tại");
        }

        if (
            $this->authUser['role'] === 'manager' &&
            (int)$project['manager_id'] !== (int)$this->authUser['id']
        ) {
            throw new Exception("Bạn không có quyền truy cập project này");
        }

        if ($this->authUser['role'] === 'employee') {
            throw new Exception("Bạn không có quyền");
        }

        return $project;
    }

    // =========================
    // CREATE
    // =========================
    public function create($data) {
        if (!in_array($this->authUser['role'], ['admin', 'manager'], true)) {
            throw new Exception("Bạn không có quyền tạo project");
        }

        $this->validate($data);

        if ($this->authUser['role'] === 'manager') {
            $managerId = (int)$this->authUser['id'];
        } else {
            $managerId = isset($data['manager_id'])
                ? (int)$data['manager_id']
                : 0;

            if ($managerId <= 0 || !$this->managerExists($managerId)) {
                throw new Exception("Admin phải chọn Manager hợp lệ cho project");
            }
        }

        return $this->model->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'client_id' => $data['client_id'] ?? null,
            'status' => $data['status'] ?? 'Active',
            'member_ids' => $data['member_ids'] ?? [],
        ], $managerId);
    }

    // =========================
    // UPDATE
    // =========================
    public function update($id, $data) {
        if (!in_array($this->authUser['role'], ['admin', 'manager'], true)) {
            throw new Exception("Bạn không có quyền cập nhật");
        }

        $project = $this->getById($id);
        $this->validate($data, true);

        $updateData = [];

        if (array_key_exists('name', $data)) {
            $updateData['name'] = $data['name'];
        }

        if (array_key_exists('description', $data)) {
            $updateData['description'] = $data['description'];
        }

        if (array_key_exists('client_id', $data)) {
            $updateData['client_id'] = $data['client_id'];
        }

        if (array_key_exists('status', $data)) {
            $updateData['status'] = $data['status'];
        }

        if (array_key_exists('member_ids', $data)) {
            $updateData['member_ids'] = $data['member_ids'];
        }

        $managerId = $this->authUser['role'] === 'manager'
            ? (int)$this->authUser['id']
            : null;

        return $this->model->update((int)$id, $updateData, $managerId);
    }

    // =========================
    // DELETE - CASCADE
    // =========================
    public function delete($id) {
        $projectId = (int)$id;

        if (!in_array($this->authUser['role'], ['admin', 'manager'], true)) {
            throw new Exception("Bạn không có quyền xoá");
        }

        $filePaths = Database::transaction(function (PDO $conn) use ($projectId) {
            $project = $this->model->findByIdForUpdate($projectId);

            if (!$project) {
                throw new Exception("Project không tồn tại");
            }

            if (
                $this->authUser['role'] === 'manager' &&
                (int)$project['manager_id'] !== (int)$this->authUser['id']
            ) {
                throw new Exception("Bạn không có quyền xoá project này");
            }

            // Lấy đường dẫn file trước khi DELETE.
            // Record task_attachments sẽ bị MySQL cascade xóa theo tasks.
            $filePaths = TaskAttachmentService::getFilePathsByProject(
                $projectId,
                $conn
            );

            $stmt = $conn->prepare("
                DELETE FROM projects
                WHERE id = ?
            ");

            $stmt->execute([$projectId]);

            if ($stmt->rowCount() !== 1) {
                throw new Exception("Không thể xoá project");
            }

            return $filePaths;
        });

        // File vật lý không thuộc transaction của MySQL.
        // Chỉ xóa sau khi transaction DB đã COMMIT thành công.
        TaskAttachmentService::deletePhysicalFiles($filePaths);

        return true;
    }
}

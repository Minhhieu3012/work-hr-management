# Work & HR Management System

Tài liệu này quy định chuẩn giao tiếp API giữa Frontend và Backend cho toàn bộ các module trong dự án.

> Tất cả các thành viên **BẮT BUỘC** tuân thủ định dạng này.

---

## 1. Cấu trúc Response Chuẩn

### ✅ Thành công (`200 OK`, `201 Created`)

```json
{
  "status": "success",
  "message": "Mô tả kết quả",
  "data": {}
}
```

### ❌ Thất bại (`400`, `401`, `403`, `404`, `500`)

```json
{
  "status": "error",
  "message": "Mô tả lỗi cụ thể",
  "errors": {
    "email": "Email đã tồn tại"
  }
}
```

> `errors` là tùy chọn, chỉ dùng khi có lỗi validation chi tiết.

---

## 2. Xác thực (Authentication)

Tất cả route yêu cầu đăng nhập phải gửi kèm JWT Token trong header:
Authorization: Bearer <your_jwt_token>

---

## 3. Endpoints

### Auth & Tài khoản

> Nhánh: `core-auth-security` — **Hiếu**

| Method | Endpoint             | Mô tả                             |
| ------ | -------------------- | --------------------------------- |
| `POST` | `/api/auth/login`    | Đăng nhập, trả về JWT Token       |
| `POST` | `/api/auth/register` | Đăng ký tài khoản nhân viên mới   |
| `GET`  | `/api/auth/me`       | Lấy thông tin user đang đăng nhập |

---

### Quản lý Nhân sự (HRM)

> Nhánh: `hrm-employee-crud` — **Thành**

| Method   | Endpoint             | Mô tả                   |
| -------- | -------------------- | ----------------------- |
| `GET`    | `/api/employees`     | Lấy danh sách nhân viên |
| `POST`   | `/api/employees`     | Tạo nhân viên mới       |
| `GET`    | `/api/employees/:id` | Xem chi tiết nhân viên  |
| `PUT`    | `/api/employees/:id` | Cập nhật hồ sơ          |
| `DELETE` | `/api/employees/:id` | Xóa nhân viên           |

---

### Quản lý Công việc (Task)

> Nhánh: `task-kanban-board` — **Huy & Bảo**

| Method  | Endpoint                  | Mô tả                                                                                               |
| ------- | ------------------------- | --------------------------------------------------------------------------------------------------- |
| `GET`   | `/api/tasks`              | Lấy danh sách task (filter: `?status=...&project_id=...`). Trạng thái: `To do, Doing, Review, Done` |
| `POST`  | `/api/tasks`              | Tạo task mới                                                                                        |
| `PATCH` | `/api/tasks/:id/status`   | Cập nhật trạng thái Kanban. Các giá trị hợp lệ: `To do, Doing, Review, Done`                        |
| `POST`  | `/api/tasks/:id/comments` | Thêm bình luận vào task                                                                             |

---

#### Field `status` - Allowed Values

| Giá trị  | Mô tả                  |
| -------- | ---------------------- |
| `To do`  | Task mới, chưa bắt đầu |
| `Doing`  | Đang thực hiện         |
| `Review` | Chờ review/kiểm tra    |
| `Done`   | Hoàn thành             |

> Áp dụng cho cả `POST /api/tasks` (field `status` khi tạo) và `PATCH /api/tasks/:id/status` (field `status` khi cập nhật).

#### Ví dụ Request — `PATCH /api/tasks/:id/status`

```json
{
  "status": "Review"
}
```

#### Ví dụ Request — `GET /api/tasks` với filter

```http
GET /api/tasks?status=Review&project_id=1
```

---

### Chấm công & Nghỉ phép & Tính lương

> Nhánh: `attendance-payroll` — **Tiến**

| Method | Endpoint | Roles | Mô tả |
| --- | --- | --- | --- |
| `GET` | `/api/attendance` | `admin`, `manager`, `employee` | Lấy dữ liệu chấm công tháng hiện tại & lịch sử |
| `POST` | `/api/attendance/checkin` | `manager`, `employee` | Chấm công vào (Web check-in) |
| `POST` | `/api/attendance/checkout` | `manager`, `employee` | Chấm công ra (Web check-out) |
| `POST` | `/api/attendance/payroll` | `admin`, `manager` | Tính bảng lương tháng (gọi Stored Procedure) |
| `GET` | `/api/leaves` | `admin`, `manager`, `employee` | Lấy quỹ phép còn lại và lịch sử đơn nghỉ của cá nhân |
| `GET` | `/api/admin/leaves` | `admin`, `manager` | Lấy danh sách đơn xin nghỉ đang chờ duyệt (`Pending`) |
| `POST` | `/api/leaves` | `manager`, `employee` | Gửi đơn xin nghỉ phép |
| `PATCH` | `/api/leaves/:id/approve` | `manager` | Phê duyệt (`Approved`) hoặc Từ chối (`Rejected`) đơn nghỉ |

---

#### Field `leave_type` - Allowed Values (Thống nhất với DB ENUM)

| Giá trị     | Tên hiển thị      | Trừ quỹ phép năm (`remaining_leave_days`)? |
| ----------- | ----------------- | ------------------------------------------ |
| `Annual`    | Nghỉ phép năm     | Có (kiểm tra quỹ phép trước khi gửi/duyệt) |
| `Personal`  | Nghỉ việc cá nhân | Có                                         |
| `Sick`      | Nghỉ ốm           | Không (hưởng chế độ y tế / BHXH)           |
| `Unpaid`    | Nghỉ không lương  | Không (cho phép gửi kể cả khi hết phép)     |
| `Maternity` | Nghỉ thai sản     | Không (chế độ thai sản BHXH)               |

> **Lưu ý quan trọng về nghỉ nửa ngày:** `half_day` **không** phải là loại nghỉ phép. Để xin nghỉ nửa ngày, chọn loại nghỉ tương ứng (`Annual`, `Personal`...) và gửi `duration: 0.5`. Hệ thống sẽ từ chối nếu truyền `leave_type: half_day`.

#### Ví dụ Request — `POST /api/attendance/payroll`

```json
{
  "month": 9,
  "year": 2026
}
```

> Hỗ trợ cả `POST /api/payroll/calculate` và truyền tham số qua Query String (`?month=9&year=2026`). Nếu không truyền, hệ thống sẽ mặc định tính cho tháng và năm hiện tại.

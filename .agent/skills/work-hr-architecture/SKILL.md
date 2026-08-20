---
name: work-hr-architecture
description: Enforce architectural patterns, security standards, and coding conventions for the work-hr-management Custom PHP MVC codebase.
---

# Architecture Rules for work-hr-management

## 1. Controller & Service Layer Pattern

- **Controllers** (`app/Controllers/`): Chỉ nhận Request, gọi Middleware xác thực, chuyển dữ liệu cho Service và trả về JSON API response hoặc render View tương ứng. Không viết trực tiếp SQL query hoặc logic nghiệp vụ phức tạp trong Controller.
- **Services** (`app/services/`): Xử lý toàn bộ logic nghiệp vụ (Business Logic), tính toán ngày công, duyệt phép, quản lý Task/Kanban/Gantt.
- **Models** (`app/Models/`): Tương tác trực tiếp với cơ sở dữ liệu qua wrapper `core/Database.php`.

## 2. Security & Data Integrity

- Luôn kiểm tra quyền truy cập thông qua `app/Middleware/AuthMiddleware.php` và `RoleMiddleware.php`.
- Tất cả truy vấn DB phải sử dụng **PDO Prepared Statements** thông qua `core/Database.php` để ngăn chặn triệt để SQL Injection.
- Xử lý JWT Token qua `core/JwtHandler.php` và sanitize input/output qua `core/Security.php` (tránh XSS khi render tại View).

## 3. Frontend Standards (No Heavy Frameworks)

- **UI/CSS**: Tận dụng hệ thống component có sẵn tại `app/View/components/` (`modal.php`, `toast.php`, `badge.php`, `stat-card.php`) và CSS module tại `public/assets/css/`.
- **JavaScript**: Viết Vanilla ES6+, module hóa trong `public/assets/js/` (ví dụ `tasks-kanban.js`, `tasks-gantt.js`, `modal.js`).

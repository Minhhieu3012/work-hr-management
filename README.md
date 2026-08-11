# Work & HR Management System

> Comprehensive Human Resource & Task Management Platform built with PHP (Custom MVC architecture) & MySQL. It streamlines organizational hierarchy, employee contracts, attendance/leave management, project workflows (Kanban & Gantt), and interactive client collaboration.

---

## Overview

**Work & HR Management** (`work-hr-management`) is an all-in-one enterprise management system designed to connect human resource operations directly with day-to-day task execution. The system features modular architecture covering HR record management, payroll/attendance, interactive task boards, and a dedicated Client Portal for external stakeholders.

## Tech Stack

### Frontend:

Core:

- HTML5, CSS3, Vanilla JavaScript (ES6+)

UI Framework:

- Bootstrap 5 (Customized)

Features:

- Dynamic Kanban Board
- Gantt Chart Visualizer
- Client Portal Dashboard
- UI State Components (Modals, Toasts, Empty States, Sidebars)

### Backend:

Core:

- PHP 8+ (Custom Object-Oriented MVC Architecture)
- Routing & Security: Custom Dynamic Router, Role-Based Access Control (RBAC) Middleware, JWT Token Handler, Prepared Statements (SQL Injection Protection), XSS & CSRF Handling.

Database:

- MySQL (Relational Schema with Strict Foreign Keys & utf8mb4 encoding).

---

## Role-Based Access Control (RBAC)

The system manages authorization through defined security middleware across four key roles:

1. **Admin:** System-wide administration, user/account approvals, organization structure, and global security management.
2. **Manager:** Department management, project creation, task assignment, employee leave approval, and workflow review.
3. **Employee:** Profile management, daily web attendance tracking, task status updates (Kanban/Gantt), comment/attachment management, and leave requests.
4. **Client:** Restricted external access via Client Portal to monitor project milestones, task statuses, and submit support inquiries.

---

## Core Modules

### 1. Human Resources Management (HRM)

- **Employee Directory & Contracts:** Complete CRUD for employee profiles, position assignments, department mapping, and document/contract tracking.
- **Department & Organization Setup:** Hierarchical structuring of departments and position tiers.

### 2. Timekeeping & Leave Management

- **Attendance Tracking:** Daily web-based check-in/check-out logs.
- **Leave Request Workflow:** Employees submit leave requests -> Managers review and approve/reject -> Automatic updates to leave balance adjustments.

### 3. Project & Task Flow

- **Visual Workspaces:** Interactive Kanban boards and Gantt charts for project timelines.
- **Task Lifecycle:** Full task management (Create -> Assign -> Execute -> Activity Tracking -> Approval/Review -> Completion).
- **Collaboration Tools:** Comments, file attachments, and activity logs for each task.

---

## Getting Started

### 1. Clone the repository

```bash
git clone https://github.com/Minhhieu3012/work-hr-management.git
cd work-hr-management
```

### 2. Environment Setup

```bash
cp .env.example .env
# Update DB credentials in .env (DB_HOST, DB_NAME, DB_USER, DB_PASS)
```

### 3. Database Setup

Create a new database (e.g., work_hr_management) and execute the SQL scripts in the following sequence:

1. **Base Schema:**

```bash
database/schema.sql
```

2. **Migrations (in order):**

```bash
database/migrations/002_hrm_schema.sql
database/migrations/003_employee_documents.sql
database/migrations/004_operation_flow_schema.sql
```

3. **Seed Data (Optional):**

```bash
database/seeders/002_hrm_seed_data.sql
database/seeders/003_auth_task_test_seed.sql
database/seeders/004_operation_flow_seed.sql
```

### 4. Run the Application

### Option 1: Local Web Server (XAMPP)

Point your Virtual Host directory directly to the `public/` folder so `public/index.php` serves as the single entry point.

- **Base URL:** `http://localhost/work-hr-management/public/`
- **Admin Portal:** `http://localhost/work-hr-management/public/admin/login`
- **Staff Portal:** `http://localhost/work-hr-management/public/staff/login`
- **Client Portal:** `http://localhost/work-hr-management/public/client/login`

---

### Option 2: PHP Built-in Server (Development)

Run the built-in server targeting the `public` directory:

```bash
php -S localhost:8000 -t public
```

Then visit `http://localhost:8000` in your browser.

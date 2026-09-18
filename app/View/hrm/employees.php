<?php
$pageTitle = 'Nhân sự & Tài khoản | Work & HR Management';
$pageCss = ['hrm.css', 'dashboard.css'];
$pageJs = [];
$activeMenu = 'employees';
$topbarTitle = 'Nhân sự & Tài khoản';
$brandName = 'Work & HR Management';

$baseUrl = $baseUrl ?? '/work-hr-management';
$publicUrl = $baseUrl . '/public';
$viewUrl = $baseUrl . '/app/View';

ob_start();
?>

<?php
$pageHeading = 'Nhân sự & Tài khoản';
$pageSubtitle = 'Quản lý nhân sự, tài khoản client/employee và luồng duyệt tài khoản theo vai trò.';
$pageAction = '
    <button class="btn btn-primary" type="button" id="btnOpenOnboardModal" style="display:inline-flex; align-items:center; gap:6px;">
        ＋ Tiếp nhận nhân sự
    </button>
    <button class="btn btn-emerald" type="button" data-open-create-account style="display:none; align-items:center; gap:6px;">
        ＋ Tạo tài khoản
    </button>
    <button class="btn btn-light" type="button" data-refresh-accounts>
        ⟳ Làm mới
    </button>
';
require __DIR__ . '/../components/page-header.php';
?>

<section class="hrm-grid" data-account-governance-page>
    <div class="stat-grid">
        <article class="stat-card">
            <div class="stat-card-icon">◉</div>
            <div class="stat-card-body">
                <span>Tổng tài khoản</span>
                <strong data-stat-total>0</strong>
                <small>Trong phạm vi quyền xem</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon">✓</div>
            <div class="stat-card-body">
                <span>Đang hoạt động</span>
                <strong data-stat-active>0</strong>
                <small>Có thể đăng nhập</small>
            </div>
        </article>

        <article class="stat-card stat-card-warning">
            <div class="stat-card-icon">◷</div>
            <div class="stat-card-body">
                <span>Chờ duyệt</span>
                <strong data-stat-pending>0</strong>
                <small>Đợi Admin xử lý</small>
            </div>
        </article>

        <article class="stat-card stat-card-danger">
            <div class="stat-card-icon">!</div>
            <div class="stat-card-body">
                <span>Bị khóa</span>
                <strong data-stat-suspended>0</strong>
                <small>Suspended</small>
            </div>
        </article>
    </div>

    <article class="card" data-create-account-panel style="display:none;">
        <div class="card-header dashboard-card-title-row">
            <div>
                <h2>Tạo tài khoản chờ duyệt</h2>
                <p>Manager tạo Employee/Client. Admin duyệt xong tài khoản mới đăng nhập được.</p>
            </div>

            <button class="btn btn-light" type="button" data-close-create-account>
                Đóng
            </button>
        </div>

        <div class="card-body">
            <form data-create-account-form>
                <div class="hrm-form-grid">
                    <div class="form-group">
                        <label class="form-label" for="account_full_name">Họ và tên</label>
                        <input id="account_full_name" class="form-control" type="text" name="full_name"
                            placeholder="VD: Nguyễn Văn A" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="account_email">Email</label>
                        <input id="account_email" class="form-control" type="email" name="email"
                            placeholder="name@agency.vn" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="account_password">Mật khẩu mặc định</label>
                        <input id="account_password" class="form-control" type="text" name="password" value="123456"
                            minlength="6" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="account_role">Loại tài khoản</label>
                        <select id="account_role" class="form-select" name="role" required>
                            <option value="employee">Employee</option>
                            <option value="client">Client</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="account_department">Phòng ban</label>
                        <select id="account_department" class="form-select" name="department_id">
                            <option value="">Tự chọn mặc định</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="account_position">Chức vụ</label>
                        <select id="account_position" class="form-select" name="position_id">
                            <option value="">Tự chọn mặc định</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="account_phone">Số điện thoại</label>
                        <input id="account_phone" class="form-control" type="text" name="phone" maxlength="20"
                            placeholder="Tuỳ chọn">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="account_gender">Giới tính</label>
                        <select id="account_gender" class="form-select" name="gender">
                            <option value="">Chưa cập nhật</option>
                            <option value="male">Nam</option>
                            <option value="female">Nữ</option>
                            <option value="other">Khác</option>
                        </select>
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:12px; margin-top: 22px; flex-wrap:wrap;">
                    <button class="btn btn-light" type="button" data-close-create-account>
                        Hủy
                    </button>
                    <button class="btn btn-primary" type="submit">
                        Gửi Admin duyệt
                    </button>
                </div>
            </form>
        </div>
    </article>

    <article class="card" data-admin-pending-card style="display:none;">
        <div class="card-header dashboard-card-title-row">
            <div>
                <h2>Tài khoản chờ Admin duyệt</h2>
                <p>Approve để kích hoạt đăng nhập, Reject để khóa tài khoản.</p>
            </div>

            <button class="btn btn-soft" type="button" data-refresh-pending>
                ⟳ Tải lại pending
            </button>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nhân sự</th>
                            <th>Vai trò</th>
                            <th>Manager tạo</th>
                            <th>Ngày tạo</th>
                            <th>Trạng thái</th>
                            <th style="text-align:right;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody data-pending-table-body>
                        <tr>
                            <td colspan="6">Đang tải tài khoản chờ duyệt...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </article>

    <article class="card" data-project-member-card style="display:none;">
        <div class="card-header dashboard-card-title-row">
            <div>
                <h2>Nhân sự theo Project</h2>
                <p>Manager thêm hoặc gỡ Employee khỏi từng project. Nhân sự được thêm vào đây mới xuất hiện trong
                    dropdown giao task của project đó.</p>
            </div>

            <button class="btn btn-soft" type="button" data-refresh-project-members>
                ⟳ Tải lại nhân sự project
            </button>
        </div>

        <div class="card-body">
            <div class="task-filter-bar" style="margin-bottom: 18px;">
                <select class="form-select" data-project-member-project-select>
                    <option value="">Đang tải project...</option>
                </select>

                <select class="form-select" data-project-member-employee-select>
                    <option value="">Đang tải Employee...</option>
                </select>

                <button class="btn btn-primary" type="button" data-add-project-member>
                    ＋ Thêm vào project
                </button>
            </div>

            <div data-project-member-summary style="margin-bottom: 16px; color: var(--text-muted);">
                Chọn project để xem danh sách nhân sự đang tham gia.
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nhân sự</th>
                            <th>Phòng ban</th>
                            <th>Chức vụ</th>
                            <th>Ngày tham gia</th>
                            <th style="text-align:right;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody data-project-member-table-body>
                        <tr>
                            <td colspan="5">Chưa chọn project.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </article>

    <article class="card">
        <div class="card-header dashboard-card-title-row">
            <div>
                <h2>Danh sách tài khoản</h2>
                <p data-list-description>Danh sách tài khoản trong phạm vi quyền của bạn.</p>
            </div>
        </div>

        <div class="card-body">
            <div class="task-filter-bar" style="margin-bottom: 18px;">
                <div class="input-with-icon">
                    <span class="input-icon">⌕</span>
                    <input class="form-control" type="search" placeholder="Tìm tên, email, mã..." data-account-search>
                </div>

                <select class="form-select" data-account-role-filter>
                    <option value="">Tất cả vai trò</option>
                    <option value="admin">Admin</option>
                    <option value="manager">Manager</option>
                    <option value="employee">Employee</option>
                    <option value="client">Client</option>
                </select>

                <select class="form-select" data-account-status-filter>
                    <option value="">Tất cả trạng thái</option>
                    <option value="active">Active</option>
                    <option value="inactive">Chờ duyệt</option>
                    <option value="suspended">Suspended</option>
                    <option value="resigned">Resigned</option>
                </select>

                <button class="btn btn-soft" type="button" data-apply-account-filter>
                    Lọc
                </button>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tài khoản</th>
                            <th>Vai trò</th>
                            <th>Phòng ban</th>
                            <th>Chức vụ</th>
                            <th>Manager</th>
                            <th>Trạng thái</th>
                            <th>Ngày vào</th>
                            <th style="text-align:right;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody data-account-table-body>
                        <tr>
                            <td colspan="8">Đang tải danh sách tài khoản...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </article>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const apiRoot = '<?php echo $publicUrl; ?>';

    const createButton = document.querySelector('[data-open-create-account]');
    const refreshButton = document.querySelector('[data-refresh-accounts]');
    const createPanel = document.querySelector('[data-create-account-panel]');
    const createForm = document.querySelector('[data-create-account-form]');
    const closeCreateButtons = document.querySelectorAll('[data-close-create-account]');

    const adminPendingCard = document.querySelector('[data-admin-pending-card]');
    const pendingBody = document.querySelector('[data-pending-table-body]');
    const refreshPendingButton = document.querySelector('[data-refresh-pending]');

    const accountBody = document.querySelector('[data-account-table-body]');
    const searchInput = document.querySelector('[data-account-search]');
    const roleFilter = document.querySelector('[data-account-role-filter]');
    const statusFilter = document.querySelector('[data-account-status-filter]');
    const applyFilterButton = document.querySelector('[data-apply-account-filter]');
    const listDescription = document.querySelector('[data-list-description]');

    const projectMemberCard = document.querySelector('[data-project-member-card]');
    const projectMemberProjectSelect = document.querySelector('[data-project-member-project-select]');
    const projectMemberEmployeeSelect = document.querySelector('[data-project-member-employee-select]');
    const projectMemberTableBody = document.querySelector('[data-project-member-table-body]');
    const projectMemberSummary = document.querySelector('[data-project-member-summary]');
    const refreshProjectMembersButton = document.querySelector('[data-refresh-project-members]');
    const addProjectMemberButton = document.querySelector('[data-add-project-member]');

    const departmentSelect = document.querySelector('#account_department');
    const positionSelect = document.querySelector('#account_position');

    const statTotal = document.querySelector('[data-stat-total]');
    const statActive = document.querySelector('[data-stat-active]');
    const statPending = document.querySelector('[data-stat-pending]');
    const statSuspended = document.querySelector('[data-stat-suspended]');

    let currentUser = null;
    let accounts = [];
    let pendingAccounts = [];
    let projectOptions = {
        projects: [],
        employees: []
    };
    let selectedProjectMembers = [];

    function getToken() {
        return localStorage.getItem('cah_auth_token') || localStorage.getItem('cah_token') || '';
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function toast(type, title, message) {
        if (window.CAHToast && typeof window.CAHToast[type] === 'function') {
            window.CAHToast[type](title, message);
            return;
        }
        if (type === 'error') {
            console.error(title, message);
            return;
        }
        console.log(title, message);
    }

    async function apiRequest(path, options = {}) {
        const token = getToken();

        const headers = {
            Accept: 'application/json',
            ...(options.headers || {})
        };

        if (token) {
            headers.Authorization = 'Bearer ' + token;
        }

        const response = await fetch(apiRoot + path, {
            credentials: 'same-origin',
            ...options,
            headers
        });

        const text = await response.text();
        let payload;

        try {
            payload = JSON.parse(text);
        } catch (error) {
            console.error('API không trả JSON:', path, text);
            throw new Error('Server không trả JSON hợp lệ.');
        }

        if (!response.ok || payload.status === 'error') {
            throw new Error(payload.message || 'Yêu cầu không thành công.');
        }

        return payload;
    }

    function roleLabel(role) {
        const map = {
            admin: 'Admin',
            manager: 'Manager',
            employee: 'Employee',
            client: 'Client'
        };
        return map[String(role || '').toLowerCase()] || role || 'Employee';
    }

    function statusLabel(status) {
        const map = {
            active: 'Active',
            inactive: 'Chờ duyệt',
            suspended: 'Suspended',
            resigned: 'Resigned'
        };
        return map[String(status || '').toLowerCase()] || status || 'Chưa rõ';
    }

    function statusBadgeClass(status) {
        status = String(status || '').toLowerCase();
        if (status === 'active') return 'badge-success';
        if (status === 'inactive') return 'badge-warning';
        if (status === 'suspended') return 'badge-danger';
        return 'badge-info';
    }

    function formatDate(value) {
        if (!value) return 'Chưa cập nhật';
        const date = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) return value;
        return date.toLocaleDateString('vi-VN');
    }

    function initialsFromName(name) {
        const parts = String(name || '').trim().split(/\s+/).filter(Boolean);
        if (parts.length === 0) return 'CA';
        const first = parts[0].charAt(0);
        const last = parts.length > 1 ? parts[parts.length - 1].charAt(0) : '';
        return (first + last).toUpperCase();
    }

    function accountIdentityHtml(account) {
        const name = account.full_name || account.name || 'Chưa có tên';
        const email = account.email || 'Chưa có email';
        const code = account.employee_code || ('#' + account.id);

        return `
            <div style="display:flex; align-items:center; gap:12px;">
                <span class="client-avatar" style="width:38px;height:38px;font-size:12px;">
                    ${escapeHtml(initialsFromName(name))}
                </span>
                <div>
                    <strong>${escapeHtml(name)}</strong>
                    <small style="display:block; color: var(--text-muted); margin-top:4px;">
                        ${escapeHtml(email)} • ${escapeHtml(code)}
                    </small>
                </div>
            </div>
        `;
    }

    async function loadCurrentUser() {
        const payload = await apiRequest('/api/auth/me');
        currentUser = payload.data && payload.data.user ? payload.data.user : payload.data;

        const role = String(currentUser?.role || '').toLowerCase();

        if (role === 'manager') {
            if (createButton) createButton.style.display = 'inline-flex';
            if (projectMemberCard) projectMemberCard.style.display = 'block';
            if (listDescription) listDescription.textContent =
                'Manager xem các tài khoản mình tạo/quản lý. Tài khoản mới sẽ chờ Admin duyệt.';
        }

        if (role === 'admin') {
            if (adminPendingCard) adminPendingCard.style.display = 'block';
            if (listDescription) listDescription.textContent =
                'Admin xem toàn bộ tài khoản và duyệt các tài khoản Manager gửi lên.';
        }
    }

    async function loadOrganizationOptions() {
        try {
            const payload = await apiRequest('/api/organization/data');
            const data = payload.data || {};
            const departments = Array.isArray(data.departments) ? data.departments : [];
            const positions = Array.isArray(data.positions) ? data.positions : [];

            departmentSelect.innerHTML = '<option value="">Tự chọn mặc định</option>' + departments.map(
                function(department) {
                    return `<option value="${department.id}">${escapeHtml(department.name)}</option>`;
                }).join('');

            positionSelect.innerHTML = '<option value="">Tự chọn mặc định</option>' + positions.map(
                function(position) {
                    return `<option value="${position.id}">${escapeHtml(position.name)}</option>`;
                }).join('');
        } catch (error) {
            departmentSelect.innerHTML = '<option value="">Tự chọn mặc định</option>';
            positionSelect.innerHTML = '<option value="">Tự chọn mặc định</option>';
        }
    }

    function isManager() {
        return String(currentUser?.role || '').toLowerCase() === 'manager';
    }

    function getProjectId(project) {
        return Number(project?.id || project?.project_id || 0);
    }

    function getEmployeeId(employee) {
        return Number(employee?.id || employee?.employee_id || 0);
    }

    function projectOptionHtml(projects, selectedId = '') {
        if (!Array.isArray(projects) || projects.length === 0) {
            return '<option value="">Chưa có project để quản lý</option>';
        }

        return '<option value="">Chọn project</option>' + projects.map(function(project) {
            const id = getProjectId(project);
            const selected = Number(id) === Number(selectedId) ? 'selected' : '';
            const label = project.name || project.project_name || ('Project #' + id);

            return `<option value="${escapeHtml(id)}" ${selected}>${escapeHtml(label)}</option>`;
        }).join('');
    }

    function employeeOptionHtml(employees, selectedId = '', members = []) {
        const memberIds = new Set((Array.isArray(members) ? members : []).map(function(member) {
            return getEmployeeId(member);
        }));

        const availableEmployees = (Array.isArray(employees) ? employees : []).filter(function(employee) {
            const id = getEmployeeId(employee);
            return id > 0 && !memberIds.has(id);
        });

        if (availableEmployees.length === 0) {
            return '<option value="">Không còn Employee active để thêm</option>';
        }

        return '<option value="">Chọn Employee active để thêm</option>' + availableEmployees.map(function(
            employee) {
            const id = getEmployeeId(employee);
            const selected = Number(id) === Number(selectedId) ? 'selected' : '';
            const labelParts = [
                employee.full_name || employee.name || ('Employee #' + id),
                employee.email || '',
                employee.department_name || '',
            ].filter(Boolean);

            return `<option value="${escapeHtml(id)}" ${selected}>${escapeHtml(labelParts.join(' • '))}</option>`;
        }).join('');
    }

    function extractProjectsFromPayload(payload) {
        const data = payload?.data || {};
        if (Array.isArray(data.projects)) return data.projects;
        if (Array.isArray(data.data?.projects)) return data.data.projects;
        if (Array.isArray(data)) return data;
        return [];
    }

    function extractEmployeesFromPayload(payload) {
        const data = payload?.data || {};
        if (Array.isArray(data.employees)) return data.employees;
        if (Array.isArray(data.assignees)) return data.assignees;
        if (Array.isArray(data.data?.employees)) return data.data.employees;
        if (Array.isArray(data.data?.assignees)) return data.data.assignees;
        return [];
    }

    function renderProjectMemberOptions() {
        if (!projectMemberProjectSelect || !projectMemberEmployeeSelect) return;
        const selectedProjectId = projectMemberProjectSelect.value || '';
        projectMemberProjectSelect.innerHTML = projectOptionHtml(projectOptions.projects, selectedProjectId);
        projectMemberEmployeeSelect.innerHTML = employeeOptionHtml(projectOptions.employees, '',
            selectedProjectMembers);
    }

    async function loadProjectOptions() {
        if (!isManager() || !projectMemberCard) return;

        projectMemberSummary.textContent = 'Đang tải project và danh sách Employee...';

        try {
            const optionsPayload = await apiRequest('/api/tasks/options');
            projectOptions.projects = extractProjectsFromPayload(optionsPayload);
            projectOptions.employees = extractEmployeesFromPayload(optionsPayload);

            renderProjectMemberOptions();

            if (projectOptions.projects.length === 0) {
                projectMemberSummary.textContent =
                    'Chưa có project nào trong phạm vi quản lý của Manager hiện tại.';
                projectMemberTableBody.innerHTML =
                    `<tr><td colspan="5">Chưa có project để thêm nhân sự.</td></tr>`;
                return;
            }

            if (!projectMemberProjectSelect.value) {
                projectMemberProjectSelect.value = String(getProjectId(projectOptions.projects[0]));
            }

            await loadProjectMembers(projectMemberProjectSelect.value);
        } catch (error) {
            projectMemberSummary.textContent = 'Không thể tải project/employees: ' + error.message;
            projectMemberTableBody.innerHTML =
                `<tr><td colspan="5" style="color: var(--danger);">Không thể tải dữ liệu project: ${escapeHtml(error.message)}</td></tr>`;
        }
    }

    async function loadProjectMembers(projectId = null) {
        if (!isManager() || !projectMemberCard) return;

        const selectedProjectId = projectId || projectMemberProjectSelect.value;

        if (!selectedProjectId) {
            selectedProjectMembers = [];
            projectMemberSummary.textContent = 'Chọn project để xem danh sách nhân sự đang tham gia.';
            projectMemberTableBody.innerHTML = `<tr><td colspan="5">Chưa chọn project.</td></tr>`;
            renderProjectMemberOptions();
            return;
        }

        projectMemberTableBody.innerHTML = `<tr><td colspan="5">Đang tải nhân sự project...</td></tr>`;

        try {
            const payload = await apiRequest('/api/projects/' + encodeURIComponent(selectedProjectId) +
                '/members');
            const data = payload.data || {};

            if (Array.isArray(data)) selectedProjectMembers = data;
            else if (Array.isArray(data.members)) selectedProjectMembers = data.members;
            else if (Array.isArray(data.data)) selectedProjectMembers = data.data;
            else selectedProjectMembers = [];

            renderProjectMembers();
            renderProjectMemberOptions();
        } catch (error) {
            projectMemberTableBody.innerHTML =
                `<tr><td colspan="5" style="color: var(--danger);">Không thể tải nhân sự project: ${escapeHtml(error.message)}</td></tr>`;
        }
    }

    function renderProjectMembers() {
        const selectedProjectId = projectMemberProjectSelect.value;
        const selectedProject = projectOptions.projects.find(function(project) {
            return getProjectId(project) === Number(selectedProjectId);
        });

        if (!selectedProjectId) {
            projectMemberSummary.textContent = 'Chọn project để xem danh sách nhân sự đang tham gia.';
        } else {
            projectMemberSummary.textContent =
                `${selectedProjectMembers.length} nhân sự đang tham gia project "${selectedProject?.name || selectedProject?.project_name || ('#' + selectedProjectId)}".`;
        }

        if (selectedProjectMembers.length === 0) {
            projectMemberTableBody.innerHTML =
                `<tr><td colspan="5">Project này chưa có Employee nào. Hãy thêm nhân sự trước khi giao task.</td></tr>`;
            return;
        }

        projectMemberTableBody.innerHTML = selectedProjectMembers.map(function(member) {
            const memberId = getEmployeeId(member);
            return `
                <tr data-project-member-id="${escapeHtml(memberId)}">
                    <td>${accountIdentityHtml(member)}</td>
                    <td>${escapeHtml(member.department_name || 'Chưa cập nhật')}</td>
                    <td>${escapeHtml(member.position_name || 'Chưa cập nhật')}</td>
                    <td>${escapeHtml(formatDate(member.joined_at || member.created_at))}</td>
                    <td style="text-align:right;">
                        <button class="btn btn-light btn-sm" type="button" data-remove-project-member="${escapeHtml(memberId)}">
                            Gỡ khỏi project
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    async function addProjectMember() {
        const projectId = projectMemberProjectSelect.value;
        const employeeId = projectMemberEmployeeSelect.value;

        if (!projectId) {
            toast('error', 'Chưa chọn project', 'Vui lòng chọn project cần cập nhật nhân sự.');
            return;
        }

        if (!employeeId) {
            toast('error', 'Chưa chọn nhân sự', 'Vui lòng chọn Employee active để thêm vào project.');
            return;
        }

        addProjectMemberButton.disabled = true;
        addProjectMemberButton.textContent = 'Đang thêm...';

        try {
            await apiRequest('/api/projects/' + encodeURIComponent(projectId) + '/members', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    employee_id: Number(employeeId)
                })
            });

            toast('success', 'Đã thêm nhân sự',
                'Employee đã được thêm vào project. Dropdown giao task sẽ nhận nhân sự này.');
            await loadProjectMembers(projectId);
        } catch (error) {
            toast('error', 'Không thể thêm nhân sự', error.message);
        } finally {
            addProjectMemberButton.disabled = false;
            addProjectMemberButton.textContent = '＋ Thêm vào project';
        }
    }

    async function removeProjectMember(employeeId) {
        const projectId = projectMemberProjectSelect.value;

        if (!projectId || !employeeId) {
            toast('error', 'Thiếu dữ liệu', 'Không xác định được project hoặc employee cần gỡ.');
            return;
        }

        const member = selectedProjectMembers.find(function(item) {
            return getEmployeeId(item) === Number(employeeId);
        });

        const ok = window.confirm(`Gỡ ${member?.full_name || 'nhân sự này'} khỏi project?`);
        if (!ok) return;

        try {
            await apiRequest('/api/projects/' + encodeURIComponent(projectId) + '/members/' +
                encodeURIComponent(employeeId), {
                    method: 'DELETE'
                });

            toast('success', 'Đã gỡ nhân sự', 'Employee đã được gỡ khỏi project.');
            await loadProjectMembers(projectId);
        } catch (error) {
            toast('error', 'Không thể gỡ nhân sự', error.message);
        }
    }

    function buildEmployeesQuery() {
        const params = new URLSearchParams();
        const search = String(searchInput.value || '').trim();
        const role = String(roleFilter.value || '').trim();
        const status = String(statusFilter.value || '').trim();

        if (search) params.set('search', search);
        if (role) params.set('role', role);
        if (status) params.set('status', status);
        params.set('limit', '200');

        return params.toString();
    }

    async function loadAccounts() {
        accountBody.innerHTML = `<tr><td colspan="8">Đang tải danh sách tài khoản...</td></tr>`;

        try {
            const query = buildEmployeesQuery();
            const payload = await apiRequest('/api/employees' + (query ? '?' + query : ''));

            accounts = Array.isArray(payload.data) ? payload.data : [];

            renderAccounts();
            updateStats();
        } catch (error) {
            accountBody.innerHTML =
                `<tr><td colspan="8" style="color: var(--danger);">Không thể tải tài khoản: ${escapeHtml(error.message)}</td></tr>`;
        }
    }

    async function loadPendingAccounts() {
        if (!currentUser || String(currentUser.role || '').toLowerCase() !== 'admin') {
            return;
        }

        pendingBody.innerHTML = `<tr><td colspan="6">Đang tải tài khoản chờ duyệt...</td></tr>`;

        try {
            const payload = await apiRequest('/api/admin/accounts/pending');
            pendingAccounts = Array.isArray(payload.data) ? payload.data : [];

            renderPendingAccounts();
            updateStats();
        } catch (error) {
            pendingBody.innerHTML =
                `<tr><td colspan="6" style="color: var(--danger);">Không thể tải pending: ${escapeHtml(error.message)}</td></tr>`;
        }
    }

    function renderAccounts() {
        if (accounts.length === 0) {
            accountBody.innerHTML = `<tr><td colspan="8">Không có tài khoản phù hợp bộ lọc.</td></tr>`;
            return;
        }

        accountBody.innerHTML = accounts.map(function(account) {
            // Chỉ hiển thị nút soi Deadline với role Employee
            const btnHtml = account.role === 'employee' ?
                `<button class="btn btn-soft btn-sm" type="button" data-view-upcoming="${account.id}" title="Xem task sắp hết hạn của nhân viên này">🔎 Deadline</button>` :
                '';

            return `
                <tr>
                    <td>${accountIdentityHtml(account)}</td>
                    <td>${escapeHtml(roleLabel(account.role))}</td>
                    <td>${escapeHtml(account.department_name || 'Chưa cập nhật')}</td>
                    <td>${escapeHtml(account.position_name || 'Chưa cập nhật')}</td>
                    <td>${escapeHtml(account.manager_name || 'Chưa gán')}</td>
                    <td>
                        <span class="badge ${statusBadgeClass(account.status)}">
                            ${escapeHtml(statusLabel(account.status))}
                        </span>
                    </td>
                    <td>${escapeHtml(formatDate(account.hire_date || account.created_at))}</td>
                    <td style="text-align:right;">${btnHtml}</td>
                </tr>
            `;
        }).join('');
    }

    function renderPendingAccounts() {
        if (pendingAccounts.length === 0) {
            pendingBody.innerHTML = `<tr><td colspan="6">Không có tài khoản nào đang chờ duyệt.</td></tr>`;
            return;
        }

        pendingBody.innerHTML = pendingAccounts.map(function(account) {
            return `
                <tr data-pending-account-id="${account.id}">
                    <td>${accountIdentityHtml(account)}</td>
                    <td>${escapeHtml(roleLabel(account.role))}</td>
                    <td>${escapeHtml(account.manager_name || 'Không rõ')}</td>
                    <td>${escapeHtml(formatDate(account.created_at))}</td>
                    <td>
                        <span class="badge badge-warning">
                            ${escapeHtml(statusLabel(account.status))}
                        </span>
                    </td>
                    <td style="text-align:right;">
                        <div style="display:flex; justify-content:flex-end; gap:8px; flex-wrap:wrap;">
                            <button class="btn btn-emerald btn-sm" type="button" data-approve-account="${account.id}">Duyệt</button>
                            <button class="btn btn-light btn-sm" type="button" data-reject-account="${account.id}">Từ chối</button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function updateStats() {
        const merged = [...accounts];

        pendingAccounts.forEach(function(pending) {
            if (!merged.some(function(account) {
                    return Number(account.id) === Number(pending.id);
                })) {
                merged.push(pending);
            }
        });

        const total = merged.length;
        const active = merged.filter(function(account) {
            return String(account.status || '').toLowerCase() === 'active';
        }).length;
        const pending = merged.filter(function(account) {
            return String(account.status || '').toLowerCase() === 'inactive';
        }).length;
        const suspended = merged.filter(function(account) {
            return String(account.status || '').toLowerCase() === 'suspended';
        }).length;

        statTotal.textContent = String(total);
        statActive.textContent = String(active);
        statPending.textContent = String(pending);
        statSuspended.textContent = String(suspended);
    }

    function toggleCreatePanel(forceOpen = null) {
        const isOpen = createPanel.style.display !== 'none';
        const nextOpen = forceOpen === null ? !isOpen : Boolean(forceOpen);

        createPanel.style.display = nextOpen ? 'block' : 'none';

        if (nextOpen) {
            createPanel.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    }

    async function createPendingAccount(form) {
        const formData = new FormData(form);

        const payload = {
            full_name: String(formData.get('full_name') || '').trim(),
            email: String(formData.get('email') || '').trim(),
            password: String(formData.get('password') || '').trim(),
            role: String(formData.get('role') || 'employee').trim(),
            department_id: formData.get('department_id') ? Number(formData.get('department_id')) : null,
            position_id: formData.get('position_id') ? Number(formData.get('position_id')) : null,
            phone: String(formData.get('phone') || '').trim(),
            gender: String(formData.get('gender') || '').trim()
        };

        if (!payload.full_name || !payload.email || !payload.password || !payload.role) {
            toast('error', 'Thiếu thông tin', 'Vui lòng nhập đầy đủ họ tên, email, mật khẩu và vai trò.');
            return;
        }

        try {
            await apiRequest('/api/accounts', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            toast('success', 'Đã gửi Admin duyệt', 'Tài khoản mới đang ở trạng thái chờ duyệt.');
            form.reset();
            document.querySelector('#account_password').value = '123456';
            toggleCreatePanel(false);

            await loadAccounts();
            await loadProjectOptions();
        } catch (error) {
            toast('error', 'Không thể tạo tài khoản', error.message);
        }
    }

    async function approveAccount(id) {
        try {
            await apiRequest('/api/admin/accounts/' + encodeURIComponent(id) + '/approve', {
                method: 'PATCH'
            });
            toast('success', 'Đã duyệt tài khoản', 'Tài khoản hiện có thể đăng nhập.');
            await loadPendingAccounts();
            await loadAccounts();
        } catch (error) {
            toast('error', 'Không thể duyệt', error.message);
        }
    }

    async function rejectAccount(id) {
        const ok = window.confirm('Từ chối tài khoản này? Tài khoản sẽ chuyển sang trạng thái Suspended.');
        if (!ok) return;

        try {
            await apiRequest('/api/admin/accounts/' + encodeURIComponent(id) + '/reject', {
                method: 'PATCH'
            });
            toast('success', 'Đã từ chối tài khoản', 'Tài khoản đã chuyển sang Suspended.');
            await loadPendingAccounts();
            await loadAccounts();
        } catch (error) {
            toast('error', 'Không thể từ chối', error.message);
        }
    }

    // ĐÃ THÊM: Logic Gọi API và Hiển thị Modal "Task sắp hết hạn"
    async function viewUpcomingTasks(employeeId) {
        const emp = accounts.find(a => Number(a.id) === Number(employeeId));
        const empName = emp ? emp.full_name : 'nhân viên';

        if (window.CAHModal) {
            CAHModal.open({
                title: `🔎 Task sắp hết hạn - ${escapeHtml(empName)}`,
                subtitle: `Đang tải dữ liệu...`,
                body: `<div style="text-align:center; padding:20px;">Vui lòng chờ trong giây lát.</div>`
            });
        }

        try {
            const payload = await apiRequest(`/api/tasks/upcoming?employee_id=${employeeId}`);
            const tasks = payload.data || [];

            let bodyHtml = '';
            if (tasks.length === 0) {
                bodyHtml = `<div class="ui-empty-state" style="padding: 30px;">
                                <div class="ui-empty-icon">🎉</div>
                                <div class="ui-empty-content">
                                    <h3>Thảnh thơi!</h3>
                                    <p>Nhân viên này không có task nào trễ hạn hay sắp đến hạn trong 3 ngày tới.</p>
                                </div>
                            </div>`;
            } else {
                const rows = tasks.map(task => {
                    let deadlineStyle = '';
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    const dDate = new Date(task.deadline);

                    if (dDate < today) deadlineStyle = 'color: var(--danger); font-weight: bold;';
                    else if (dDate.getTime() === today.getTime()) deadlineStyle =
                        'color: #d97706; font-weight: bold;';

                    return `
                        <tr>
                            <td>${escapeHtml(task.project_name)}</td>
                            <td><strong>${escapeHtml(task.title)}</strong></td>
                            <td><span class="badge ${statusBadgeClass(task.status)}">${escapeHtml(task.status)}</span></td>
                            <td style="${deadlineStyle}">${escapeHtml(task.deadline)}</td>
                        </tr>
                    `;
                }).join('');

                bodyHtml = `
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Dự án</th>
                                    <th>Tên Task</th>
                                    <th>Trạng thái</th>
                                    <th>Deadline</th>
                                </tr>
                            </thead>
                            <tbody>${rows}</tbody>
                        </table>
                    </div>
                `;
            }

            if (window.CAHModal) {
                CAHModal.open({
                    title: `🔎 Task sắp hết hạn - ${escapeHtml(empName)}`,
                    subtitle: `Các task chưa hoàn thành (có deadline trong 3 ngày tới hoặc đã trễ hạn).`,
                    body: bodyHtml
                });
            } else {
                alert("Dữ liệu tải thành công nhưng thiếu module UI CAHModal.");
            }

        } catch (error) {
            if (window.CAHModal) {
                CAHModal.open({
                    title: `🔎 Task sắp hết hạn - ${escapeHtml(empName)}`,
                    subtitle: `Lỗi tải dữ liệu.`,
                    body: `<div style="color:var(--danger); padding:20px; text-align:center;">Lỗi: ${escapeHtml(error.message)}</div>`
                });
            }
        }
    }

    if (createButton) {
        createButton.addEventListener('click', function() {
            toggleCreatePanel(true);
        });
    }

    if (refreshButton) {
        refreshButton.addEventListener('click', async function() {
            await loadAccounts();
            await loadPendingAccounts();
            await loadProjectOptions();
        });
    }

    if (refreshPendingButton) {
        refreshPendingButton.addEventListener('click', loadPendingAccounts);
    }

    if (closeCreateButtons && closeCreateButtons.length > 0) {
        closeCreateButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                toggleCreatePanel(false);
            });
        });
    }

    if (createForm) {
        createForm.addEventListener('submit', function(event) {
            event.preventDefault();
            createPendingAccount(createForm);
        });
    }

    if (applyFilterButton) {
        applyFilterButton.addEventListener('click', loadAccounts);
    }

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            window.clearTimeout(searchInput._timer);
            searchInput._timer = window.setTimeout(loadAccounts, 280);
        });
    }

    if (roleFilter) {
        roleFilter.addEventListener('change', loadAccounts);
    }

    if (statusFilter) {
        statusFilter.addEventListener('change', loadAccounts);
    }

    if (projectMemberProjectSelect) {
        projectMemberProjectSelect.addEventListener('change', function() {
            loadProjectMembers(projectMemberProjectSelect.value);
        });
    }

    if (refreshProjectMembersButton) {
        refreshProjectMembersButton.addEventListener('click', async function() {
            await loadProjectOptions();
            if (projectMemberProjectSelect && projectMemberProjectSelect.value) {
                await loadProjectMembers(projectMemberProjectSelect.value);
            }
        });
    }

    if (addProjectMemberButton) {
        addProjectMemberButton.addEventListener('click', addProjectMember);
    }

    document.addEventListener('click', function(event) {
        const approveButton = event.target.closest('[data-approve-account]');
        const rejectButton = event.target.closest('[data-reject-account]');
        const removeProjectMemberButton = event.target.closest('[data-remove-project-member]');
        const viewUpcomingButton = event.target.closest('[data-view-upcoming]');

        if (removeProjectMemberButton) {
            removeProjectMember(removeProjectMemberButton.getAttribute('data-remove-project-member'));
        }

        if (approveButton) {
            approveAccount(approveButton.getAttribute('data-approve-account'));
        }

        if (rejectButton) {
            rejectAccount(rejectButton.getAttribute('data-reject-account'));
        }

        if (viewUpcomingButton) {
            viewUpcomingTasks(viewUpcomingButton.getAttribute('data-view-upcoming'));
        }
    });

    // ==========================================
    // LOGIC DEMO TIẾP NHẬN NHÂN SỰ (sp_OnboardNewEmployee)
    // ==========================================
    const btnOpenOnboard = document.querySelector('#btnOpenOnboardModal');
    const modalOnboard = document.querySelector('#onboardModal');
    const btnCloseOnboard = document.querySelector('#btnCloseOnboardModal');
    const btnCancelOnboard = document.querySelector('#btnCancelOnboard');
    const formOnboard = document.querySelector('#onboardForm');
    const deptOnboardSelect = document.querySelector('#onboardDeptSelect');
    const posOnboardSelect = document.querySelector('#onboardPosSelect');

    if (btnOpenOnboard && modalOnboard) {
        btnOpenOnboard.addEventListener('click', async () => {
            // Mở modal ngay lập tức để người dùng thấy phản hồi
            modalOnboard.style.display = 'flex';

            const randomNum = Math.floor(1000 + Math.random() * 9000);
            const empCodeInput = document.querySelector('#onboardEmpCode');
            if (empCodeInput) empCodeInput.value = 'EMP-' + randomNum;

            try {
                const payload = await apiRequest('/api/organization/data');
                const orgData = payload.data || {};
                if (deptOnboardSelect && Array.isArray(orgData.departments) && orgData.departments
                    .length > 0) {
                    deptOnboardSelect.innerHTML = orgData.departments.map(d =>
                        `<option value="${d.id}">${escapeHtml(d.name)}</option>`).join('');
                }
                if (posOnboardSelect && Array.isArray(orgData.positions) && orgData.positions
                    .length > 0) {
                    posOnboardSelect.innerHTML = orgData.positions.map(p =>
                        `<option value="${p.id}">${escapeHtml(p.name)}</option>`).join('');
                }
            } catch (e) {
                console.warn('Không tải được danh mục org:', e);
            }
        });

        const closeOnboard = () => {
            modalOnboard.style.display = 'none';
        };
        if (btnCloseOnboard) btnCloseOnboard.addEventListener('click', closeOnboard);
        if (btnCancelOnboard) btnCancelOnboard.addEventListener('click', closeOnboard);

        if (formOnboard) {
            formOnboard.addEventListener('submit', async (e) => {
                e.preventDefault();
                const salary = parseFloat(document.querySelector('#onboardSalary').value);
                const payload = {
                    full_name: document.querySelector('#onboardFullName').value.trim(),
                    email: document.querySelector('#onboardEmail').value.trim(),
                    employee_code: document.querySelector('#onboardEmpCode').value.trim(),
                    department_id: parseInt(deptOnboardSelect ? deptOnboardSelect.value : 1) ||
                        1,
                    position_id: parseInt(posOnboardSelect ? posOnboardSelect.value : 1) || 1,
                    salary: salary,
                    contract_salary: salary,
                    contract_type: document.querySelector('#onboardContractType').value,
                    hire_date: document.querySelector('#onboardHireDate').value,
                    password: 'password123',
                    role: 'employee',
                    status: 'active'
                };

                const submitBtn = document.querySelector('#btnSubmitOnboard');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Đang thực thi Stored Procedure...';
                }

                try {
                    const res = await apiRequest('/api/employees', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });

                    alert('✅ TIẾP NHẬN THÀNH CÔNG (TRANSACTION COMMIT):\n\n' + res.message +
                        '\n\n-> Nhân viên mới và Hợp đồng lao động khởi điểm đã được ghi nhận vào CSDL trong cùng 1 Giao dịch!'
                    );
                    modalOnboard.style.display = 'none';
                    formOnboard.reset();
                    await loadAccounts();
                    await loadPendingAccounts();
                } catch (err) {
                    alert('❌ GIAO DỊCH THẤT BẠI (TRANSACTION ROLLBACK):\n\n' + err.message +
                        '\n\n-> Do vi phạm ràng buộc dữ liệu, Procedure đã tự động ROLLBACK toàn bộ, không tạo nhân viên dở dang!'
                    );
                } finally {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Xác nhận Tiếp nhận';
                    }
                }
            });
        }
    }

    async function init() {
        try {
            await loadCurrentUser();
        } catch (e) {
            console.warn('loadCurrentUser error:', e);
        }

        try {
            await loadOrganizationOptions();
        } catch (e) {
            console.warn('loadOrganizationOptions error:', e);
        }

        try {
            await loadAccounts();
        } catch (e) {
            console.warn('loadAccounts error:', e);
        }

        try {
            await loadPendingAccounts();
        } catch (e) {
            console.warn('loadPendingAccounts error:', e);
        }

        try {
            await loadProjectOptions();
        } catch (e) {
            console.warn('loadProjectOptions error:', e);
        }
    }

    init();
});
</script>

<!-- Modal Tiếp nhận nhân sự (sp_OnboardNewEmployee) -->
<div class="modal" id="onboardModal"
    style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(15,23,42,0.6); backdrop-filter: blur(5px); align-items: center; justify-content: center;">
    <div class="card"
        style="max-width: 650px; width: 92%; max-height: 90vh; overflow-y: auto; border-radius: 20px; box-shadow: 0 25px 60px rgba(0,0,0,0.3); border: 1px solid var(--line);">
        <div class="card-header dashboard-card-title-row"
            style="padding: 20px 24px; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 style="margin: 0; font-size: 1.2rem; display: flex; align-items: center; gap: 8px;">
                    Tiếp nhận Nhân sự mới (Onboard)
                </h2>
                <p style="margin: 4px 0 0; font-size: 0.8rem; color: #64748b;">
                    Thực thi Stored Procedure <code>sp_OnboardNewEmployee</code> đảm bảo toàn vẹn dữ liệu (Atomicity).
                </p>
            </div>
            <button class="btn btn-sm btn-light" type="button" id="btnCloseOnboardModal"
                style="font-size: 1.2rem; line-height: 1; padding: 4px 10px; border-radius: 8px;">✕</button>
        </div>

        <form id="onboardForm" style="padding: 24px;">
            <div
                style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; font-size: 0.85rem; color: #1e40af; line-height: 1.6;">

                • <strong>Test Rollback (Lỗi):</strong> Nhập Lương hợp đồng <code>&lt;= 0</code> (vd:
                <code>-5000000</code> hoặc <code>0</code>) → Bị chặn bởi ràng buộc toàn vẹn
                <code>chk_contract_salary &gt; 0</code>, Procedure tự động ROLLBACK toàn bộ.<br>
                • <strong>Test Commit (Thành công):</strong> Nhập Lương hợp đồng hợp lệ (vd: <code>15000000</code>) → Cả
                Nhân viên và Hợp đồng được lưu thành công trong 1 giao tác.
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label" style="font-weight: 600; font-size: 0.85rem;">Họ và tên nhân sự <span
                            style="color:red;">*</span></label>
                    <input class="form-control" type="text" id="onboardFullName" name="full_name" required
                        placeholder="VD: Lê Hoàng Nam" style="width:100%;">
                </div>

                <div class="form-group">
                    <label class="form-label" style="font-weight: 600; font-size: 0.85rem;">Email đăng nhập <span
                            style="color:red;">*</span></label>
                    <input class="form-control" type="email" id="onboardEmail" name="email" required
                        placeholder="VD: nam.le@agency.vn" style="width:100%;">
                </div>

                <div class="form-group">
                    <label class="form-label" style="font-weight: 600; font-size: 0.85rem;">Mã nhân viên <span
                            style="color:red;">*</span></label>
                    <input class="form-control" type="text" id="onboardEmpCode" name="employee_code" required
                        value="EMP-2026-09" style="width:100%;">
                </div>

                <div class="form-group">
                    <label class="form-label" style="font-weight: 600; font-size: 0.85rem;">Phòng ban <span
                            style="color:red;">*</span></label>
                    <select class="form-select" id="onboardDeptSelect" name="department_id" required
                        style="width:100%;">
                        <option value="1">Ban Quản Trị Hệ Thống</option>
                        <option value="2">Account Department</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" style="font-weight: 600; font-size: 0.85rem;">Chức vụ <span
                            style="color:red;">*</span></label>
                    <select class="form-select" id="onboardPosSelect" name="position_id" required style="width:100%;">
                        <option value="1">Director</option>
                        <option value="2">Manager</option>
                        <option value="3">Designer</option>
                        <option value="4">Developer</option>
                    </select>
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label" style="font-weight: 600; font-size: 0.85rem;">
                        Mức lương hợp đồng (VNĐ) <span style="color:red;">*</span>
                    </label>
                    <input class="form-control" type="number" id="onboardSalary" name="salary" required value="15000000"
                        step="500000" style="width:100%; font-weight:700; color:#0f766e; font-size: 1.05rem;">
                    <small style="color: #64748b; font-size: 0.78rem; display: block; margin-top: 4px;">
                        Gợi ý: Nhập số &lt;= 0 để xem MySQL Trigger & Stored Procedure kích hoạt ROLLBACK.
                    </small>
                </div>

                <div class="form-group">
                    <label class="form-label" style="font-weight: 600; font-size: 0.85rem;">Loại hợp đồng</label>
                    <select class="form-select" id="onboardContractType" name="contract_type" style="width:100%;">
                        <option value="probation">Thử việc (Probation)</option>
                        <option value="fixed_term">Xác định thời hạn</option>
                        <option value="indefinite">Không thời hạn</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" style="font-weight: 600; font-size: 0.85rem;">Ngày bắt đầu</label>
                    <input class="form-control" type="date" id="onboardHireDate" name="hire_date"
                        value="<?php echo date('Y-m-d'); ?>" style="width:100%;">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
                <button class="btn btn-light" type="button" id="btnCancelOnboard">Hủy</button>
                <button class="btn btn-primary" type="submit" id="btnSubmitOnboard"
                    style="padding: 10px 24px; font-weight: 700;">
                    Xác nhận
                </button>
            </div>
        </form>
    </div>
</div>

<?php
// Tích hợp components/modal.php để giao diện JS có chỗ render UI Kính lúp
require __DIR__ . '/../components/modal.php';
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>
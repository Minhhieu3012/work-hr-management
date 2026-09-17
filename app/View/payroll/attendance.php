<?php
// Khởi tạo thông tin trang
$pageTitle = 'Chấm công | Work & HR Management';
$pageCss = ['payroll.css', 'hrm.css'];
$pageJs = ['payroll.js'];
$activeMenu = 'attendance';
$topbarTitle = 'Web Check-in';
$brandName = 'Work & HR Management';

// Mảng history này sẽ được JavaScript ghi đè khi dữ liệu từ API tải xong
$history = $history ?? [];

ob_start();
?>

<section class="payroll-shell">
    <!-- Hero Section: Đồng hồ và Nút bấm -->
    <article class="attendance-hero">
        <div class="attendance-copy">
            <span>Work & HR Management • Web Check-in</span>
            <h1>Chấm công nhanh trong một chạm.</h1>
            <p>
                Ghi nhận giờ vào/ra mỗi ngày, theo dõi trạng thái chuyên cần và hỗ trợ dữ liệu
                cho bảng công, lương và KPI cuối tháng.
            </p>
        </div>

        <div class="attendance-clock-card">
            <div class="attendance-clock">
                <strong data-attendance-clock>--:--:--</strong>
                <small data-attendance-date>Đang tải ngày hiện tại...</small>
            </div>

            <div class="attendance-actions">
                <button class="btn btn-light" type="button" data-payroll-action="check-in">
                    Check-in
                </button>
                <button class="btn btn-emerald" type="button" data-payroll-action="check-out">
                    Check-out
                </button>
            </div>

            <div class="attendance-status">
                <div class="attendance-status-item">
                    <span>Trạng thái hôm nay</span>
                    <strong data-checkin-status>Đang kiểm tra...</strong>
                </div>

                <div class="attendance-status-item">
                    <span>Ca làm việc</span>
                    <strong>08:00 - 17:30</strong>
                </div>
            </div>
        </div>
    </article>

    <!-- Thống kê nhanh: Sẽ được payroll.js cập nhật qua ID -->
    <div class="stat-grid">
        <article class="stat-card">
            <div class="stat-card-icon">◷</div>
            <div class="stat-card-body">
                <span>Ngày công tháng này</span>
                <strong id="js-stat-total">0</strong>
                <small>Trên tổng 22 ngày</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon">✓</div>
            <div class="stat-card-body">
                <span>Đúng giờ</span>
                <strong id="js-stat-ontime">0</strong>
                <small id="js-stat-rate">Tỷ lệ 0%</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon">△</div>
            <div class="stat-card-body">
                <span>Đi muộn</span>
                <strong id="js-stat-late">0</strong>
                <small>Cần cải thiện</small>
            </div>
        </article>

        <article class="stat-card stat-card-danger">
            <div class="stat-card-icon">!</div>
            <div class="stat-card-body">
                <span>Thiếu checkout</span>
                <strong id="js-stat-missing">0</strong>
                <small>Cần bổ sung</small>
            </div>
        </article>
    </div>

    <!-- Lưới hiển thị Lịch sử và Timeline -->
    <section class="payroll-grid">
        <article class="card employee-table-card">
            <div class="card-header dashboard-card-title-row">
                <div>
                    <h2>Lịch sử chấm công</h2>
                    <p class="section-subtitle">Theo dõi các lần check-in/check-out gần nhất.</p>
                </div>

                <div style="display: flex; gap: 8px;">
                    <button class="btn btn-emerald" type="button" data-payroll-action="calculate-payroll">
                        ⚡ Tính lương tháng (Stored Procedure)
                    </button>
                    <button class="btn btn-soft" type="button" data-payroll-action="mock-save">
                        ⇩ Xuất bảng công
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Ngày</th>
                            <th>Giờ vào</th>
                            <th>Giờ ra</th>
                            <th>Trạng thái</th>
                            <th>Ghi chú</th>
                        </tr>
                    </thead>

                    <tbody id="js-attendance-history">
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px; color: #999;">
                                Đang tải lịch sử chấm công...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </article>

        <!-- Sidebar: Timeline tĩnh (có thể mở rộng thành động sau) -->
        <aside class="card">
            <div class="card-body">
                <h2 class="section-title">Timeline hôm nay</h2>

                <div class="timeline-list" style="margin-top: 24px;">
                    <div class="timeline-row">
                        <div class="timeline-dot">1</div>
                        <div class="timeline-content">
                            <strong>08:00</strong>
                            <p>Bắt đầu ca làm việc tiêu chuẩn.</p>
                        </div>
                    </div>

                    <div class="timeline-row">
                        <div class="timeline-dot">2</div>
                        <div class="timeline-content">
                            <strong>12:00</strong>
                            <p>Nghỉ trưa và cập nhật trạng thái công việc.</p>
                        </div>
                    </div>

                    <div class="timeline-row">
                        <div class="timeline-dot">3</div>
                        <div class="timeline-content">
                            <strong>17:30</strong>
                            <p>Kết thúc ca làm việc và check-out.</p>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </section>

    <!-- Modal Tính lương tháng bằng Stored Procedure -->
    <div id="payrollModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="width: 100%; max-width: 780px; margin: 20px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.6); border: 1px solid rgba(255,255,255,0.15);">
            <div class="card-header dashboard-card-title-row" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.1);">
                <div>
                    <h2 style="margin: 0; display: flex; align-items: center; gap: 8px;">
                        <span>⚡ Tính lương tự động bằng Stored Procedure</span>
                    </h2>
                    <p class="section-subtitle" style="margin: 4px 0 0 0;">
                        Gọi thủ tục <code>sp_CalculateMonthlyPayroll(p_month, p_year)</code> - Áp dụng Cursor duyệt nhân viên active.
                    </p>
                </div>
                <button type="button" class="btn btn-soft" data-payroll-modal-close style="font-size: 16px; padding: 4px 12px; cursor: pointer;">✕</button>
            </div>
            <div class="card-body" style="padding: 24px;">
                <div style="display: flex; gap: 16px; align-items: flex-end; margin-bottom: 24px; flex-wrap: wrap; background: rgba(255,255,255,0.04); padding: 16px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.08);">
                    <div style="flex: 1; min-width: 120px;">
                        <label style="display: block; font-size: 13px; margin-bottom: 6px; font-weight: 500; color: #cbd5e1;">Tháng</label>
                        <select id="payrollMonth" class="form-control" style="width: 100%; padding: 8px 12px; border-radius: 6px; background: #1e293b; color: #fff; border: 1px solid rgba(255,255,255,0.2);">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>" <?= $m == (int)date('m') ? 'selected' : '' ?>>Tháng <?= $m ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div style="flex: 1; min-width: 120px;">
                        <label style="display: block; font-size: 13px; margin-bottom: 6px; font-weight: 500; color: #cbd5e1;">Năm</label>
                        <select id="payrollYear" class="form-control" style="width: 100%; padding: 8px 12px; border-radius: 6px; background: #1e293b; color: #fff; border: 1px solid rgba(255,255,255,0.2);">
                            <?php $curY = (int)date('Y'); for ($y = $curY - 1; $y <= $curY + 1; $y++): ?>
                                <option value="<?= $y ?>" <?= $y == $curY ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <button type="button" id="btnRunPayrollSP" class="btn btn-emerald" style="padding: 9px 20px; font-weight: 600;">
                            ▶ Thực thi Stored Procedure
                        </button>
                    </div>
                </div>

                <div id="payrollLoading" style="display: none; text-align: center; padding: 30px; color: #94a3b8;">
                    ⏳ Đang thực thi Stored Procedure và Cursor duyệt dữ liệu bảng chấm công...
                </div>

                <div id="payrollResultWrapper" style="display: none;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <h3 style="font-size: 15px; margin: 0; color: #10b981;">
                            ✓ Bảng lương tính toán tự động:
                        </h3>
                        <small style="color: #94a3b8;">Công chuẩn: 22 ngày/tháng</small>
                    </div>
                    <div class="table-responsive" style="border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;">
                        <table class="data-table" style="width: 100%; margin: 0;">
                            <thead>
                                <tr>
                                    <th>ID Nhân viên</th>
                                    <th>Lương cơ bản</th>
                                    <th>Ngày công thực tế</th>
                                    <th style="text-align: right;">Lương thực nhận</th>
                                </tr>
                            </thead>
                            <tbody id="payrollTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>
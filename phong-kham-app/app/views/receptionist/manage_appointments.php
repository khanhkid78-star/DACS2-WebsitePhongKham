<?php
require_once __DIR__ . '/../../helpers/Auth.php';
Auth::checkRole(array('receptionist', 'admin'));
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/navbar.php';
?>

<div class="page-head">
    <div>
        <h2>Lịch hẹn</h2>
        <p class="lead">Gõ để lọc. Bấm Sửa trên đúng dòng cần đổi giờ, bác sĩ, triệu chứng hoặc trạng thái.</p>
    </div>
</div>

<form class="filters" id="appt-filters">
    <label>Tìm kiếm
        <input type="search" name="q" placeholder="Tên, số điện thoại, mã">
    </label>
    <label>Từ ngày
        <input type="date" name="from">
    </label>
    <label>Đến ngày
        <input type="date" name="to">
    </label>
    <label>Trạng thái
        <select name="status">
            <option value="">Tất cả</option>
            <option value="Pending">Chờ duyệt</option>
            <option value="Scheduled">Đã lên lịch</option>
            <option value="Completed">Đã khám xong</option>
            <option value="Cancelled">Đã hủy</option>
        </select>
    </label>
    <label>Bác sĩ
        <select name="doctor_id">
            <option value="0">Tất cả</option>
            <?php foreach ($doctors as $doc): ?>
                <option value="<?php echo (int) $doc['doctor_id']; ?>"><?php echo htmlspecialchars((string) $doc['full_name']); ?></option>
            <?php endforeach; ?>
        </select>
    </label>
</form>

<div class="table-wrap list-scroll">
<table>
    <thead>
        <tr>
            <th>Mã</th><th>Bệnh nhân</th><th>SĐT</th><th>Bác sĩ</th><th>Ngày giờ</th><th>Triệu chứng</th><th>Trạng thái</th><th></th>
        </tr>
    </thead>
    <tbody id="appt-body">
        <?php require __DIR__ . '/_appointment_rows.php'; ?>
    </tbody>
</table>
</div>

<div class="modal" id="appt-modal">
    <div class="modal-card">
        <button class="modal-x" type="button" data-close-box>Đóng</button>
        <h2>Sửa lịch #<span id="appt-label"></span></h2>
        <form action="/index.php?action=update_appointment" method="POST">
            <input type="hidden" name="id" id="appt-id">
            <label>Bác sĩ
                <select name="doctor_id" id="appt-doctor" required>
                    <?php foreach ($doctors as $doc): ?>
                        <option value="<?php echo (int) $doc['doctor_id']; ?>"><?php echo htmlspecialchars((string) $doc['full_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Ngày giờ
                <input type="datetime-local" name="appointment_date" id="appt-date" required>
            </label>
            <label>Triệu chứng
                <textarea name="note" id="appt-note" rows="3" required></textarea>
            </label>
            <label>Trạng thái
                <select name="status" id="appt-status">
                    <option value="Scheduled">Đã lên lịch</option>
                    <option value="Cancelled">Đã hủy</option>
                </select>
                <p id="appt-status-lock" class="lead" style="display:none">Lịch đã có bệnh án, trạng thái giữ là Đã khám xong.</p>
            </label>
            <p><button class="btn-navy" type="submit">Lưu dòng này</button></p>
        </form>
    </div>
</div>

<div class="modal" id="record-detail-modal">
    <div class="modal-card">
        <button class="modal-x" type="button" data-close-box>Đóng</button>
        <h2 id="detail-title">Chi tiết hồ sơ</h2>
        <div id="detail-body"><p class="lead">Đang tải…</p></div>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
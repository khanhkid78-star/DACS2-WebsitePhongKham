<?php
require_once __DIR__ . '/../../helpers/Auth.php';
Auth::checkRole(array('doctor', 'admin'));
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/navbar.php';
?>

<div class="page-head">
    <div>
        <h2>Hồ sơ bệnh án</h2>
        <p class="lead">Danh sách nằm trong khung cố định. Sửa dòng nào thì popup chỉ hiện dòng đó.</p>
    </div>
    <button class="btn-navy" type="button" id="open-new-record" <?php echo empty($openAppointments) ? 'disabled' : ''; ?>>Ghi bệnh án</button>
</div>
<?php if (empty($openAppointments)): ?>
    <p class="lead">Chưa có lịch đã duyệt nào để ghi bệnh án mới.</p>
<?php endif; ?>

<form class="filters" id="record-filters">
    <label>Tìm kiếm
        <input type="search" name="q" placeholder="Bệnh nhân, chẩn đoán, mã">
    </label>
    <label>Từ ngày
        <input type="date" name="from">
    </label>
    <label>Đến ngày
        <input type="date" name="to">
    </label>
    <?php if (Auth::getRole() === 'admin'): ?>
        <label>Bác sĩ
            <select name="doctor_id">
                <option value="0">Tất cả</option>
                <?php foreach ($doctors as $doc): ?>
                    <option value="<?php echo (int) $doc['doctor_id']; ?>"><?php echo htmlspecialchars((string) $doc['full_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    <?php endif; ?>
</form>

<div class="table-wrap list-scroll">
<table>
    <thead>
        <tr>
            <th>Mã</th><th>Bệnh nhân</th><th>Bác sĩ</th><th>Ngày khám</th><th>Chẩn đoán</th><th></th>
        </tr>
    </thead>
    <tbody id="record-body">
        <?php require __DIR__ . '/_record_rows.php'; ?>
    </tbody>
</table>
</div>

<div class="modal" id="record-modal">
    <div class="modal-card">
        <button class="modal-x" type="button" data-close-box>Đóng</button>
        <h2 id="record-title">Ghi bệnh án mới</h2>
        <form id="record-form" action="/index.php?action=save_record" method="POST">
            <input type="hidden" name="record_id" id="record-id" disabled>
            <div id="record-locked" style="display:none">
                <label>Mã hồ sơ <input id="lock-record" type="text" disabled></label>
                <label>Mã lịch <input id="lock-appt" type="text" disabled></label>
                <label>Bệnh nhân <input id="lock-patient" type="text" disabled></label>
                <label>Bác sĩ <input id="lock-doctor" type="text" disabled></label>
                <label>Ngày khám <input id="lock-when" type="text" disabled></label>
            </div>
            <div id="record-create-fields">
                <label>Lịch đã duyệt
                    <select name="appointment_id" data-appointment required>
                        <option value="">Chọn bệnh nhân</option>
                        <?php foreach ($openAppointments as $item): ?>
                            <option value="<?php echo (int) $item['appointment_id']; ?>" data-note="<?php echo htmlspecialchars((string) $item['note'], ENT_QUOTES); ?>">
                                #<?php echo (int) $item['appointment_id']; ?> — <?php echo htmlspecialchars((string) $item['patient_name']); ?> — <?php echo date('H:i d/m/Y', strtotime($item['appointment_date'])); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <label>Triệu chứng <textarea name="symptoms" id="record-symptoms" rows="3" required></textarea></label>
            <label>Chẩn đoán <textarea name="diagnosis" id="record-diagnosis" rows="3" required></textarea></label>
            <label>Kết luận <textarea name="conclusion" id="record-conclusion" rows="3" required></textarea></label>
            <div id="record-rx">
                <p class="lead">Đơn thuốc, bỏ trống nếu chưa kê. Tick dòng rồi bấm xóa.</p>
                <div id="rx-lines">
                    <div class="rx-line" data-line>
                        <label class="rx-check"><input type="checkbox" data-line-check> Chọn</label>
                        <label>Thuốc
                            <select name="medicine_id[]">
                                <option value="">Chọn thuốc</option>
                                <?php foreach ($medicines as $med): ?>
                                    <option value="<?php echo (int) $med['medicine_id']; ?>"><?php echo htmlspecialchars((string) $med['name']); ?> (<?php echo htmlspecialchars((string) $med['unit']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Số lượng <input type="number" name="quantity[]" min="1" step="1"></label>
                        <label>Liều <input type="text" name="dosage[]" placeholder="Sáng 1, tối 1"></label>
                        <label>Cách dùng <input type="text" name="usage[]" placeholder="Uống sau ăn"></label>
                    </div>
                </div>
                <p class="actions">
                    <button class="btn-ghost" type="button" data-add-line>Thêm dòng thuốc</button>
                    <button class="btn-logout" type="button" data-remove-line>Xóa dòng đã chọn</button>
                </p>
            </div>
            <p><button class="btn-navy" type="submit" id="record-submit">Lưu bệnh án</button></p>
        </form>
    </div>
</div>


<div class="modal" id="record-detail-modal">
    <div class="modal-card">
        <button class="modal-x" type="button" data-close-box>Đóng</button>
        <h2 id="detail-title">Chi tiết hồ sơ</h2>
        <div id="detail-body" class="detail-body">
            <p class="lead">Đang tải…</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
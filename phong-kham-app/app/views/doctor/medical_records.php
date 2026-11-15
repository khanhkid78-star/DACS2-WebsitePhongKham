<?php
require_once __DIR__ . '/../../helpers/Auth.php';
Auth::checkRole(array('doctor', 'admin'));
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/navbar.php';
?>

<h2>Hồ sơ bệnh án</h2>
<p class="lead">Chỉ lịch lễ tân đã duyệt mới ghi được. Lưu xong, lịch chuyển sang đã khám.</p>

<?php if ($editRecord): ?>
    <form class="card form-wide" action="/index.php?action=update_record" method="POST">
        <h2>Sửa bệnh án #<?php echo (int) $editRecord['record_id']; ?></h2>
        <p class="lead">Đơn thuốc đã kê sẽ giữ nguyên.</p>
        <input type="hidden" name="record_id" value="<?php echo (int) $editRecord['record_id']; ?>">
        <label>
            Triệu chứng
            <textarea name="symptoms" rows="3" required><?php echo htmlspecialchars((string) $editRecord['symptoms']); ?></textarea>
        </label>
        <label>
            Chẩn đoán
            <textarea name="diagnosis" rows="3" required><?php echo htmlspecialchars((string) $editRecord['diagnosis']); ?></textarea>
        </label>
        <label>
            Kết luận
            <textarea name="conclusion" rows="3" required><?php echo htmlspecialchars((string) $editRecord['conclusion']); ?></textarea>
        </label>
        <p class="actions">
            <button class="btn-navy" type="submit">Cập nhật</button>
            <a class="btn-ghost" href="/index.php?action=doctor_records">Hủy</a>
        </p>
    </form>
<?php elseif (empty($openAppointments)): ?>
    <p class="lead">Chưa có lịch đã duyệt nào để ghi bệnh án.</p>
<?php else: ?>
    <form class="card form-wide" action="/index.php?action=save_record" method="POST">
        <h2>Ghi bệnh án mới</h2>
        <label>
            Lịch đã duyệt
            <select name="appointment_id" data-appointment required>
                <option value="">Chọn bệnh nhân</option>
                <?php foreach ($openAppointments as $item): ?>
                    <option
                        value="<?php echo (int) $item['appointment_id']; ?>"
                        data-note="<?php echo htmlspecialchars((string) $item['note'], ENT_QUOTES); ?>"
                    >
                        #<?php echo (int) $item['appointment_id']; ?>
                        — <?php echo htmlspecialchars((string) $item['patient_name']); ?>
                        — BS. <?php echo htmlspecialchars((string) $item['doctor_name']); ?>
                        — <?php echo date('H:i d/m/Y', strtotime($item['appointment_date'])); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Triệu chứng
            <textarea name="symptoms" rows="3" required></textarea>
        </label>
        <label>
            Chẩn đoán
            <textarea name="diagnosis" rows="3" required></textarea>
        </label>
        <label>
            Kết luận
            <textarea name="conclusion" rows="3" required></textarea>
        </label>

        <p class="lead">Đơn thuốc, bỏ trống nếu chưa kê.</p>
        <div id="rx-lines">
            <div class="rx-line" data-line>
                <label>
                    Thuốc
                    <select name="medicine_id[]">
                        <option value="">Chọn thuốc</option>
                        <?php foreach ($medicines as $med): ?>
                            <option value="<?php echo (int) $med['medicine_id']; ?>">
                                <?php echo htmlspecialchars((string) $med['name']); ?>
                                (<?php echo htmlspecialchars((string) $med['unit']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    Số lượng
                    <input type="number" name="quantity[]" min="1" step="1">
                </label>
                <label>
                    Liều
                    <input type="text" name="dosage[]" placeholder="Sáng 1, tối 1">
                </label>
                <label>
                    Cách dùng
                    <input type="text" name="usage[]" placeholder="Uống sau ăn">
                </label>
            </div>
        </div>
        <p>
            <button class="btn-ghost" type="button" data-add-line>Thêm dòng thuốc</button>
        </p>
        <p>
            <button class="btn-navy" type="submit">Lưu bệnh án</button>
        </p>
    </form>
<?php endif; ?>

<?php if (empty($records)): ?>
    <p class="lead">Chưa có bệnh án.</p>
<?php else: ?>
    <div class="table-wrap">
    <table>
        <tr>
            <th>Mã</th>
            <th>Bệnh nhân</th>
            <th>Bác sĩ</th>
            <th>Ngày khám</th>
            <th>Triệu chứng</th>
            <th>Chẩn đoán</th>
            <th>Kết luận</th>
            <th>Đơn thuốc</th>
            <th>Sửa</th>
        </tr>
        <?php foreach ($records as $rec): ?>
        <?php
            $mine = Auth::getRole() === 'admin'
                || (int) ($rec['doctor_id'] ?? 0) === (int) ($_SESSION['doctor_id'] ?? 0);
        ?>
        <tr>
            <td>#<?php echo (int) $rec['record_id']; ?></td>
            <td><?php echo htmlspecialchars((string) $rec['patient_name']); ?></td>
            <td><?php echo htmlspecialchars((string) $rec['doctor_name']); ?></td>
            <td><?php echo date('H:i d/m/Y', strtotime($rec['visit_date'])); ?></td>
            <td><?php echo htmlspecialchars((string) $rec['symptoms']); ?></td>
            <td><?php echo htmlspecialchars((string) $rec['diagnosis']); ?></td>
            <td><?php echo htmlspecialchars((string) $rec['conclusion']); ?></td>
            <td><?php echo $rec['prescription_text'] ? htmlspecialchars($rec['prescription_text']) : 'Chưa kê'; ?></td>
            <td>
                <?php if ($mine): ?>
                    <a class="btn-ghost" href="/index.php?action=doctor_records&edit=<?php echo (int) $rec['record_id']; ?>">Sửa</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
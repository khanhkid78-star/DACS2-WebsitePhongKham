<?php
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/navbar.php';
require_once __DIR__ . '/../../helpers/Auth.php';

Auth::startSession();
$isPatient = Auth::getRole() === 'patient';
$selectedDoctor = isset($_GET['doctor_id']) ? (int) $_GET['doctor_id'] : 0;
?>

<h2>Đăng ký đặt lịch khám</h2>
<p class="lead">
    Lịch mới ở trạng thái chờ duyệt cho đến khi lễ tân xác nhận.
</p>

<form class="card form-narrow" action="/index.php?action=submit_booking" method="POST">
    <?php if (!$isPatient): ?>
        <div class="form-row">
            <label>
                Họ tên bệnh nhân
                <input type="text" name="full_name" required>
            </label>
            <label>
                Số điện thoại
                <input type="tel" name="phone" required>
            </label>
        </div>
    <?php endif; ?>

    <label>
        Bác sĩ
        <select name="doctor_id" required>
            <option value="">Chọn bác sĩ và chuyên khoa</option>
            <?php foreach ($doctors as $doc): ?>
                <option
                    value="<?php echo (int) $doc['doctor_id']; ?>"
                    <?php echo $selectedDoctor === (int) $doc['doctor_id'] ? 'selected' : ''; ?>
                >
                    BS. <?php echo htmlspecialchars($doc['full_name']); ?>
                    — <?php echo htmlspecialchars($doc['specialty']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>
        Ngày và giờ muốn khám
        <input type="datetime-local" name="appointment_date" required>
    </label>
    <p class="lead">Thứ 2 – thứ 7: 7:00–17:00. Chủ nhật: 7:00–11:00.</p>

    <label>
        Lý do khám / triệu chứng
        <textarea
            name="note"
            rows="4"
            required
            placeholder="Ví dụ: sốt từ hôm qua, đau họng khi nuốt."
        ></textarea>
    </label>

    <p>
        <button class="btn-navy" type="submit">Gửi yêu cầu đặt lịch</button>
    </p>
</form>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
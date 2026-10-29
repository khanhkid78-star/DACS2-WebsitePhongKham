<?php require_once __DIR__ . '/../partials/header.php'; ?>
<?php require_once __DIR__ . '/../partials/navbar.php'; ?>

<h2>Đăng Ký Đặt Lịch Khám Trực Tuyến</h2>
<p style="color: #666;">Điền đầy đủ thông tin bên dưới để được ưu tiên sắp xếp lịch khám.</p>

<form action="/index.php?action=submit_booking" method="POST" style="background: white; padding: 20px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); max-width: 600px;">
    <div style="margin-bottom: 15px;">
        <label><strong>Chọn Bác Sĩ Khám:</strong></label><br>
        <select name="doctor_id" required style="width: 100%; padding: 10px; margin-top: 5px;">
            <option value="">-- Chọn bác sĩ/chuyên khoa --</option>
            <?php foreach ($doctors as $doc): ?>
                <option value="<?php echo $doc['doctor_id']; ?>">
                    BS. <?php echo $doc['full_name']; ?> - Chuyên khoa: <?php echo $doc['specialty']; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div style="margin-bottom: 15px;">
        <label><strong>Ngày và Giờ Muốn Khám:</strong></label><br>
        <input type="datetime-local" name="appointment_date" required style="width: 100%; padding: 10px; margin-top: 5px;">
    </div>

    <div style="margin-bottom: 15px;">
        <label><strong>Lý do khám / Triệu chứng bệnh:</strong></label><br>
        <textarea name="note" rows="4" placeholder="Mô tả ngắn gọn về tình trạng sức khỏe của bạn..." required style="width: 100%; padding: 10px; margin-top: 5px;"></textarea>
    </div>

    <button type="submit" style="background-color: #004b93; color: white; border: none; padding: 12px 20px; font-weight: bold; cursor: pointer; border-radius: 4px;">GỬI YÊU CẦU ĐẶT LỊCH</button>
</form>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
<?php 
require_once __DIR__ . '/../../helpers/Auth.php';
Auth::checkRole(array('receptionist', 'admin')); // Chỉ Lễ tân và Admin được dùng[cite: 25]

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/navbar.php';
?>

<h2>Quản Lý Duyệt Lịch Hẹn (View Lễ Tân)</h2>

<table border="1" cellpadding="10" cellspacing="0" style="width: 100%; border-collapse: collapse; background: white;">
    <tr style="background-color: #004b93; color: white;">
        <th>Mã Lịch</th>
        <th>Bệnh Nhân</th>
        <th>SĐT</th>
        <th>Bác Sĩ</th>
        <th>Ngày Giờ Khám</th>
        <th>Triệu Chứng</th>
        <th>Trạng Thái</th>
        <th>Thao Tác</th>
    </tr>
    <?php foreach ($appointments as $app): ?>
    <tr>
        <td>#<?php echo $app['appointment_id']; ?></td>
        <td><?php echo htmlspecialchars($app['patient_name']); ?></td>
        <td><?php echo htmlspecialchars($app['patient_phone']); ?></td>
        <td><?php echo htmlspecialchars($app['doctor_name']); ?></td>
        <td><?php echo $app['appointment_date']; ?></td>
        <td><?php echo htmlspecialchars($app['note']); ?></td>
        <td>
            <strong><?php echo $app['status']; ?></strong>
        </td>
        <td>
            <?php if ($app['status'] === 'Pending'): ?>
                <a href="/index.php?action=update_appointment_status&id=<?php echo $app['appointment_id']; ?>&status=Scheduled" style="color: green; font-weight: bold; margin-right: 10px;">[Duyệt Lịch]</a>
                <a href="/index.php?action=update_appointment_status&id=<?php echo $app['appointment_id']; ?>&status=Cancelled" style="color: red;">[Hủy]</a>
            <?php else: ?>
                <span>Đã xử lý</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
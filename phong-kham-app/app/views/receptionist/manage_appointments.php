<?php
require_once __DIR__ . '/../../helpers/Auth.php';
Auth::checkRole(array('receptionist', 'admin'));

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/navbar.php';
?>

<h2>Duyệt lịch hẹn</h2>
<p class="lead">Chỉ lịch đang chờ mới có nút duyệt hoặc hủy.</p>

<div class="table-wrap">
<table>
    <tr>
        <th>Mã</th>
        <th>Bệnh nhân</th>
        <th>SĐT</th>
        <th>Bác sĩ</th>
        <th>Ngày giờ</th>
        <th>Triệu chứng</th>
        <th>Trạng thái</th>
        <th>Thao tác</th>
    </tr>
    <?php foreach ($appointments as $app): ?>
    <tr>
        <td>#<?php echo (int) $app['appointment_id']; ?></td>
        <td><?php echo htmlspecialchars($app['patient_name']); ?></td>
        <td><?php echo htmlspecialchars($app['patient_phone']); ?></td>
        <td><?php echo htmlspecialchars($app['doctor_name']); ?></td>
        <td><?php echo htmlspecialchars($app['appointment_date']); ?></td>
        <td><?php echo htmlspecialchars($app['note']); ?></td>
        <td><?php echo htmlspecialchars($app['status']); ?></td>
        <td>
            <?php if ($app['status'] === 'Pending'): ?>
                <form action="/index.php?action=update_appointment_status" method="POST">
                    <input type="hidden" name="id" value="<?php echo (int) $app['appointment_id']; ?>">
                    <input type="hidden" name="status" value="Scheduled">
                    <button class="btn-navy" type="submit">Duyệt lịch</button>
                </form>
                <form action="/index.php?action=update_appointment_status" method="POST">
                    <input type="hidden" name="id" value="<?php echo (int) $app['appointment_id']; ?>">
                    <input type="hidden" name="status" value="Cancelled">
                    <button class="btn-ghost" type="submit">Hủy</button>
                </form>
            <?php else: ?>
                <span>Đã xử lý</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
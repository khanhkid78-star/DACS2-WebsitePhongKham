<?php
require_once __DIR__ . '/../../helpers/Auth.php';
Auth::checkRole(array('patient', 'admin'));

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/navbar.php';
?>

<div class="actions">
    <h2>Lịch hẹn của tôi</h2>
    <a class="btn-booking" href="/index.php?action=booking">Đặt lịch mới</a>
</div>

<?php if (empty($my_appointments)): ?>
    <div class="card">
        <p class="lead">Bạn chưa đăng ký lịch khám nào.</p>
        <a href="/index.php?action=booking">Đặt lịch khám ngay</a>
    </div>
<?php else: ?>
    <div class="table-wrap">
    <table>
        <tr>
            <th>Mã</th>
            <th>Bác sĩ</th>
            <th>Chuyên khoa</th>
            <th>Ngày giờ</th>
            <th>Lý do</th>
            <th>Trạng thái</th>
        </tr>
        <?php foreach ($my_appointments as $app): ?>
        <tr>
            <td>#<?php echo (int) $app['appointment_id']; ?></td>
            <td>BS. <?php echo htmlspecialchars($app['doctor_name']); ?></td>
            <td><?php echo htmlspecialchars($app['specialty']); ?></td>
            <td><?php echo date('H:i - d/m/Y', strtotime($app['appointment_date'])); ?></td>
            <td><?php echo htmlspecialchars($app['note']); ?></td>
            <td>
                <?php if ($app['status'] === 'Pending'): ?>
                    <span class="badge badge-pending">Chờ lễ tân duyệt</span>
                <?php elseif ($app['status'] === 'Scheduled'): ?>
                    <span class="badge badge-ok">Đã lên lịch</span>
                <?php elseif ($app['status'] === 'Completed'): ?>
                    <span class="badge badge-done">Đã khám xong</span>
                <?php elseif ($app['status'] === 'Cancelled'): ?>
                    <span class="badge badge-cancel">Đã hủy</span>
                <?php else: ?>
                    <?php echo htmlspecialchars($app['status']); ?>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
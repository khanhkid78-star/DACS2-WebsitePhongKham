<?php
require_once __DIR__ . '/../../helpers/Auth.php';
Auth::checkRole(array('receptionist', 'admin'));
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/navbar.php';

$statusLabel = array(
    'Pending' => array('Chờ duyệt', 'badge-pending'),
    'Scheduled' => array('Đã lên lịch', 'badge-ok'),
    'Completed' => array('Đã khám xong', 'badge-done'),
    'Cancelled' => array('Đã hủy', 'badge-cancel'),
);
?>

<h2>Duyệt lịch hẹn</h2>
<p class="lead">Lịch chờ thì duyệt hoặc hủy. Lịch đã lên lịch thì đánh dấu đã khám, hoặc hủy.</p>

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
    <?php if (empty($appointments)): ?>
        <tr><td colspan="8">Chưa có lịch hẹn.</td></tr>
    <?php endif; ?>
    <?php foreach ($appointments as $app): ?>
    <?php $st = isset($statusLabel[$app['status']]) ? $statusLabel[$app['status']] : array($app['status'], 'badge-cancel'); ?>
    <tr>
        <td>#<?php echo (int) $app['appointment_id']; ?></td>
        <td><?php echo htmlspecialchars((string) $app['patient_name']); ?></td>
        <td><?php echo htmlspecialchars((string) $app['patient_phone']); ?></td>
        <td><?php echo htmlspecialchars((string) $app['doctor_name']); ?></td>
        <td><?php echo date('H:i d/m/Y', strtotime($app['appointment_date'])); ?></td>
        <td><?php echo htmlspecialchars((string) $app['note']); ?></td>
        <td><span class="badge <?php echo $st[1]; ?>"><?php echo htmlspecialchars((string) $st[0]); ?></span></td>
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
            <?php elseif ($app['status'] === 'Scheduled'): ?>
                <form action="/index.php?action=update_appointment_status" method="POST">
                    <input type="hidden" name="id" value="<?php echo (int) $app['appointment_id']; ?>">
                    <input type="hidden" name="status" value="Completed">
                    <button class="btn-navy" type="submit">Đã khám</button>
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
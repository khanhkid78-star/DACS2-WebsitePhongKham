<?php
$labels = array(
    'Pending' => array('Chờ duyệt', 'badge-pending'),
    'Scheduled' => array('Đã lên lịch', 'badge-ok'),
    'Completed' => array('Đã khám xong', 'badge-done'),
    'Cancelled' => array('Đã hủy', 'badge-cancel'),
    'NoShow' => array('Trễ hẹn', 'badge-cancel'),
);
if (empty($appointments)): ?>
    <tr><td colspan="8">Không có lịch khớp bộ lọc.</td></tr>
<?php else: foreach ($appointments as $app):
    $st = isset($labels[$app['status']]) ? $labels[$app['status']] : array($app['status'], 'badge-cancel');
    $payload = htmlspecialchars(json_encode(array(
        'id' => (int) $app['appointment_id'],
        'doctor_id' => (int) $app['doctor_id'],
        'date' => date('Y-m-d\TH:i', strtotime($app['appointment_date'])),
        'note' => (string) $app['note'],
        'status' => (string) $app['status'],
    ), JSON_UNESCAPED_UNICODE), ENT_QUOTES);
?>
    <tr class="appt-open" data-appointment-id="<?php echo (int) $app['appointment_id']; ?>">
        <td>#<?php echo (int) $app['appointment_id']; ?></td>
        <td><?php echo htmlspecialchars((string) $app['patient_name']); ?></td>
        <td><?php echo htmlspecialchars((string) $app['patient_phone']); ?></td>
        <td><?php echo htmlspecialchars((string) $app['doctor_name']); ?></td>
        <td><?php echo date('H:i d/m/Y', strtotime($app['appointment_date'])); ?></td>
        <td class="clip" title="<?php echo htmlspecialchars((string) $app['note']); ?>"><?php echo htmlspecialchars((string) $app['note']); ?></td>
        <td><span class="badge <?php echo $st[1]; ?>"><?php echo htmlspecialchars((string) $st[0]); ?></span></td>
        <td>
            <?php if ($app['status'] !== 'Cancelled' && $app['status'] !== 'NoShow'): ?>
                <button class="btn-ghost" type="button" data-appt="<?php echo $payload; ?>">Sửa</button>
            <?php endif; ?>
        </td>
    </tr>
<?php endforeach; endif; ?>
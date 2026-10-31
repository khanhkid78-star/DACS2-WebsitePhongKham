<?php
require_once __DIR__ . '/../../helpers/Auth.php';
Auth::checkRole(array('receptionist', 'admin'));

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/navbar.php';
?>

<h2>Hóa đơn khám bệnh</h2>

<div class="table-wrap">
<table>
    <tr>
        <th>Mã</th>
        <th>Bệnh nhân</th>
        <th>Tổng tiền</th>
        <th>Phương thức</th>
        <th>Trạng thái</th>
        <th>Ngày tạo</th>
    </tr>
    <?php foreach ($invoices as $inv): ?>
    <tr>
        <td>#<?php echo (int) $inv['invoice_id']; ?></td>
        <td><?php echo htmlspecialchars($inv['patient_name']); ?></td>
        <td><?php echo number_format($inv['total_amount']); ?> đ</td>
        <td><?php echo htmlspecialchars($inv['payment_method']); ?></td>
        <td>
            <?php if ($inv['status'] === 'Paid'): ?>
                <span class="badge badge-ok">Đã thanh toán</span>
            <?php else: ?>
                <span class="badge badge-pending">Chưa thanh toán</span>
            <?php endif; ?>
        </td>
        <td><?php echo date('d/m/Y H:i', strtotime($inv['created_at'])); ?></td>
    </tr>
    <?php endforeach; ?>
</table>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
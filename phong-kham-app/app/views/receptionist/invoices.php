<?php 
require_once __DIR__ . '/../../helpers/Auth.php';
Auth::checkRole(array('receptionist', 'admin'));

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/navbar.php';
?>

<h2>Quản Lý Hóa Đơn Khám Bệnh (View Lễ Tân)</h2>

<table border="1" cellpadding="10" cellspacing="0" style="width: 100%; border-collapse: collapse; background: white;">
    <tr style="background-color: #004b93; color: white;">
        <th>Mã Hóa Đơn</th>
        <th>Bệnh Nhân</th>
        <th>Tổng Tiền</th>
        <th>Phương Thức</th>
        <th>Trạng Thái</th>
        <th>Ngày Tạo</th>
    </tr>
    <?php foreach ($invoices as $inv): ?>
    <tr>
        <td>#<?php echo $inv['invoice_id']; ?></td>
        <td><?php echo htmlspecialchars($inv['patient_name']); ?></td>
        <td><strong><?php echo number_format($inv['total_amount']); ?> VNĐ</strong></td>
        <td><?php echo htmlspecialchars($inv['payment_method']); ?></td>
        <td>
            <span style="padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 12px; background: <?php echo $inv['status'] === 'Paid' ? '#d4edda' : '#fff3cd'; ?>; color: <?php echo $inv['status'] === 'Paid' ? '#155724' : '#856404'; ?>;">
                <?php echo $inv['status'] === 'Paid' ? 'Đã Thanh Toán' : 'Chưa Thanh Toán'; ?>
            </span>
        </td>
        <td><?php echo date('d/m/Y H:i', strtotime($inv['created_at'])); ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
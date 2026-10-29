<?php 
require_once __DIR__ . '/../../helpers/Auth.php';
Auth::checkRole(array('nurse', 'admin')); // Chỉ Y tá và Admin[cite: 25]

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/navbar.php';
?>

<h2>Danh Mục Quản Lý Thuốc (View Y Tái)</h2>

<table border="1" cellpadding="10" cellspacing="0" style="width: 100%; border-collapse: collapse; background: white;">
    <tr style="background-color: #004b93; color: white;">
        <th>Mã Thuốc</th>
        <th>Tên Thuốc</th>
        <th>Đơn Vị</th>
        <th>Đơn Giá</th>
        <th>Mô Tả / Nhóm Thuốc</th>
    </tr>
    <?php foreach ($medicines as $med): ?>
    <tr>
        <td>#<?php echo $med['medicine_id']; ?></td>
        <td><strong><?php echo htmlspecialchars($med['name']); ?></strong></td>
        <td><?php echo htmlspecialchars($med['unit']); ?></td>
        <td><?php echo number_format($med['price']); ?> VNĐ</td>
        <td><?php echo htmlspecialchars($med['description']); ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
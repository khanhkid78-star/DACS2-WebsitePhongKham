<?php
require_once __DIR__ . '/../../helpers/Auth.php';
Auth::checkRole(array('nurse', 'admin'));

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/navbar.php';
?>

<h2>Kho thuốc</h2>

<div class="table-wrap">
<table>
    <tr>
        <th>Mã</th>
        <th>Tên thuốc</th>
        <th>Đơn vị</th>
        <th>Đơn giá</th>
        <th>Nhóm</th>
    </tr>
    <?php foreach ($medicines as $med): ?>
    <tr>
        <td>#<?php echo (int) $med['medicine_id']; ?></td>
        <td><?php echo htmlspecialchars($med['name']); ?></td>
        <td><?php echo htmlspecialchars($med['unit']); ?></td>
        <td><?php echo number_format($med['price']); ?> đ</td>
        <td><?php echo htmlspecialchars($med['description']); ?></td>
    </tr>
    <?php endforeach; ?>
</table>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
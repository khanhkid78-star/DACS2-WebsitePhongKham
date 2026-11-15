<?php
require_once __DIR__ . '/../../helpers/Auth.php';
Auth::checkRole(array('nurse', 'admin'));
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/navbar.php';
?>

<h2><?php echo $editMedicine ? 'Cập nhật thuốc' : 'Thêm thuốc'; ?></h2>
<form class="card form-narrow" action="/index.php?action=save_medicine" method="POST">
    <?php if ($editMedicine): ?>
        <input type="hidden" name="medicine_id" value="<?php echo (int) $editMedicine['medicine_id']; ?>">
    <?php endif; ?>
    <div class="form-row">
        <label>
            Tên thuốc
            <input type="text" name="name" required value="<?php echo htmlspecialchars((string) ($editMedicine['name'] ?? '')); ?>">
        </label>
        <label>
            Đơn vị
            <input type="text" name="unit" required placeholder="Viên, hộp, chai" value="<?php echo htmlspecialchars((string) ($editMedicine['unit'] ?? '')); ?>">
        </label>
    </div>
    <label>
        Đơn giá
        <input type="number" name="price" min="0" step="1" required value="<?php echo htmlspecialchars((string) ($editMedicine['price'] ?? '')); ?>">
    </label>
    <label>
        Mô tả / nhóm
        <textarea name="description" rows="3"><?php echo htmlspecialchars((string) ($editMedicine['description'] ?? '')); ?></textarea>
    </label>
    <p class="actions">
        <button class="btn-navy" type="submit"><?php echo $editMedicine ? 'Cập nhật' : 'Thêm thuốc'; ?></button>
        <?php if ($editMedicine): ?>
            <a class="btn-ghost" href="/index.php?action=nurse_medicines">Hủy</a>
        <?php endif; ?>
    </p>
</form>

<h2>Kho thuốc</h2>
<div class="table-wrap">
<table>
    <tr>
        <th>Mã</th>
        <th>Tên thuốc</th>
        <th>Đơn vị</th>
        <th>Đơn giá</th>
        <th>Mô tả</th>
        <th>Thao tác</th>
    </tr>
    <?php if (empty($medicines)): ?>
        <tr><td colspan="6">Chưa có thuốc.</td></tr>
    <?php endif; ?>
    <?php foreach ($medicines as $med): ?>
    <tr>
        <td>#<?php echo (int) $med['medicine_id']; ?></td>
        <td><?php echo htmlspecialchars((string) $med['name']); ?></td>
        <td><?php echo htmlspecialchars((string) $med['unit']); ?></td>
        <td><?php echo number_format((float) $med['price']); ?> đ</td>
        <td><?php echo htmlspecialchars((string) $med['description']); ?></td>
        <td>
            <a class="btn-ghost" href="/index.php?action=nurse_medicines&edit=<?php echo (int) $med['medicine_id']; ?>">Sửa</a>
            <form action="/index.php?action=delete_medicine" method="POST" onsubmit="return confirm('Xóa thuốc này?');">
                <input type="hidden" name="medicine_id" value="<?php echo (int) $med['medicine_id']; ?>">
                <button class="btn-logout" type="submit">Xóa</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
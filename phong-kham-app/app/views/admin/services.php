<?php
require_once __DIR__ . '/../../helpers/Auth.php';
Auth::checkRole(array('admin'));
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/navbar.php';
?>
<div class="page-head">
    <div>
        <h2>Dịch vụ khám</h2>
        <p class="lead">Giá này dùng cho bệnh án ghi từ lúc lưu trở đi. Hóa đơn đã lập không đổi.</p>
    </div>
</div>
<form class="card form-wide" action="/index.php?action=save_service" method="POST">
    <input type="hidden" name="service_id" value="0">
    <div class="form-row">
        <label>Chuyên khoa
            <select name="specialty_id" required>
                <?php foreach ($specialties as $row): ?>
                    <option value="<?php echo (int) $row['specialty_id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Tên dịch vụ <input type="text" name="name" required></label>
        <label>Giá <input type="number" name="price" min="0" step="1000" required></label>
    </div>
    <p><button class="btn-navy" type="submit">Thêm dịch vụ</button></p>
</form>
<div class="table-wrap list-scroll">
<table>
    <thead><tr><th>Chuyên khoa</th><th>Dịch vụ</th><th>Giá</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($services as $row): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['specialty']); ?></td>
            <td colspan="3">
                <form action="/index.php?action=save_service" method="POST" class="form-row">
                    <input type="hidden" name="service_id" value="<?php echo (int) $row['service_id']; ?>">
                    <input type="hidden" name="specialty_id" value="<?php echo (int) $row['specialty_id']; ?>">
                    <input type="text" name="name" value="<?php echo htmlspecialchars($row['name']); ?>" required>
                    <input type="number" name="price" min="0" step="1000" value="<?php echo (float) $row['price']; ?>" required>
                    <button class="btn-navy" type="submit">Lưu</button>
                </form>
                <form action="/index.php?action=delete_service" method="POST" onsubmit="return confirm('Xóa dịch vụ này?');">
                    <input type="hidden" name="service_id" value="<?php echo (int) $row['service_id']; ?>">
                    <button class="btn-logout" type="submit">Xóa</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
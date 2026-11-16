<?php
require_once __DIR__ . '/../../helpers/Auth.php';
Auth::checkRole(array('nurse', 'admin'));
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/navbar.php';
?>

<div class="page-head">
    <div>
        <h2>Kho thuốc</h2>
        <p class="lead">Danh sách nằm trong khung cố định. Thêm và sửa thuốc mở trong popup.</p>
    </div>
    <button class="btn-navy" type="button" id="open-new-medicine">Thêm thuốc</button>
</div>

<form class="filters" id="medicine-filters">
    <label>Tìm kiếm
        <input type="search" name="q" placeholder="Tên, mã, mô tả">
    </label>
    <label>Đơn vị
        <select name="unit">
            <option value="">Tất cả</option>
            <?php foreach ($units as $unit): ?>
                <option value="<?php echo htmlspecialchars((string) $unit); ?>"><?php echo htmlspecialchars((string) $unit); ?></option>
            <?php endforeach; ?>
        </select>
    </label>
</form>

<div class="table-wrap list-scroll">
<table>
    <thead>
        <tr>
            <th>Mã</th>
            <th>Tên thuốc</th>
            <th>Đơn vị</th>
            <th>Đơn giá</th>
            <th>Mô tả</th>
            <th></th>
        </tr>
    </thead>
    <tbody id="medicine-body">
        <?php require __DIR__ . '/_medicine_rows.php'; ?>
    </tbody>
</table>
</div>

<div class="modal" id="medicine-modal">
    <div class="modal-card">
        <button class="modal-x" type="button" data-close-box>Đóng</button>
        <h2 id="medicine-title">Thêm thuốc</h2>
        <form id="medicine-form" action="/index.php?action=save_medicine" method="POST">
            <input type="hidden" name="medicine_id" id="medicine-id" disabled>
            <div class="form-row">
                <label>Tên thuốc
                    <input type="text" name="name" id="medicine-name" required>
                </label>
                <label>Đơn vị
                    <input type="text" name="unit" id="medicine-unit" required placeholder="Viên, hộp, chai">
                </label>
            </div>
            <label>Đơn giá
                <input type="number" name="price" id="medicine-price" min="0" step="1" required>
            </label>
            <label>Mô tả / nhóm
                <textarea name="description" id="medicine-description" rows="3"></textarea>
            </label>
            <p><button class="btn-navy" type="submit" id="medicine-submit">Thêm thuốc</button></p>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
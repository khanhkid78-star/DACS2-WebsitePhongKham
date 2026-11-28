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
    <button class="btn-navy" type="button" data-open-box="service-modal">Thêm dịch vụ</button>
</div>

<form class="filters" id="service-filters">
    <label>Tìm kiếm
        <input type="search" id="service-q" placeholder="Tên, chuyên khoa hoặc giá (vd: 150 hoặc 150000)">
    </label>
    <label>Sắp xếp giá
        <select id="service-sort">
            <option value="">Mặc định</option>
            <option value="asc">Giá tăng dần</option>
            <option value="desc">Giá giảm dần</option>
        </select>
    </label>
</form>

<div class="table-wrap list-scroll">
<table class="svc-table">
    <thead>
        <tr><th>Chuyên khoa</th><th>Dịch vụ</th><th>Giá</th><th></th></tr>
    </thead>
    <tbody id="service-body">
    <?php foreach ($services as $row): $sid = (int) $row['service_id']; ?>
        <tr data-price="<?php echo (float) $row['price']; ?>" data-text="<?php echo htmlspecialchars(mb_strtolower($row['specialty'] . ' ' . $row['name'], 'UTF-8')); ?>">
            <td><?php echo htmlspecialchars($row['specialty']); ?></td>
            <td><input form="svc-<?php echo $sid; ?>" type="text" name="name" value="<?php echo htmlspecialchars($row['name']); ?>" required></td>
            <td><input form="svc-<?php echo $sid; ?>" type="number" name="price" min="0" step="1000" value="<?php echo (float) $row['price']; ?>" required></td>
            <td class="row-actions">
                <button class="btn-navy" type="submit" form="svc-<?php echo $sid; ?>">Lưu</button>
                <button class="btn-logout" type="submit" form="del-<?php echo $sid; ?>">Xóa</button>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php foreach ($services as $row): $sid = (int) $row['service_id']; ?>
    <form id="svc-<?php echo $sid; ?>" action="/index.php?action=save_service" method="POST" hidden>
        <input type="hidden" name="service_id" value="<?php echo $sid; ?>">
        <input type="hidden" name="specialty_id" value="<?php echo (int) $row['specialty_id']; ?>">
    </form>
    <form id="del-<?php echo $sid; ?>" action="/index.php?action=delete_service" method="POST" hidden onsubmit="return confirm('Xóa dịch vụ này?');">
        <input type="hidden" name="service_id" value="<?php echo $sid; ?>">
    </form>
<?php endforeach; ?>

<div class="modal" id="service-modal">
    <div class="modal-card">
        <button class="modal-x" type="button" data-close-box>Đóng</button>
        <h2>Thêm dịch vụ</h2>
        <form action="/index.php?action=save_service" method="POST">
            <input type="hidden" name="service_id" value="0">
            <label>Chuyên khoa
                <select name="specialty_id" required>
                    <?php foreach ($specialties as $row): ?>
                        <option value="<?php echo (int) $row['specialty_id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Tên dịch vụ <input type="text" name="name" required></label>
            <label>Giá <input type="number" name="price" min="0" step="1000" required></label>
            <p><button class="btn-navy" type="submit">Lưu dịch vụ</button></p>
        </form>
    </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", function () {
    var q = document.getElementById("service-q");
    var sort = document.getElementById("service-sort");
    var body = document.getElementById("service-body");
    if (!q || !body) return;
    function apply() {
        var needle = q.value.toLowerCase().trim();
        var digits = needle.replace(/\D/g, "");
        var rows = Array.prototype.slice.call(body.querySelectorAll("tr"));
        rows.forEach(function (row) {
            var text = row.getAttribute("data-text") || "";
            var price = String(Math.round(Number(row.getAttribute("data-price") || 0)));
            var hit = needle === "" || text.indexOf(needle) !== -1 || (digits.length >= 2 && price.indexOf(digits) !== -1);
            row.hidden = !hit;
        });
        var visible = rows.filter(function (row) { return !row.hidden; });
        var mode = sort.value;
        if (mode === "asc" || mode === "desc") {
            visible.sort(function (a, b) {
                var diff = Number(a.getAttribute("data-price")) - Number(b.getAttribute("data-price"));
                return mode === "asc" ? diff : -diff;
            });
            visible.forEach(function (row) { body.appendChild(row); });
        }
    }
    q.addEventListener("input", apply);
    sort.addEventListener("change", apply);
});
</script>
<?php require __DIR__ . '/../partials/footer.php'; ?>
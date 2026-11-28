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
        <tr>
            <th>Chuyên khoa</th>
            <th>Dịch vụ</th>
            <th>Giá</th>
            <th></th>
        </tr>
    </thead>
    <tbody id="service-body">
    <?php foreach ($services as $row):
        $sid = (int) $row['service_id'];
        $payload = htmlspecialchars(json_encode(array(
            'id' => $sid,
            'specialty_id' => (int) $row['specialty_id'],
            'name' => (string) $row['name'],
            'price' => (float) $row['price'],
        ), JSON_UNESCAPED_UNICODE), ENT_QUOTES);
    ?>
        <tr
            data-price="<?php echo (float) $row['price']; ?>"
            data-text="<?php echo htmlspecialchars(mb_strtolower($row['specialty'] . ' ' . $row['name'], 'UTF-8')); ?>"
        >
            <td><?php echo htmlspecialchars($row['specialty']); ?></td>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td><?php echo number_format((float) $row['price']); ?> đ</td>
            <td class="row-actions">
                <button class="btn-ghost" type="button" data-service="<?php echo $payload; ?>">
                    Sửa
                </button>
                <form action="/index.php?action=delete_service" method="POST" onsubmit="return confirm('Xóa dịch vụ này?');">
                    <input type="hidden" name="service_id" value="<?php echo $sid; ?>">
                    <button class="btn-logout" type="submit">Xóa</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<div class="modal" id="service-modal">
    <div class="modal-card">
        <button class="modal-x" type="button" data-close-box>Đóng</button>
        <h2 id="service-title">Thêm dịch vụ</h2>
        <form id="service-form" action="/index.php?action=save_service" method="POST">
            <input type="hidden" name="service_id" id="service-id" value="0">
            <label>
                Chuyên khoa
                <select name="specialty_id" id="service-specialty" required>
                    <?php foreach ($specialties as $row): ?>
                        <option value="<?php echo (int) $row['specialty_id']; ?>">
                            <?php echo htmlspecialchars($row['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Tên dịch vụ
                <input type="text" name="name" id="service-name" required>
            </label>
            <label>
                Giá
                <input type="number" name="price" id="service-price" min="0" step="1000" required>
            </label>
            <p>
                <button class="btn-navy" type="submit" id="service-submit">Lưu dịch vụ</button>
            </p>
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
<?php
require_once __DIR__ . '/../../helpers/Auth.php';
$role = Auth::getRole();
if ($role === 'receptionist') {
    Auth::checkRole(array('receptionist'));
} else {
    Auth::checkRole(array('admin'));
}
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/navbar.php';

$fields = array(
    'doctor' => array(
        array('name' => 'full_name', 'label' => 'Họ tên', 'type' => 'text', 'required' => true),
        array('name' => 'gender', 'label' => 'Giới tính', 'type' => 'select', 'options' => array('Nam', 'Nữ', 'Khác'), 'required' => true),
        array('name' => 'date_of_birth', 'label' => 'Ngày sinh', 'type' => 'date'),
        array('name' => 'specialty', 'label' => 'Chuyên khoa', 'type' => 'text', 'required' => true),
        array('name' => 'phone', 'label' => 'Số điện thoại', 'type' => 'text', 'required' => true),
        array('name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true),
        array('name' => 'degree', 'label' => 'Học vị', 'type' => 'text', 'required' => true),
    ),
    'nurse' => array(
        array('name' => 'full_name', 'label' => 'Họ tên', 'type' => 'text', 'required' => true),
        array('name' => 'gender', 'label' => 'Giới tính', 'type' => 'select', 'options' => array('Nam', 'Nữ', 'Khác'), 'required' => true),
        array('name' => 'date_of_birth', 'label' => 'Ngày sinh', 'type' => 'date'),
        array('name' => 'specialty', 'label' => 'Chuyên khoa', 'type' => 'text'),
        array('name' => 'phone', 'label' => 'Số điện thoại', 'type' => 'text'),
        array('name' => 'email', 'label' => 'Email', 'type' => 'email'),
        array('name' => 'degree', 'label' => 'Học vị', 'type' => 'text'),
    ),
    'receptionist' => array(
        array('name' => 'full_name', 'label' => 'Họ tên', 'type' => 'text', 'required' => true),
        array('name' => 'gender', 'label' => 'Giới tính', 'type' => 'select', 'options' => array('Nam', 'Nữ', 'Khác'), 'required' => true),
        array('name' => 'date_of_birth', 'label' => 'Ngày sinh', 'type' => 'date'),
        array('name' => 'phone', 'label' => 'Số điện thoại', 'type' => 'text', 'required' => true),
        array('name' => 'email', 'label' => 'Email', 'type' => 'email'),
        array('name' => 'address', 'label' => 'Địa chỉ', 'type' => 'text'),
        array('name' => 'shift', 'label' => 'Ca làm', 'type' => 'text'),
        array('name' => 'status', 'label' => 'Trạng thái', 'type' => 'select', 'options' => array('ACTIVE', 'INACTIVE')),
    ),
    'patient' => array(
        array('name' => 'full_name', 'label' => 'Họ tên', 'type' => 'text', 'required' => true),
        array('name' => 'date_of_birth', 'label' => 'Ngày sinh', 'type' => 'date', 'required' => true),
        array('name' => 'gender', 'label' => 'Giới tính', 'type' => 'select', 'options' => array('Nam', 'Nữ', 'Khác'), 'required' => true),
        array('name' => 'phone', 'label' => 'Số điện thoại', 'type' => 'text', 'required' => true),
        array('name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true),
        array('name' => 'address', 'label' => 'Địa chỉ', 'type' => 'text', 'required' => true),
        array('name' => 'cccd', 'label' => 'CCCD', 'type' => 'text', 'required' => true),
        array('name' => 'bhyt', 'label' => 'Số BHYT', 'type' => 'text', 'required' => true),
    ),
);
$labels = array(
    'id' => 'Mã', 'full_name' => 'Họ tên', 'gender' => 'Giới tính', 'date_of_birth' => 'Ngày sinh',
    'specialty' => 'Chuyên khoa', 'phone' => 'Số điện thoại', 'email' => 'Email', 'degree' => 'Học vị',
    'address' => 'Địa chỉ', 'shift' => 'Ca làm', 'status' => 'Trạng thái', 'cccd' => 'CCCD',
    'bhyt' => 'Số BHYT', 'created_at' => 'Ngày tạo',
);
?>
<div class="page-head">
    <div>
        <h2><?php echo $role === 'admin' ? 'Nhân sự' : 'Bệnh nhân'; ?></h2>
        <p class="lead">Bảng chỉ hiện các cột chính. Bấm một dòng để xem đủ thông tin. Ảnh lưu trong cơ sở dữ liệu.</p>
    </div>
    <button class="btn-navy" type="button" id="open-person">Thêm hồ sơ</button>
</div>

<?php if (count($tabs) > 1): ?>
    <div class="staff-tabs">
        <?php foreach ($tabs as $key => $label): ?>
            <a class="<?php echo $kind === $key ? 'btn-navy' : 'btn-ghost'; ?>" href="/index.php?action=staff&kind=<?php echo $key; ?>"><?php echo $label; ?></a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form class="filters" id="staff-filters">
    <input type="hidden" name="kind" value="<?php echo htmlspecialchars($kind); ?>">
    <label>Tìm kiếm
        <input type="search" name="q" placeholder="Tên, điện thoại, email, mã<?php echo $kind === 'patient' ? ', CCCD, BHYT' : ''; ?>">
    </label>
    <label>Giới tính
        <select name="gender">
            <option value="">Tất cả</option>
            <option value="Nam">Nam</option>
            <option value="Nữ">Nữ</option>
            <option value="Khác">Khác</option>
        </select>
    </label>
    <?php if ($kind === 'doctor' || $kind === 'nurse'): ?>
        <label>Chuyên khoa
            <select name="specialty">
                <option value="">Tất cả</option>
                <?php foreach ($specialties as $item): ?>
                    <option value="<?php echo htmlspecialchars((string) $item); ?>"><?php echo htmlspecialchars((string) $item); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    <?php endif; ?>
    <?php if ($kind === 'receptionist'): ?>
        <label>Ca làm
            <select name="shift">
                <option value="">Tất cả</option>
                <?php foreach ($shifts as $item): ?>
                    <option value="<?php echo htmlspecialchars((string) $item); ?>"><?php echo htmlspecialchars((string) $item); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Trạng thái
            <select name="status">
                <option value="">Tất cả</option>
                <?php foreach ($statuses as $item): ?>
                    <option value="<?php echo htmlspecialchars((string) $item); ?>"><?php echo htmlspecialchars((string) $item); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    <?php endif; ?>
    <label>Sinh từ <input type="date" name="from"></label>
    <label>Sinh đến <input type="date" name="to"></label>
</form>

<div class="table-wrap list-scroll">
<table>
    <thead>
        <tr>
            <th>Ảnh</th><th>Mã</th><th>Họ tên</th><th>Giới tính</th><th>Điện thoại</th>
            <?php if ($kind === 'doctor' || $kind === 'nurse'): ?><th>Chuyên khoa</th>
            <?php elseif ($kind === 'receptionist'): ?><th>Ca</th><th>Trạng thái</th>
            <?php else: ?><th>CCCD</th><?php endif; ?>
            <th></th>
        </tr>
    </thead>
    <tbody id="staff-body"><?php require __DIR__ . '/_rows.php'; ?></tbody>
</table>
</div>

<div class="modal" id="person-detail">
    <div class="modal-card">
        <button class="modal-x" type="button" data-close-box>Đóng</button>
        <h2 id="detail-title">Chi tiết</h2>
        <img id="detail-photo" class="avatar avatar-lg" alt="" hidden>
        <div id="detail-body"></div>
    </div>
</div>

<div class="modal" id="person-modal">
    <div class="modal-card">
        <button class="modal-x" type="button" data-close-box>Đóng</button>
        <h2 id="person-title">Thêm hồ sơ</h2>
        <form action="/index.php?action=staff_save" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="kind" value="<?php echo htmlspecialchars($kind); ?>">
            <input type="hidden" name="id" id="person-id" value="0">
            <img id="person-preview" class="avatar avatar-lg" alt="" hidden>
            <?php foreach ($fields[$kind] as $field): ?>
                <label><?php echo $field['label']; ?>
                    <?php if ($field['type'] === 'select'): ?>
                        <select name="<?php echo $field['name']; ?>" <?php echo !empty($field['required']) ? 'required' : ''; ?>>
                            <option value="">Chọn</option>
                            <?php foreach ($field['options'] as $option): ?>
                                <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input type="<?php echo $field['type']; ?>" name="<?php echo $field['name']; ?>" <?php echo !empty($field['required']) ? 'required' : ''; ?>>
                    <?php endif; ?>
                </label>
            <?php endforeach; ?>
            <label>Ảnh cá nhân <input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label>
            <label class="rx-check"><input type="checkbox" name="clear_photo" value="1"> Xóa ảnh hiện tại</label>
            <p><button class="btn-navy" type="submit">Lưu</button></p>
        </form>
    </div>
</div>

<script>
var fieldLabels = <?php echo json_encode($labels, JSON_UNESCAPED_UNICODE); ?>;
function setPersonField(name, value) {
    var el = document.querySelector("#person-modal [name='" + name + "']");
    if (!el) return;
    if (el.tagName === "SELECT" && value) {
        var found = false;
        for (var i = 0; i < el.options.length; i++) {
            if (el.options[i].value === value) found = true;
        }
        if (!found) {
            var opt = document.createElement("option");
            opt.value = value;
            opt.textContent = value;
            el.appendChild(opt);
        }
    }
    el.value = value || "";
}
function loadPerson(kind, id, mode) {
    jQuery.getJSON("/index.php?action=staff_detail&kind=" + encodeURIComponent(kind) + "&id=" + encodeURIComponent(id))
        .done(function (res) {
            if (!res || !res.ok) return;
            var d = res.data;
            if (mode === "edit") {
                document.getElementById("person-title").textContent = "Sửa hồ sơ #" + d.id;
                document.getElementById("person-id").value = d.id;
                Object.keys(d).forEach(function (key) { setPersonField(key, d[key]); });
                var preview = document.getElementById("person-preview");
                preview.hidden = !d.photo_url;
                preview.src = d.photo_url || "";
                openBox("person-modal");
                return;
            }
            document.getElementById("detail-title").textContent = "Chi tiết #" + d.id;
            var photo = document.getElementById("detail-photo");
            photo.hidden = !d.photo_url;
            photo.src = d.photo_url || "";
            var html = "";
            Object.keys(fieldLabels).forEach(function (key) {
                if (d[key] === undefined || d[key] === null || d[key] === "") return;
                html += "<p><strong>" + fieldLabels[key] + ":</strong> " + esc(d[key]) + "</p>";
            });
            document.getElementById("detail-body").innerHTML = html;
            openBox("person-detail");
        })
        
        .fail(function () {
            document.getElementById("detail-title").textContent = "Chi tiết";
            document.getElementById("detail-body").innerHTML = "<p class=\"lead\">Không tải được chi tiết hồ sơ.</p>";
            openBox("person-detail");
        });
}

document.getElementById("open-person").addEventListener("click", function () {
    document.querySelector("#person-modal form").reset();
    document.getElementById("person-id").value = "0";
    document.getElementById("person-title").textContent = "Thêm hồ sơ";
    document.getElementById("person-preview").hidden = true;
    openBox("person-modal");
});

window.addEventListener("load", function () {
    jQuery(document).on("click", "tr.person-row", function (e) {
        if (jQuery(e.target).closest("button, a, form").length && !jQuery(e.target).closest("[data-edit]").length) {
            return;
        }
        var mode = jQuery(e.target).closest("[data-edit]").length ? "edit" : "detail";
        loadPerson(jQuery(this).attr("data-kind"), jQuery(this).attr("data-id"), mode);
    });
});

</script>
<?php require __DIR__ . '/../partials/footer.php'; ?>
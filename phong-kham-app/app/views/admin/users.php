<?php
require_once __DIR__ . '/../../helpers/Auth.php';
Auth::checkRole(array('admin'));
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/navbar.php';

function staffOptions($rows) {
    echo '<option value="">Chọn hồ sơ</option>';
    foreach ($rows as $row) {
        $name = trim((string) $row['full_name']);
        if ($name === '') {
            $name = 'Hồ sơ #' . (int) $row['id'];
        }
        echo '<option value="' . (int) $row['id'] . '">' . htmlspecialchars($name) . '</option>';
    }
}
?>

<div class="page-head">
    <div>
        <h2>Tài khoản</h2>
        <p class="lead">
            Một chỗ cho hồ sơ của bạn và danh sách tài khoản.
            Tạo mới, đặt mật khẩu và sửa hồ sơ mở trong cửa sổ.
        </p>
    </div>
    <div class="actions">
        <button class="btn-ghost" type="button" data-open-box="own-account-modal">Hồ sơ của tôi</button>
        <button class="btn-navy" type="button" data-open-box="create-user-modal">Tạo tài khoản</button>
    </div>
</div>

<div class="table-wrap list-scroll">
<table>
    <thead>
        <tr>
            <th>Mã</th>
            <th>Tên đăng nhập</th>
            <th>Vai trò</th>
            <th>Thao tác</th>
        </tr>
    </thead>
    <?php foreach ($users as $u): ?>
    <tr>
        <td>#<?php echo (int) $u['user_id']; ?></td>
        <td><?php echo htmlspecialchars((string) $u['username']); ?></td>
        <td><span class="badge badge-ok"><?php echo htmlspecialchars(Auth::roleLabel($u['role'])); ?></span></td>
        <td>
            <?php if ($u['role'] !== 'patient'):
                $payload = htmlspecialchars(json_encode(array(
                    'id' => (int) $u['user_id'],
                    'username' => (string) $u['username'],
                    'role' => (string) $u['role'],
                ), JSON_UNESCAPED_UNICODE), ENT_QUOTES);
            ?>
                <div class="row-actions">
                    <button class="btn-ghost" type="button" data-edit-user="<?php echo $payload; ?>">
                        Sửa
                    </button>
                    <?php if ((int) $u['user_id'] !== (int) $_SESSION['user_id']): ?>
                        <form action="/index.php?action=delete_user" method="POST" onsubmit="return confirm('Xóa tài khoản này? Hồ sơ nhân sự vẫn còn.');">
                            <input type="hidden" name="user_id" value="<?php echo (int) $u['user_id']; ?>">
                            <button class="btn-logout" type="submit">Xóa</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
</div>

<div class="modal" id="own-account-modal">
    <div class="modal-card">
        <button class="modal-x" type="button" data-close-box>Đóng</button>
        <h2>Hồ sơ của tôi</h2>
        <form id="own-save" action="/index.php?action=save_account" method="POST">
            <label>
                Tên đăng nhập
                <input type="text" name="username" required value="<?php echo htmlspecialchars((string) ($_SESSION['username'] ?? '')); ?>">
            </label>
            <label>
                Mật khẩu mới
                <input type="password" name="password" minlength="5" placeholder="Để trống nếu không đổi">
            </label>
            <label>
                Vai trò
                <select name="role">
                    <?php
                    $ownRole = Auth::getRole();
                    $roles = array(
                        'admin' => 'Quản trị',
                        'doctor' => 'Bác sĩ',
                        'nurse' => 'Y tá',
                        'receptionist' => 'Lễ tân',
                    );
                    foreach ($roles as $value => $label):
                    ?>
                        <option value="<?php echo $value; ?>" <?php echo $ownRole === $value ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </form>
        <form id="own-delete" action="/index.php?action=delete_account" method="POST" onsubmit="return confirm('Xóa tài khoản này? Bạn sẽ không đăng nhập được nữa.');"></form>
        <div class="account-actions">
            <button class="btn-navy" type="submit" form="own-save">Lưu tài khoản</button>
            <button class="btn-logout" type="submit" form="own-delete">Xóa tài khoản của tôi</button>
        </div>
    </div>
</div>

<div class="modal" id="edit-user-modal">
    <div class="modal-card">
        <button class="modal-x" type="button" data-close-box>Đóng</button>
        <h2 id="edit-user-title">Sửa tài khoản</h2>
        <form action="/index.php?action=save_user" method="POST">
            <input type="hidden" name="form" value="edit">
            <input type="hidden" name="user_id" id="edit-user-id" value="0">
            <label>
                Tên đăng nhập
                <input type="text" name="username" id="edit-username" required>
            </label>
            <label>
                Mật khẩu mới
                <input type="password" name="password" id="edit-password" minlength="5" placeholder="Để trống nếu không đổi">
            </label>
            <label>
                Vai trò
                <select name="role" id="edit-role">
                    <option value="doctor">Bác sĩ</option>
                    <option value="nurse">Y tá</option>
                    <option value="receptionist">Lễ tân</option>
                    <option value="admin">Quản trị</option>
                </select>
            </label>
            <p><button class="btn-navy" type="submit">Lưu tài khoản</button></p>
        </form>
    </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", function () {
    document.addEventListener("click", function (event) {
        var button = event.target.closest("[data-edit-user]");
        if (!button) return;
        var data = JSON.parse(button.getAttribute("data-edit-user"));
        document.getElementById("edit-user-id").value = data.id;
        document.getElementById("edit-username").value = data.username;
        document.getElementById("edit-password").value = "";
        document.getElementById("edit-role").value = data.role;
        document.getElementById("edit-user-title").textContent = "Sửa tài khoản #" + data.id;
        openBox("edit-user-modal");
    });
});
</script>

<div class="modal" id="create-user-modal">
    <div class="modal-card">
        <button class="modal-x" type="button" data-close-box>Đóng</button>
        <h2>Tạo tài khoản nhân sự</h2>
        <p class="lead">Bệnh nhân tự đăng ký. Ở đây chỉ tạo quản trị, bác sĩ, y tá và lễ tân.</p>
        <form action="/index.php?action=save_user" method="POST">
            <input type="hidden" name="form" value="create">
            <div class="form-row">
                <label>
                    Tên đăng nhập
                    <input type="text" name="username" required>
                </label>
                <label>
                    Mật khẩu
                    <input type="password" name="password" required minlength="5">
                </label>
            </div>
            <label>
                Vai trò
                <select name="role" data-role>
                    <option value="doctor">Bác sĩ</option>
                    <option value="nurse">Y tá</option>
                    <option value="receptionist">Lễ tân</option>
                    <option value="admin">Quản trị</option>
                </select>
            </label>
            <div data-role-box="doctor">
                <label>Hồ sơ bác sĩ chưa có tài khoản
                    <select name="doctor_id"><?php staffOptions($openDoctors); ?></select>
                </label>
            </div>
            <div data-role-box="nurse" class="is-hidden">
                <label>Hồ sơ y tá chưa có tài khoản
                    <select name="nurse_id"><?php staffOptions($openNurses); ?></select>
                </label>
            </div>
            <div data-role-box="receptionist" class="is-hidden">
                <label>Hồ sơ lễ tân chưa có tài khoản
                    <select name="receptionist_id"><?php staffOptions($openReceptionists); ?></select>
                </label>
            </div>
            <p><button class="btn-navy" type="submit">Tạo tài khoản</button></p>
        </form>
    </div>
</div>

<div class="modal" id="reset-user-modal">
    <div class="modal-card">
        <button class="modal-x" type="button" data-close-box>Đóng</button>
        <h2>Đặt lại mật khẩu</h2>
        <form action="/index.php?action=save_user" method="POST">
            <input type="hidden" name="form" value="reset">
            <label>
                Tên đăng nhập
                <input type="text" name="username" required>
            </label>
            <label>
                Mật khẩu mới
                <input type="password" name="password" required minlength="5">
            </label>
            <p><button class="btn-navy" type="submit">Đặt lại</button></p>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
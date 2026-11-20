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
            <?php if ($u['role'] !== 'patient' && (int) $u['user_id'] !== (int) $_SESSION['user_id']): ?>
                <form action="/index.php?action=delete_user" method="POST" onsubmit="return confirm('Xóa tài khoản này? Hồ sơ nhân sự vẫn còn.');">
                    <input type="hidden" name="user_id" value="<?php echo (int) $u['user_id']; ?>">
                    <button class="btn-logout" type="submit">Xóa</button>
                </form>
            <?php elseif ((int) $u['user_id'] === (int) $_SESSION['user_id']): ?>
                <button class="btn-ghost" type="button" data-open-box="own-account-modal">Sửa</button>
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
        <p class="lead">Bạn không đổi được quyền quản trị.</p>
        <form action="/index.php?action=save_account" method="POST">
            <label>
                Tên đăng nhập
                <input type="text" name="username" required value="<?php echo htmlspecialchars((string) ($_SESSION['username'] ?? '')); ?>">
            </label>
            <label>
                Mật khẩu mới
                <input type="password" name="password" minlength="5" placeholder="Để trống nếu không đổi">
            </label>
            <p><button class="btn-navy" type="submit">Lưu tài khoản</button></p>
        </form>
        <form action="/index.php?action=delete_account" method="POST" onsubmit="return confirm('Xóa tài khoản này? Bạn sẽ không đăng nhập được nữa.');">
            <button class="btn-logout" type="submit">Xóa tài khoản của tôi</button>
        </form>
    </div>
</div>

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
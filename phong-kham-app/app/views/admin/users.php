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

<h2>Tạo tài khoản nhân sự</h2>
<p class="lead">Bệnh nhân tự đăng ký. Ở đây chỉ tạo quản trị, bác sĩ, y tá và lễ tân.</p>
<form class="card form-wide" action="/index.php?action=save_user" method="POST">
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

<h2>Đặt lại mật khẩu</h2>
<form class="card form-narrow" action="/index.php?action=save_user" method="POST">
    <input type="hidden" name="form" value="reset">
    <div class="form-row">
        <label>
            Tên đăng nhập
            <input type="text" name="username" required>
        </label>
        <label>
            Mật khẩu mới
            <input type="password" name="password" required minlength="5">
        </label>
    </div>
    <p><button class="btn-navy" type="submit">Đặt lại</button></p>
</form>

<h2>Tài khoản hệ thống</h2>
<div class="table-wrap">
<table>
    <tr>
        <th>Mã</th>
        <th>Tên đăng nhập</th>
        <th>Vai trò</th>
        <th>Liên kết</th>
        <th>Thao tác</th>
    </tr>
    <?php foreach ($users as $u): ?>
    <tr>
        <td>#<?php echo (int) $u['user_id']; ?></td>
        <td><?php echo htmlspecialchars((string) $u['username']); ?></td>
        <td><span class="badge badge-ok"><?php echo htmlspecialchars(Auth::roleLabel($u['role'])); ?></span></td>
        <td>
            <?php
                if ($u['doctor_id']) {
                    echo 'Bác sĩ #' . (int) $u['doctor_id'];
                } elseif ($u['nurse_id']) {
                    echo 'Y tá #' . (int) $u['nurse_id'];
                } elseif ($u['receptionist_id']) {
                    echo 'Lễ tân #' . (int) $u['receptionist_id'];
                } elseif ($u['patient_id']) {
                    echo 'Bệnh nhân #' . (int) $u['patient_id'];
                } else {
                    echo 'Quản trị viên hệ thống';
                }
            ?>
        </td>
        <td>
            <?php if ($u['role'] !== 'patient' && (int) $u['user_id'] !== (int) $_SESSION['user_id']): ?>
                <form action="/index.php?action=delete_user" method="POST" onsubmit="return confirm('Xóa tài khoản này? Hồ sơ nhân sự vẫn còn.');">
                    <input type="hidden" name="user_id" value="<?php echo (int) $u['user_id']; ?>">
                    <button class="btn-logout" type="submit">Xóa</button>
                </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
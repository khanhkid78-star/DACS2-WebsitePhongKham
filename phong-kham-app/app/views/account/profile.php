<?php
require_once __DIR__ . '/../../helpers/Auth.php';
Auth::startSession();
if (!Auth::isLoggedIn()) {
    header('Location: /index.php?action=login');
    exit();
}
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/navbar.php';

$dob = '';
if (!empty($profile['date_of_birth'])) {
    $dob = substr((string) $profile['date_of_birth'], 0, 10);
}
$genders = array('Nam', 'Nữ', 'Khác');
$currentGender = (string) ($profile['gender'] ?? '');
if ($currentGender !== '' && !in_array($currentGender, $genders, true)) {
    array_unshift($genders, $currentGender);
}
?>

<h2>Tài khoản</h2>
<p class="lead">
    Vai trò: <?php echo htmlspecialchars(Auth::roleLabel($accountUser['role'])); ?>.
    Bạn không đổi được quyền.
</p>

<?php if ($accountKind !== 'admin' && $profile): ?>
<form class="card form-wide" action="/index.php?action=save_profile" method="POST" enctype="multipart/form-data">
    <?php if ($accountKind === 'patient'): ?>
        <?php if (!empty($profile['photo'])): ?>
            <img class="avatar avatar-lg" alt="" src="/index.php?action=account_photo">
        <?php else: ?>
            <span class="avatar avatar-lg avatar-empty">?</span>
        <?php endif; ?>
        <label>Ảnh cá nhân
            <input type="file" name="photo" accept="image/jpeg,image/png,image/webp">
        </label>
        <label class="rx-check"><input type="checkbox" name="clear_photo" value="1"> Xóa ảnh hiện tại</label>
    <?php endif; ?>

    <label>Họ tên
        <input type="text" name="full_name" value="<?php echo htmlspecialchars((string) ($profile['full_name'] ?? '')); ?>">
    </label>
    <div class="form-row">
        <label>Ngày sinh
            <input type="date" name="date_of_birth" value="<?php echo htmlspecialchars($dob); ?>">
        </label>
        <label>Giới tính
            <select name="gender">
                <?php foreach ($genders as $g): ?>
                    <option value="<?php echo htmlspecialchars($g); ?>" <?php echo $currentGender === $g ? 'selected' : ''; ?>><?php echo htmlspecialchars($g); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>
    <div class="form-row">
        <label>Số điện thoại
            <input type="tel" name="phone" value="<?php echo htmlspecialchars((string) ($profile['phone'] ?? '')); ?>">
        </label>
        <label>Email
            <input type="email" name="email" value="<?php echo htmlspecialchars((string) ($profile['email'] ?? '')); ?>">
        </label>
    </div>
    <?php if ($accountKind === 'patient'): ?>
        <label>Địa chỉ
            <input type="text" name="address" value="<?php echo htmlspecialchars((string) ($profile['address'] ?? '')); ?>">
        </label>
        <div class="form-row">
            <label>CCCD
                <input type="text" name="cccd" maxlength="12" value="<?php echo htmlspecialchars((string) ($profile['cccd'] ?? '')); ?>">
            </label>
            <label>Số BHYT
                <input type="text" name="bhyt" maxlength="15" value="<?php echo htmlspecialchars((string) ($profile['bhyt'] ?? '')); ?>">
            </label>
        </div>
    <?php elseif ($accountKind === 'doctor' || $accountKind === 'nurse'): ?>
        <div class="form-row">
            <label>Chuyên khoa
                <input type="text" name="specialty" value="<?php echo htmlspecialchars((string) ($profile['specialty'] ?? '')); ?>">
            </label>
            <label>Bằng cấp
                <input type="text" name="degree" value="<?php echo htmlspecialchars((string) ($profile['degree'] ?? '')); ?>">
            </label>
        </div>
    <?php else: ?>
        <label>Địa chỉ
            <input type="text" name="address" value="<?php echo htmlspecialchars((string) ($profile['address'] ?? '')); ?>">
        </label>
        <label>Ca làm
            <input type="text" name="shift" value="<?php echo htmlspecialchars((string) ($profile['shift'] ?? '')); ?>">
        </label>
    <?php endif; ?>
    <p><button class="btn-navy" type="submit">Lưu hồ sơ</button></p>
</form>
<?php endif; ?>

<div class="card form-wide">
    <form action="/index.php?action=save_account" method="POST">
        <div class="form-row">
            <label>Tên đăng nhập
                <input type="text" name="username" required value="<?php echo htmlspecialchars((string) $accountUser['username']); ?>">
            </label>
            <label>Mật khẩu mới
                <input type="password" name="password" minlength="5" placeholder="Để trống nếu không đổi">
            </label>
        </div>
        <p><button class="btn-navy" type="submit">Lưu tài khoản</button></p>
    </form>
    <form action="/index.php?action=delete_account" method="POST" onsubmit="return confirm('Xóa tài khoản này? Hồ sơ khám bệnh vẫn được giữ.');">
        <button class="btn-logout" type="submit">Xóa tài khoản</button>
    </form>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
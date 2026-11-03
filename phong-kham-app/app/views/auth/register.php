<?php
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/navbar.php';
?>

<div class="card login-box">
    <h2>Đăng ký bệnh nhân</h2>
    <p class="lead">Điền đủ ngày sinh, giới tính, CCCD và BHYT rồi mới đặt lịch được.</p>

    <?php if (!empty($error)): ?>
        <p class="alert"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form action="/index.php?action=do_register" method="POST">
        <div class="form-row">
            <label>
                Tên đăng nhập
                <input type="text" name="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </label>
            <label>
                Mật khẩu
                <input type="password" name="password" required minlength="5">
            </label>
        </div>
        <label>
            Họ tên
            <input type="text" name="full_name" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
        </label>
        <div class="form-row">
            <label>
                Ngày sinh
                <input type="date" name="date_of_birth" required value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>">
            </label>
            <label>
                Giới tính
                <select name="gender" required>
                    <option value="">Chọn</option>
                    <?php foreach (array('Nam', 'Nữ', 'Khác') as $g): ?>
                        <option value="<?php echo $g; ?>" <?php echo (($_POST['gender'] ?? '') === $g) ? 'selected' : ''; ?>>
                            <?php echo $g; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <div class="form-row">
            <label>
                Số điện thoại (10 số)
                <input type="tel" name="phone" required pattern="\d{10}" maxlength="10" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
            </label>
            <label>
                Email
                <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </label>
        </div>
        <label>
            Địa chỉ
            <input type="text" name="address" required value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
        </label>
        <div class="form-row">
            <label>
                CCCD (12 số)
                <input type="text" name="cccd" required pattern="\d{12}" maxlength="12" value="<?php echo htmlspecialchars($_POST['cccd'] ?? ''); ?>">
            </label>
            <label>
                Số BHYT
                <input type="text" name="bhyt" required maxlength="15" value="<?php echo htmlspecialchars($_POST['bhyt'] ?? ''); ?>">
            </label>
        </div>
        <p>
            <button class="btn-navy" type="submit">Tạo tài khoản</button>
        </p>
    </form>
    <p class="lead">Đã có tài khoản? <a href="/index.php?action=login&next=booking">Đăng nhập</a></p>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
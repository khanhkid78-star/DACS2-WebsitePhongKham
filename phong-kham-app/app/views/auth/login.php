<?php
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/navbar.php';

if (isset($_POST['next'])) {
    $nextValue = $_POST['next'];
} elseif (isset($next)) {
    $nextValue = $next;
} else {
    $nextValue = $_GET['next'] ?? '';
}

if (isset($_POST['doctor_id'])) {
    $doctorValue = (int) $_POST['doctor_id'];
} elseif (isset($doctor_id)) {
    $doctorValue = (int) $doctor_id;
} else {
    $doctorValue = (int) ($_GET['doctor_id'] ?? 0);
}
?>

<div class="card login-box">
    <h2>Đăng nhập</h2>

    <?php if (!empty($registered)): ?>
        <p class="alert alert-ok">Tạo tài khoản xong. Đăng nhập để đặt lịch.</p>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <p class="alert"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form action="/index.php?action=do_login" method="POST">
        <input type="hidden" name="next" value="<?php echo htmlspecialchars((string) $nextValue, ENT_QUOTES); ?>">
        <input type="hidden" name="doctor_id" value="<?php echo $doctorValue; ?>">
        <label>
            Tên đăng nhập
            <input type="text" name="username" required>
        </label>
        <label>
            Mật khẩu
            <input type="password" name="password" required>
        </label>
        <p>
            <button class="btn-navy" type="submit">Đăng nhập</button>
        </p>
    </form>
    <p class="lead">Chưa có tài khoản? <a href="/index.php?action=register" data-open="register">Đăng ký</a></p>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
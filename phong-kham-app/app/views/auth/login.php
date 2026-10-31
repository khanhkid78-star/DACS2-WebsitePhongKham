<?php
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/navbar.php';
?>

<div class="card login-box">
    <h2>Đăng nhập</h2>

    <?php if (isset($error)): ?>
        <p class="alert"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form action="/index.php?action=do_login" method="POST">
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
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
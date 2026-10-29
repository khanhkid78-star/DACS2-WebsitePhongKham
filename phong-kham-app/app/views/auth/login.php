<?php require_once __DIR__ . '/../partials/header.php'; ?>
<?php require_once __DIR__ . '/../partials/navbar.php'; ?>

<div style="max-width: 400px; margin: 40px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h2 style="text-align: center; color: #004b93; margin-top: 0;">ĐĂNG NHẬP HỆ THỐNG</h2>

    <?php if (isset($error)): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 14px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form action="/index.php?action=do_login" method="POST">
        <div style="margin-bottom: 15px;">
            <label><strong>Tên đăng nhập:</strong></label>
            <input type="text" name="username" required style="width: 100%; padding: 10px; margin-top: 5px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px;">
        </div>

        <div style="margin-bottom: 20px;">
            <label><strong>Mật khẩu:</strong></label>
            <input type="password" name="password" required style="width: 100%; padding: 10px; margin-top: 5px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px;">
        </div>

        <button type="submit" style="width: 100%; background: #004b93; color: white; border: none; padding: 12px; font-weight: bold; border-radius: 4px; cursor: pointer;">
            ĐĂNG NHẬP
        </button>
    </form>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
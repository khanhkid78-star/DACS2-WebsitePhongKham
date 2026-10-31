<?php
require_once __DIR__ . '/../../helpers/Auth.php';
Auth::checkRole(array('admin'));

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/navbar.php';
?>

<h2>Tài khoản hệ thống</h2>

<div class="table-wrap">
<table>
    <tr>
        <th>Mã</th>
        <th>Tên đăng nhập</th>
        <th>Vai trò</th>
        <th>Liên kết</th>
    </tr>
    <?php foreach ($users as $u): ?>
    <tr>
        <td>#<?php echo (int) $u['user_id']; ?></td>
        <td><?php echo htmlspecialchars($u['username']); ?></td>
        <td>
            <span class="badge badge-ok">
                <?php echo htmlspecialchars($u['role']); ?>
            </span>
        </td>
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
    </tr>
    <?php endforeach; ?>
</table>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
<?php 
require_once __DIR__ . '/../../helpers/Auth.php';
Auth::checkRole(array('admin'));

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/navbar.php';
?>

<h2>Quản Lý Tài Khoản Hệ Thống (View Admin)</h2>

<table border="1" cellpadding="10" cellspacing="0" style="width: 100%; border-collapse: collapse; background: white;">
    <tr style="background-color: #004b93; color: white;">
        <th>Mã User</th>
        <th>Tên Đăng Nhập</th>
        <th>Vai Trò (Role)</th>
        <th>Liên Kết Vai Trò</th>
    </tr>
    <?php foreach ($users as $u): ?>
    <tr>
        <td>#<?php echo $u['user_id']; ?></td>
        <td><strong><?php echo htmlspecialchars($u['username']); ?></strong></td>
        <td><span style="text-transform: uppercase; font-weight: bold; color: #004b93;"><?php echo htmlspecialchars($u['role']); ?></span></td>
        <td>
            <?php 
                if ($u['doctor_id']) echo "Bác sĩ ID: #" . $u['doctor_id'];
                elseif ($u['nurse_id']) echo "Y tá ID: #" . $u['nurse_id'];
                elseif ($u['receptionist_id']) echo "Lễ tân ID: #" . $u['receptionist_id'];
                elseif ($u['patient_id']) echo "Bệnh nhân ID: #" . $u['patient_id'];
                else echo "Quản trị viên hệ thống";
            ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
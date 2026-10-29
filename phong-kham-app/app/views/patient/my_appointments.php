<?php 
require_once __DIR__ . '/../../helpers/Auth.php';
// Kiểm tra quyền: Chỉ cho phép Bệnh nhân (hoặc Admin) truy cập
Auth::checkRole(array('patient', 'admin')); 

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/navbar.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2>Lịch Hẹn Khám Của Tôi</h2>
    <a href="/index.php?action=booking" class="btn-booking" style="text-decoration: none;">+ Đặt Lịch Khám Mới</a>
</div>

<?php if (empty($my_appointments)): ?>
    <div style="background: white; padding: 30px; text-align: center; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <p style="color: #666; font-size: 16px;">Bạn chưa đăng ký lịch khám nào.</p>
        <a href="/index.php?action=booking" style="color: #004b93; font-weight: bold;">Nhấp vào đây để đặt lịch khám ngay.</a>
    </div>
<?php else: ?>
    <table border="1" cellpadding="12" cellspacing="0" style="width: 100%; border-collapse: collapse; background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <thead>
            <tr style="background-color: #004b93; color: white; text-align: left;">
                <th>Mã Đặt Lịch</th>
                <th>Bác Sĩ Bệnh Viện</th>
                <th>Chuyên Khoa</th>
                <th>Ngày & Giờ Khám</th>
                <th>Lý Do Khám / Triệu Chứng</th>
                <th>Trạng Thái Duyệt</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($my_appointments as $app): ?>
                <tr>
                    <td><strong>#<?php echo $app['appointment_id']; ?></strong></td>
                    <td>BS. <?php echo htmlspecialchars($app['doctor_name']); ?></td>
                    <td><?php echo htmlspecialchars($app['specialty']); ?></td>
                    <td><?php echo date('H:i - d/m/Y', strtotime($app['appointment_date'])); ?></td>
                    <td><?php echo htmlspecialchars($app['note']); ?></td>
                    <td>
                        <?php 
                        // Hiển thị màu sắc tương ứng cho từng trạng thái
                        $status = $app['status'];
                        $badge_style = "padding: 5px 10px; border-radius: 4px; font-weight: bold; font-size: 12px; display: inline-block;";
                        
                        if ($status === 'Pending') {
                            echo "<span style='{$badge_style} background: #fff3cd; color: #856404;'>Chờ Lễ Tân Duyệt</span>";
                        } elseif ($status === 'Scheduled') {
                            echo "<span style='{$badge_style} background: #d4edda; color: #155724;'>Đã Lên Lịch Khám</span>";
                        } elseif ($status === 'Completed') {
                            echo "<span style='{$badge_style} background: #cce5ff; color: #004085;'>Đã Khám Xong</span>";
                        } elseif ($status === 'Cancelled') {
                            echo "<span style='{$badge_style} background: #f8d7da; color: #721c24;'>Đã Hủy</span>";
                        } else {
                            echo "<span>{$status}</span>";
                        }
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
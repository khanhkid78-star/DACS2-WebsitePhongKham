<?php
class Auth {
    public static function startSession() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function isLoggedIn() {
        self::startSession();
        return isset($_SESSION['user_id']);
    }

    public static function getRole() {
        self::startSession();
        return isset($_SESSION['role']) ? $_SESSION['role'] : null;
    }

    // Kiểm tra nếu người dùng có một trong các quyên cho phép hay không
    public static function checkRole($allowed_roles) {
        self::startSession();
        if (!self::isLoggedIn()) {
            header("Location: /index.php?action=login");
            exit();
        }

        $user_role = self::getRole();
        $has_permission = false;

        foreach ($allowed_roles as $role) {
            if ($role === $user_role) {
                $has_permission = true;
                break;
            }
        }

        if (!$has_permission) {
            echo "<h3 style='color:red; text-align:center;'>Bạn không có quyền truy cập vào chức năng này!</h3>";
            exit();
        }
    }

    public static function dashboardUrl($role) {
        $map = array(
            'patient' => '/index.php?action=my_appointments',
            'doctor' => '/index.php?action=doctor_records',
            'nurse' => '/index.php?action=nurse_medicines',
            'receptionist' => '/index.php?action=receptionist_appointments',
            'admin' => '/index.php?action=admin_users',
        );
        return isset($map[$role]) ? $map[$role] : '/index.php';
    }

    public static function roleLabel($role) {
        $map = array(
            'admin' => 'Quản trị',
            'doctor' => 'Bác sĩ',
            'nurse' => 'Y tá',
            'receptionist' => 'Lễ tân',
            'patient' => 'Bệnh nhân',
        );
        return isset($map[$role]) ? $map[$role] : 'Khách';
    }
}
?>
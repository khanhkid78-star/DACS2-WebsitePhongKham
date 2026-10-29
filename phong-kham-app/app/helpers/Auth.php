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
}
?>
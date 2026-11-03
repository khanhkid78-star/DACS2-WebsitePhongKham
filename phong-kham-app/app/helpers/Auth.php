<?php
class Auth {
    public static function startSession() {
        if (session_status() == PHP_SESSION_NONE) {
            $lifetime = 60 * 60 * 24 * 30;
            ini_set('session.gc_maxlifetime', (string) $lifetime);
            $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
            session_set_cookie_params(array(
                'lifetime' => $lifetime,
                'path' => '/',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ));
            session_start();
        }
        self::restoreFromCookie();
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
            $next = isset($_GET['action']) ? $_GET['action'] : '';
            $doctorId = isset($_GET['doctor_id']) ? (int) $_GET['doctor_id'] : 0;
            $url = '/index.php?action=login';
            if ($next !== '') {
                $url .= '&next=' . urlencode($next);
            }
            if ($doctorId > 0) {
                $url .= '&doctor_id=' . $doctorId;
            }
            header('Location: ' . $url);
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

    const COOKIE = 'pk_auth';
    const DAYS = 30;
    const SECRET = 'hungdinh-phongkham-cookie-key-2026';

    public static function remember() {
        self::startSession();
        if (empty($_SESSION['user_id'])) {
            return;
        }
        $payload = array(
            'user_id' => (int) $_SESSION['user_id'],
            'username' => (string) $_SESSION['username'],
            'role' => (string) $_SESSION['role'],
            'doctor_id' => $_SESSION['doctor_id'] ?? null,
            'nurse_id' => $_SESSION['nurse_id'] ?? null,
            'receptionist_id' => $_SESSION['receptionist_id'] ?? null,
            'patient_id' => $_SESSION['patient_id'] ?? null,
            'exp' => time() + (60 * 60 * 24 * self::DAYS),
        );
        $body = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
        $sig = hash_hmac('sha256', $body, self::SECRET);
        setcookie(self::COOKIE, $body . '.' . $sig, array(
            'expires' => $payload['exp'],
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ));
    }

    public static function forget() {
        setcookie(self::COOKIE, '', time() - 3600, '/');
        setcookie(session_name(), '', time() - 3600, '/');
    }

    private static function restoreFromCookie() {
        if (!empty($_SESSION['user_id']) || empty($_COOKIE[self::COOKIE])) {
            return;
        }
        $parts = explode('.', $_COOKIE[self::COOKIE], 2);
        if (count($parts) !== 2) {
            return;
        }
        if (!hash_equals(hash_hmac('sha256', $parts[0], self::SECRET), $parts[1])) {
            return;
        }
        $data = json_decode(base64_decode(strtr($parts[0], '-_', '+/')), true);
        if (!is_array($data) || empty($data['user_id']) || $data['exp'] < time()) {
            return;
        }
        $_SESSION['user_id'] = $data['user_id'];
        $_SESSION['username'] = $data['username'];
        $_SESSION['role'] = $data['role'];
        $_SESSION['doctor_id'] = $data['doctor_id'];
        $_SESSION['nurse_id'] = $data['nurse_id'];
        $_SESSION['receptionist_id'] = $data['receptionist_id'];
        $_SESSION['patient_id'] = $data['patient_id'];
    }
}
?>
<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/Auth.php';

class AuthController {
    public function showLogin() {
        require_once __DIR__ . '/../views/auth/login.php';
    }

    private function afterLoginUrl($role, $next, $doctor_id) {
        if ($role === 'patient' && $next === 'booking') {
            $url = '/index.php?action=booking';
            if ($doctor_id > 0) {
                $url .= '&doctor_id=' . $doctor_id;
            }
            return $url;
        }
        return Auth::dashboardUrl($role);
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $database = new Database();
            $db = $database->getConnection();
            $userModel = new User($db);

            $username = $_POST['username'];
            $password = $_POST['password'];
            $next = isset($_POST['next']) ? $_POST['next'] : '';
            $doctor_id = isset($_POST['doctor_id']) ? (int) $_POST['doctor_id'] : 0;

            $user = $userModel->findByUsername($username);

            if ($user) {
                // Kiểm tra mật khẩu (hỗ trợ cả mật khẩu đã băm bCrypt và mã chưa băm)
                $hash = (string) ($user['password'] ?? '');
                $passwordMatch = ($hash !== '' && password_verify($password, $hash)) || $password === $hash;

                if ($passwordMatch) {
                    Auth::startSession();
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['doctor_id'] = $user['doctor_id'];
                    $_SESSION['nurse_id'] = $user['nurse_id'];
                    $_SESSION['receptionist_id'] = $user['receptionist_id'];
                    $_SESSION['patient_id'] = $user['patient_id'];
                    
                    Auth::remember();
                    $target = $this->afterLoginUrl($user['role'], $next, $doctor_id);
                    $tab = Auth::currentTab();
                    if ($tab !== '') {
                        $target .= (strpos($target, '?') === false ? '?' : '&') . 'tab=' . urlencode($tab);
                    }

                    if (!empty($_POST['popup'])) {
                        header('Content-Type: application/json; charset=UTF-8');
                        echo json_encode(array('ok' => true, 'url' => $target), JSON_UNESCAPED_UNICODE);
                        exit();
                    }

                    header('Location: ' . $target);
                    exit();
                }
            }

            if (!empty($_POST['popup'])) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(array('ok' => false, 'error' => 'Sai tên đăng nhập hoặc mật khẩu.'), JSON_UNESCAPED_UNICODE);
                exit();
            }
            $error = 'Sai tên đăng nhập hoặc mật khẩu.';
            require_once __DIR__ . '/../views/auth/login.php';
            return;
        }
    }

    public function showRegister() {
        Auth::startSession();
        if (Auth::isLoggedIn()) {
            header('Location: ' . Auth::dashboardUrl(Auth::getRole()));
            exit();
        }
        require_once __DIR__ . '/../views/auth/register.php';
    }

    public function register() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /index.php?action=register');
            exit();
        }

        $data = array(
            'username' => trim($_POST['username'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'full_name' => trim($_POST['full_name'] ?? ''),
            'date_of_birth' => trim($_POST['date_of_birth'] ?? ''),
            'gender' => trim($_POST['gender'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'cccd' => trim($_POST['cccd'] ?? ''),
            'bhyt' => trim($_POST['bhyt'] ?? ''),
        );

        $error = $this->validateRegister($data);
        if ($error) {
            if (!empty($_POST['popup'])) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(array('ok' => false, 'error' => $error), JSON_UNESCAPED_UNICODE);
                exit();
            }
            require_once __DIR__ . '/../views/auth/register.php';
            return;
        }

        $database = new Database();
        $db = $database->getConnection();
        $userModel = new User($db);

        try {
            $userModel->registerPatient($data);
        } catch (PDOException $e) {
            $error = 'Không tạo được tài khoản. Tên đăng nhập, SĐT, email hoặc CCCD có thể đã tồn tại.';
            if (!empty($_POST['popup'])) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(array('ok' => false, 'error' => $error), JSON_UNESCAPED_UNICODE);
                exit();
            }
            require_once __DIR__ . '/../views/auth/register.php';
            return;
        }

        if (!empty($_POST['popup'])) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(array(
                'ok' => true,
                'panel' => 'login',
                'message' => 'Tạo tài khoản xong. Hãy đăng nhập.',
            ), JSON_UNESCAPED_UNICODE);
            exit();
        }
        $registered = true;
        $next = 'booking';
        $doctor_id = 0;
        require_once __DIR__ . '/../views/auth/login.php';
    }

    private function validateRegister($data) {
        foreach ($data as $value) {
            if ($value === '') {
                return 'Điền đủ mọi ô. Ngày sinh, giới tính, CCCD và BHYT là bắt buộc.';
            }
        }
        if (!preg_match('/^\d{10}$/', $data['phone'])) {
            return 'Số điện thoại phải đúng 10 chữ số.';
        }
        if (!preg_match('/^\d{12}$/', $data['cccd'])) {
            return 'CCCD phải đúng 12 chữ số.';
        }
        if (!preg_match('/^\d{10,15}$/', $data['bhyt'])) {
            return 'Số BHYT phải từ 10 đến 15 chữ số.';
        }
        if (!in_array($data['gender'], array('Nam', 'Nữ', 'Khác'), true)) {
            return 'Giới tính không hợp lệ.';
        }
        if (strlen($data['password']) < 5) {
            return 'Mật khẩu tối thiểu 5 ký tự.';
        }
        return '';
    }

    public function logout() {
        Auth::startSession();
        $tab = Auth::currentTab();
        Auth::forget();
        $_SESSION = array();
        if (session_status() === PHP_SESSION_ACTIVE) {
            $params = session_get_cookie_params();
            $path = !empty($params['path']) ? $params['path'] : '/';
            $past = gmdate('D, d M Y H:i:s', time() - 42000) . ' GMT';
            $name = session_name();
            header('Set-Cookie: ' . $name . '=; Expires=' . $past . '; Max-Age=0; Path=' . $path . '; HttpOnly; SameSite=Lax', false);
            header('Set-Cookie: ' . $name . '=; Expires=' . $past . '; Max-Age=0; Path=' . $path . '; Secure; HttpOnly; SameSite=Lax', false);
            session_destroy();
        }
        $url = '/index.php?out=1';
        if ($tab !== '') {
            $url .= '&tab=' . urlencode($tab);
        }
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Location: ' . $url);
        exit();
    }


    private function requireUser() {
        Auth::startSession();
        if (!Auth::isLoggedIn()) {
            header('Location: /index.php?action=login');
            exit();
        }
    }

    public function showAccount() {
        $this->requireUser();
        if (Auth::getRole() === 'admin') {
            header('Location: /index.php?action=admin_users');
            exit();
        }
        $bundle = (new User((new Database())->getConnection()))->accountBundle((int) $_SESSION['user_id']);
        if (!$bundle) {
            Auth::flash('Không thấy tài khoản.');
            header('Location: /index.php');
            exit();
        }
        $accountUser = $bundle['user'];
        $accountKind = $bundle['kind'];
        $profile = $bundle['profile'] ? $bundle['profile'] : array();
        require_once __DIR__ . '/../views/account/profile.php';
    }

    public function saveAccount() {
        $this->requireUser();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $back = Auth::getRole() === 'admin' ? 'admin_users' : 'account';
            header('Location: /index.php?action=' . $back);
            exit();
        }
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $message = (new User((new Database())->getConnection()))->updateAccount(
            (int) $_SESSION['user_id'],
            $username,
            $password,
            $_POST
        );
        if ($message === '') {
            $_SESSION['username'] = $username;
            Auth::remember();
            Auth::flash('Đã cập nhật tài khoản.', true);
        } else {
            Auth::flash($message);
        }
        header('Location: /index.php?action=account');
        exit();
    }

    public function deleteOwnAccount() {
        $this->requireUser();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /index.php?action=account');
            exit();
        }
        $message = (new User((new Database())->getConnection()))->deleteSelf((int) $_SESSION['user_id']);
        if ($message !== '') {
            Auth::flash($message);
            header('Location: /index.php?action=account');
            exit();
        }
        $_SESSION = array();
        Auth::forget();
        session_destroy();
        header('Location: /index.php?deleted=1');
        exit();
    }

        public function notifications() {
        Auth::startSession();
        header('Content-Type: application/json; charset=UTF-8');
        if (!Auth::isLoggedIn()) {
            echo json_encode(array('count' => 0, 'items' => array()));
            exit();
        }
        try {
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("SELECT notification_id, title, body, link, is_read, created_at FROM notifications WHERE user_id = :id ORDER BY created_at DESC LIMIT 12");
            $stmt->execute(array(':id' => (int) $_SESSION['user_id']));
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $count = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :id AND is_read = false");
            $count->execute(array(':id' => (int) $_SESSION['user_id']));
            echo json_encode(array('count' => (int) $count->fetchColumn(), 'items' => $items), JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(array('count' => 0, 'items' => array()));
        }
        exit();
    }

    public function readNotifications() {
        Auth::startSession();
        if (Auth::isLoggedIn()) {
            try {
                $stmt = (new Database())->getConnection()->prepare("UPDATE notifications SET is_read = true WHERE user_id = :id");
                $stmt->execute(array(':id' => (int) $_SESSION['user_id']));
            } catch (Exception $e) {
            }
        }
        header('Content-Type: application/json; charset=UTF-8');
        echo '{}';
        exit();
    }
}
?>
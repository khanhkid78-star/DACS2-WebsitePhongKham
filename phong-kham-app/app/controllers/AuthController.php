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

            $user = $userModel->findByUsername($username);

            if ($user) {
                // Kiểm tra mật khẩu (hỗ trợ cả mật khẩu đã băm bCrypt và mã chưa băm)
                $passwordMatch = password_verify($password, $user['password']) || ($password === $user['password']);

                if ($passwordMatch) {
                    Auth::startSession();
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['doctor_id'] = $user['doctor_id'];
                    $_SESSION['nurse_id'] = $user['nurse_id'];
                    $_SESSION['receptionist_id'] = $user['receptionist_id'];
                    $_SESSION['patient_id'] = $user['patient_id'];

                    header('Location: ' . $this->afterLoginUrl($user['role'], $next, $doctor_id));
                    exit();
                }
            }

            $error = "Tài khoản hoặc mật khẩu không chính xác!";
            require_once __DIR__ . '/../views/auth/login.php';
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
            require_once __DIR__ . '/../views/auth/register.php';
            return;
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
        session_destroy();
        header("Location: /index.php");
        exit();
    }
}
?>
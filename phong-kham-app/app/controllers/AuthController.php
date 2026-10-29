<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/Auth.php';

class AuthController {
    public function showLogin() {
        require_once __DIR__ . '/../views/auth/login.php';
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

                    header("Location: /index.php");
                    exit();
                }
            }

            $error = "Tài khoản hoặc mật khẩu không chính xác!";
            require_once __DIR__ . '/../views/auth/login.php';
        }
    }

    public function logout() {
        Auth::startSession();
        session_destroy();
        header("Location: /index.php");
        exit();
    }
}
?>
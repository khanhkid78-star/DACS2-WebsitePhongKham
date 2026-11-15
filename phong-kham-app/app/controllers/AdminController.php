<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/Auth.php';

class AdminController {
    private function model() {
        $db = (new Database())->getConnection();
        return array('db' => $db, 'users' => new User($db));
    }

    public function listUsers() {
        Auth::checkRole(array('admin'));
        $bag = $this->model();
        $users = $bag['db']->query(
            "SELECT user_id, username, role, doctor_id, nurse_id, receptionist_id, patient_id
             FROM users ORDER BY user_id ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
        $openDoctors = $bag['users']->unlinked('doctors', 'doctor_id');
        $openNurses = $bag['users']->unlinked('nurses', 'nurse_id');
        $openReceptionists = $bag['users']->unlinked('receptionists', 'receptionist_id');
        require_once __DIR__ . '/../views/admin/users.php';
    }

    public function saveUser() {
        Auth::checkRole(array('admin'));
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /index.php?action=admin_users');
            exit();
        }
        $userModel = $this->model()['users'];
        $form = $_POST['form'] ?? '';

        if ($form === 'reset') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            if ($username === '' || strlen($password) < 5) {
                Auth::flash('Nhập tên đăng nhập và mật khẩu tối thiểu 5 ký tự.');
            } elseif ($userModel->resetPassword($username, $password)) {
                Auth::flash('Đã đặt lại mật khẩu.', true);
            } else {
                Auth::flash('Không thấy tên đăng nhập này.');
            }
        } elseif ($form === 'create') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? '';
            $allowed = array('admin', 'doctor', 'nurse', 'receptionist');
            if ($role === 'doctor') {
                $linkId = (int) ($_POST['doctor_id'] ?? 0);
            } elseif ($role === 'nurse') {
                $linkId = (int) ($_POST['nurse_id'] ?? 0);
            } elseif ($role === 'receptionist') {
                $linkId = (int) ($_POST['receptionist_id'] ?? 0);
            } else {
                $linkId = 0;
            }
            if ($username === '' || strlen($password) < 5 || !in_array($role, $allowed, true)) {
                Auth::flash('Điền tên đăng nhập, mật khẩu và vai trò.');
            } else {
                $message = $userModel->createStaff($username, $password, $role, $linkId);
                Auth::flash($message === '' ? 'Đã tạo tài khoản.' : $message, $message === '');
            }
        }

        header('Location: /index.php?action=admin_users');
        exit();
    }

    public function deleteUser() {
        Auth::checkRole(array('admin'));
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /index.php?action=admin_users');
            exit();
        }
        $id = (int) ($_POST['user_id'] ?? 0);
        if ($id < 1 || $id === (int) $_SESSION['user_id']) {
            Auth::flash('Không xóa chính tài khoản đang đăng nhập.');
            header('Location: /index.php?action=admin_users');
            exit();
        }
        $message = $this->model()['users']->deleteById($id);
        Auth::flash($message === '' ? 'Đã xóa tài khoản.' : $message, $message === '');
        header('Location: /index.php?action=admin_users');
        exit();
    }
}
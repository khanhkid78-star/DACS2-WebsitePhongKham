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

        public function showStats() {
        Auth::checkRole(array('admin'));
        $staff = $this->model()['db']->query(
            "SELECT
                (SELECT COUNT(*) FROM doctors) AS doctors,
                (SELECT COUNT(*) FROM nurses) AS nurses,
                (SELECT COUNT(*) FROM receptionists) AS receptionists,
                (SELECT COUNT(*) FROM patients) AS patients"
        )->fetch(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../views/admin/stats.php';
    }

    public function statsData() {
        Auth::checkRole(array('admin'));
        $grain = (isset($_GET['grain']) && $_GET['grain'] === 'quarter') ? 'quarter' : 'month';
        $trunc = $grain === 'quarter' ? 'quarter' : 'month';
        $step = $grain === 'quarter' ? '3 months' : '1 month';
        $back = $grain === 'quarter' ? '21 months' : '11 months';
        $label = $grain === 'quarter'
            ? "('Q' || EXTRACT(QUARTER FROM b.bucket)::int || '/' || EXTRACT(YEAR FROM b.bucket)::int)"
            : "to_char(b.bucket, 'MM/YYYY')";
        $sql = "WITH buckets AS (
                    SELECT generate_series(
                        date_trunc('$trunc', CURRENT_DATE) - interval '$back',
                        date_trunc('$trunc', CURRENT_DATE),
                        interval '$step'
                    ) AS bucket
                ),
                rev AS (
                    SELECT date_trunc('$trunc', created_at) AS bucket, SUM(total_amount) AS value
                    FROM invoices
                    WHERE status = 'Paid'
                      AND created_at >= date_trunc('$trunc', CURRENT_DATE) - interval '$back'
                    GROUP BY 1
                ),
                pat AS (
                    SELECT date_trunc('$trunc', created_at) AS bucket, COUNT(*) AS value
                    FROM patients
                    WHERE created_at >= date_trunc('$trunc', CURRENT_DATE) - interval '$back'
                    GROUP BY 1
                ),
                rec AS (
                    SELECT date_trunc('$trunc', visit_date) AS bucket, COUNT(*) AS value
                    FROM medical_records
                    WHERE visit_date >= date_trunc('$trunc', CURRENT_DATE) - interval '$back'
                    GROUP BY 1
                ),
                vis AS (
                    SELECT date_trunc('$trunc', appointment_date) AS bucket, COUNT(*) AS value
                    FROM appointments
                    WHERE status <> 'Cancelled'
                      AND appointment_date >= date_trunc('$trunc', CURRENT_DATE) - interval '$back'
                    GROUP BY 1
                )
                SELECT $label AS label,
                       COALESCE(rev.value, 0) AS revenue,
                       COALESCE(pat.value, 0) AS patients,
                       COALESCE(rec.value, 0) AS records,
                       COALESCE(vis.value, 0) AS visits
                FROM buckets b
                LEFT JOIN rev ON rev.bucket = b.bucket
                LEFT JOIN pat ON pat.bucket = b.bucket
                LEFT JOIN rec ON rec.bucket = b.bucket
                LEFT JOIN vis ON vis.bucket = b.bucket
                ORDER BY b.bucket";
        $rows = $this->model()['db']->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(array(
            'labels' => array_column($rows, 'label'),
            'revenue' => array_map('floatval', array_column($rows, 'revenue')),
            'patients' => array_map('intval', array_column($rows, 'patients')),
            'records' => array_map('intval', array_column($rows, 'records')),
            'visits' => array_map('intval', array_column($rows, 'visits')),
        ));
        exit();
    }
}
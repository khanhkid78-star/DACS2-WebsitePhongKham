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
        $db = $this->model()['db'];
        $revenue = $this->trend($db, $grain, "SELECT created_at, total_amount FROM invoices WHERE status = 'Paid'", 'created_at', 'SUM(src.total_amount)');
        $patients = $this->trend($db, $grain, "SELECT created_at FROM patients", 'created_at', 'COUNT(src.created_at)');
        $records = $this->trend($db, $grain, "SELECT visit_date AS created_at FROM medical_records", 'created_at', 'COUNT(src.created_at)');
        $visits = $this->trend($db, $grain, "SELECT appointment_date AS created_at FROM appointments WHERE status <> 'Cancelled'", 'created_at', 'COUNT(src.created_at)');
        $labels = array();
        foreach ($revenue as $row) {
            $labels[] = $row['label'];
        }
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(array(
            'labels' => $labels,
            'revenue' => array_map('floatval', array_column($revenue, 'value')),
            'patients' => array_map('intval', array_column($patients, 'value')),
            'records' => array_map('intval', array_column($records, 'value')),
            'visits' => array_map('intval', array_column($visits, 'value')),
        ));
        exit();
    }

    private function trend($db, $grain, $sourceSql, $dateColumn, $aggregate) {
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
                )
                SELECT $label AS label, COALESCE($aggregate, 0) AS value
                FROM buckets b
                LEFT JOIN ($sourceSql) src ON date_trunc('$trunc', src.$dateColumn) = b.bucket
                GROUP BY b.bucket
                ORDER BY b.bucket";
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Appointment.php';
require_once __DIR__ . '/../helpers/Auth.php';

class BookingController {
    private $db;
    private $appointmentModel;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->appointmentModel = new Appointment($this->db);
    }

    public function showForm() {
    Auth::checkRole(array('patient'));
    if (empty($_SESSION['patient_id'])) {
        echo 'Tài khoản này chưa gắn hồ sơ bệnh nhân, không đặt lịch được.';
        return;
    }

    $stmt = $this->db->query(
        "SELECT doctor_id, full_name, specialty FROM doctors ORDER BY full_name"
    );
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    require_once __DIR__ . '/../views/patient/booking_form.php';
    }

    public function submit() {
        Auth::checkRole(array('patient'));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /index.php?action=booking');
            exit();
        }

        $patient_id = (int) ($_SESSION['patient_id'] ?? 0);
        $doctor_id = (int) ($_POST['doctor_id'] ?? 0);
        $appointment_date = trim($_POST['appointment_date'] ?? '');
        $note = trim($_POST['note'] ?? '');

        if ($patient_id < 1) {
            echo 'Tài khoản chưa gắn hồ sơ bệnh nhân.';
            return;
        }
        if ($doctor_id < 1 || $appointment_date === '' || $note === '') {
            echo 'Thiếu bác sĩ, ngày giờ hoặc triệu chứng.';
            return;
        }

        if ($this->appointmentModel->create($patient_id, $doctor_id, $appointment_date, $note)) {

            $who = $this->db->prepare("SELECT p.full_name AS patient_name, d.full_name AS doctor_name FROM patients p, doctors d WHERE p.patient_id = :p AND d.doctor_id = :d");
            $who->execute(array(':p' => $patient_id, ':d' => $doctor_id));
            $info = $who->fetch(PDO::FETCH_ASSOC);
            require_once __DIR__ . '/../helpers/Notify.php';
            $text = ($info['patient_name'] ?? 'Bệnh nhân') . ' đặt lịch với BS. ' . ($info['doctor_name'] ?? '') . ' lúc ' . date('H:i d/m/Y', strtotime($appointment_date)) . '. ' . $note;
            Notify::send($this->db, 'receptionists', 0, 'Có lịch hẹn mới', $text, '/index.php?action=receptionist_appointments');
            Notify::send($this->db, 'doctor', $doctor_id, 'Có lịch hẹn mới', $text, '/index.php');

            Auth::flash('Đã gửi yêu cầu. Lễ tân sẽ gọi xác nhận.', true);
            header('Location: /index.php?action=my_appointments');
            exit();
        }

        echo 'Lỗi khi lưu lịch hẹn.';
    }

    private function findOrCreatePatient($fullName, $phone) {
        if ($fullName === '' || $phone === '') {
            return 0;
        }

        $find = $this->db->prepare(
            "SELECT patient_id FROM patients WHERE phone = ? LIMIT 1"
        );
        $find->execute(array($phone));
        $row = $find->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return (int) $row['patient_id'];
        }

        $insert = $this->db->prepare(
            "INSERT INTO patients (full_name, phone)
            VALUES (?, ?)
            RETURNING patient_id"
        );
        $insert->execute(array($fullName, $phone));
        return (int) $insert->fetchColumn();
    }

    public function myAppointments() {
        Auth::checkRole(array('patient', 'admin'));
        require_once __DIR__ . '/../models/MedicalRecord.php';
        require_once __DIR__ . '/../models/Invoice.php';

        $patient_id = !empty($_SESSION['patient_id']) ? (int) $_SESSION['patient_id'] : 0;
        $my_appointments = $patient_id
            ? $this->appointmentModel->getByPatientId($patient_id)
            : array();
        $my_records = $patient_id
            ? (new MedicalRecord($this->db))->getList(null, $patient_id)
            : array();
        $my_invoices = $patient_id
            ? (new Invoice($this->db))->getByPatientId($patient_id)
            : array();
        require_once __DIR__ . '/../views/patient/my_appointments.php';
    }

    public function manageAppointments() {
        Auth::checkRole(array('receptionist', 'admin'));
        $doctors = $this->db->query("SELECT doctor_id, full_name FROM doctors ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
        $appointments = $this->appointmentModel->search('', '', 0, null);
        require_once __DIR__ . '/../views/receptionist/manage_appointments.php';
    }

    public function updateStatus() {
        Auth::checkRole(array('receptionist', 'admin'));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /index.php?action=receptionist_appointments');
            exit();
        }

        $id = (int) ($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $allowed = array('Scheduled', 'Cancelled', 'Completed');

        if ($id > 0 && in_array($status, $allowed, true)) {
            $this->appointmentModel->updateStatus($id, $status);

            $info = $this->db->prepare("SELECT a.appointment_date, a.patient_id, a.doctor_id, d.full_name AS doctor_name FROM appointments a LEFT JOIN doctors d ON d.doctor_id = a.doctor_id WHERE a.appointment_id = :id");
            $info->execute(array(':id' => $id));
            $row = $info->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                require_once __DIR__ . '/../helpers/Notify.php';
                $map = array('Scheduled' => 'đã được duyệt', 'Cancelled' => 'đã bị hủy', 'Completed' => 'đã khám xong');
                $text = 'Lịch với BS. ' . $row['doctor_name'] . ' lúc ' . date('H:i d/m/Y', strtotime($row['appointment_date'])) . ' ' . $map[$status] . '.';
                Notify::send($this->db, 'patient', (int) $row['patient_id'], 'Cập nhật lịch hẹn', $text, '/index.php?action=my_appointments');
                Notify::send($this->db, 'doctor', (int) $row['doctor_id'], 'Cập nhật lịch hẹn', $text, '/index.php');
            }

            Auth::flash('Đã cập nhật trạng thái lịch hẹn.', true);
        }

        header('Location: /index.php?action=receptionist_appointments');
        exit();
    }


        public function cancel() {
        Auth::checkRole(array('patient'));
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /index.php?action=my_appointments');
            exit();
        }
        $id = (int) ($_POST['appointment_id'] ?? 0);
        $patient_id = (int) ($_SESSION['patient_id'] ?? 0);
        if ($this->appointmentModel->cancelOwn($id, $patient_id)) {
            Auth::flash('Đã hủy lịch đang chờ duyệt.', true);
        } else {
            Auth::flash('Không hủy được. Chỉ hủy lịch đang chờ duyệt của bạn.');
        }
        header('Location: /index.php?action=my_appointments');
        exit();
    }


        public function appointmentRows() {
        Auth::checkRole(array('receptionist', 'admin'));
        $appointments = $this->appointmentModel->search(
            trim($_GET['q'] ?? ''),
            $_GET['status'] ?? '',
            (int) ($_GET['doctor_id'] ?? 0),
            null
        );
        require __DIR__ . '/../views/receptionist/_appointment_rows.php';
        exit();
    }

    public function updateAppointment() {
        Auth::checkRole(array('receptionist', 'admin'));
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /index.php?action=receptionist_appointments');
            exit();
        }
        $id = (int) ($_POST['id'] ?? 0);
        $doctorId = (int) ($_POST['doctor_id'] ?? 0);
        $when = trim($_POST['appointment_date'] ?? '');
        $note = trim($_POST['note'] ?? '');
        $status = $_POST['status'] ?? '';
        $allowed = array('Pending', 'Scheduled', 'Completed', 'Cancelled');
        $stamp = strtotime($when);
        $exists = $this->db->prepare("SELECT 1 FROM doctors WHERE doctor_id = :id");
        $exists->execute(array(':id' => $doctorId));
        if ($id < 1 || !$exists->fetchColumn() || $stamp === false || $note === '' || !in_array($status, $allowed, true)) {
            Auth::flash('Dòng lịch chưa đủ hoặc không hợp lệ.');
            header('Location: /index.php?action=receptionist_appointments');
            exit();
        }
        $this->appointmentModel->updateRow($id, $doctorId, date('Y-m-d H:i:s', $stamp), $note, $status);
        require_once __DIR__ . '/../helpers/Notify.php';
        $patient = $this->db->prepare("SELECT patient_id FROM appointments WHERE appointment_id = :id");
        $patient->execute(array(':id' => $id));
        $patientId = (int) $patient->fetchColumn();
        $text = 'Lịch #' . $id . ' đã được sửa, trạng thái ' . $status . '.';
        Notify::send($this->db, 'patient', $patientId, 'Lịch hẹn được sửa', $text, '/index.php?action=my_appointments');
        Notify::send($this->db, 'doctor', $doctorId, 'Lịch hẹn được sửa', $text, '/index.php');
        Auth::flash('Đã sửa lịch hẹn.', true);
        header('Location: /index.php?action=receptionist_appointments');
        exit();
    }
}
?>
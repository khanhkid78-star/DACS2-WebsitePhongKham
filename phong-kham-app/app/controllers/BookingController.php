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
        $appointments = $this->appointmentModel->getAllWithDetails();
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
}
?>
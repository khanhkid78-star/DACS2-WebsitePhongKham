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
        $stmt = $this->db->query(
            "SELECT doctor_id, full_name, specialty FROM doctors"
        );
        $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../views/patient/booking_form.php';
    }

    public function submit() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /index.php?action=booking');
            exit();
        }

        Auth::startSession();
        $doctor_id = (int) ($_POST['doctor_id'] ?? 0);
        $appointment_date = trim($_POST['appointment_date'] ?? '');
        $note = trim($_POST['note'] ?? '');

        if ($doctor_id < 1 || $appointment_date === '' || $note === '') {
            echo 'Thiếu bác sĩ, ngày giờ hoặc triệu chứng.';
            return;
        }

        if (!empty($_SESSION['patient_id'])) {
            $patient_id = (int) $_SESSION['patient_id'];
        } else {
            $patient_id = $this->findOrCreatePatient(
                trim($_POST['full_name'] ?? ''),
                trim($_POST['phone'] ?? '')
            );
            if (!$patient_id) {
                echo 'Khách chưa đăng nhập phải điền họ tên và số điện thoại.';
                return;
            }
        }

        if ($this->appointmentModel->create(
            $patient_id,
            $doctor_id,
            $appointment_date,
            $note
        )) {
            if (Auth::getRole() === 'patient') {
                header('Location: /index.php?action=my_appointments');
            } else {
                header('Location: /index.php?action=home&booked=1');
            }
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
        $patient_id = !empty($_SESSION['patient_id'])
            ? (int) $_SESSION['patient_id']
            : 0;
        $my_appointments = $patient_id
            ? $this->appointmentModel->getByPatientId($patient_id)
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
        }

        header('Location: /index.php?action=receptionist_appointments');
        exit();
    }
}
?>
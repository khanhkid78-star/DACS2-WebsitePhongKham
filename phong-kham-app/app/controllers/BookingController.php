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

    // Trang form đặt lịch
    public function showForm() {
        $stmt = $this->db->query("SELECT doctor_id, full_name, specialty FROM doctors");
        $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../views/patient/booking_form.php';
    }

    // Xử lý gửi form
    public function submit() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Auth::startSession();
            $patient_id = isset($_SESSION['patient_id']) ? $_SESSION['patient_id'] : 1;

            $doctor_id = $_POST['doctor_id'];
            $appointment_date = $_POST['appointment_date'];
            $note = $_POST['note'];

            if ($this->appointmentModel->create($patient_id, $doctor_id, $appointment_date, $note)) {
                echo "<script>alert('Đặt lịch hẹn thành công!'); window.location.href='/index.php?action=my_appointments';</script>";
            } else {
                echo "Lỗi khi lưu lịch hẹn.";
            }
        }
    }

    // Bệnh nhân xem lịch hẹn cá nhân
    public function myAppointments() {
        Auth::checkRole(array('patient', 'admin'));
        $patient_id = isset($_SESSION['patient_id']) ? $_SESSION['patient_id'] : 1;

        $my_appointments = $this->appointmentModel->getByPatientId($patient_id);
        require_once __DIR__ . '/../views/patient/my_appointments.php';
    }

    // Lễ tân xem tất cả lịch hẹn
    public function manageAppointments() {
        Auth::checkRole(array('receptionist', 'admin'));
        $appointments = $this->appointmentModel->getAllWithDetails();
        require_once __DIR__ . '/../views/receptionist/manage_appointments.php';
    }

    // Lễ tân duyệt / hủy lịch hẹn
    public function updateStatus() {
        Auth::checkRole(array('receptionist', 'admin'));
        $id = $_GET['id'];
        $status = $_GET['status'];

        $this->appointmentModel->updateStatus($id, $status);
        header("Location: /index.php?action=receptionist_appointments");
        exit();
    }
}
?>
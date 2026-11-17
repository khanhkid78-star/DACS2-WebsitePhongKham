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
            $when = date('H:i d/m/Y', strtotime($appointment_date));
            $patientName = $info['patient_name'] ?? 'Bệnh nhân';
            $doctorName = $info['doctor_name'] ?? '';
            Notify::send(
                $this->db,
                'receptionists',
                0,
                'Lịch mới chờ duyệt',
                $patientName . ' xin khám với BS. ' . $doctorName . ' lúc ' . $when . '. Triệu chứng: ' . $note . '. Vào Quản lý lịch hẹn để xác nhận hoặc hủy.',
                '/index.php?action=receptionist_appointments'
            );
            Notify::send(
                $this->db,
                'doctor',
                $doctor_id,
                'Có lịch chờ lễ tân duyệt',
                $patientName . ' dự kiến khám lúc ' . $when . '. Ca này chưa vào danh sách khám của bạn cho đến khi lễ tân xác nhận.',
                '/index.php?action=doctor_records'
            );

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
        if ($id < 1 || !$this->appointmentModel->updateStatus($id, $status)) {
            Auth::flash('Không đổi được. Lịch đã hủy, đã khám xong, hoặc trạng thái không hợp lệ.');
            header('Location: /index.php?action=receptionist_appointments');
            exit();
        }
        $this->tellAppointment($id, $status);
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
        $stamp = strtotime($when);
        $exists = $this->db->prepare("SELECT 1 FROM doctors WHERE doctor_id = :id");
        $exists->execute(array(':id' => $doctorId));
        if ($id < 1 || !$exists->fetchColumn() || $stamp === false || $note === '') {
            Auth::flash('Dòng lịch chưa đủ hoặc không hợp lệ.');
            header('Location: /index.php?action=receptionist_appointments');
            exit();
        }
        if (!$this->appointmentModel->updateRow($id, $doctorId, date('Y-m-d H:i:s', $stamp), $note, $status)) {
            Auth::flash('Không sửa được. Lịch đã hủy không đổi trạng thái.');
            header('Location: /index.php?action=receptionist_appointments');
            exit();
        }
        $saved = $this->db->prepare("SELECT status FROM appointments WHERE appointment_id = :id");
        $saved->execute(array(':id' => $id));
        $this->tellAppointment($id, (string) $saved->fetchColumn());
        header('Location: /index.php?action=receptionist_appointments');
        exit();
    }

    private function tellAppointment($id, $status) {
        require_once __DIR__ . '/../helpers/Notify.php';
        $info = $this->db->prepare(
            "SELECT a.appointment_date, a.note, a.patient_id, a.doctor_id,
                    p.full_name AS patient_name, d.full_name AS doctor_name
            FROM appointments a
            LEFT JOIN patients p ON p.patient_id = a.patient_id
            LEFT JOIN doctors d ON d.doctor_id = a.doctor_id
            WHERE a.appointment_id = :id"
        );
        $info->execute(array(':id' => $id));
        $row = $info->fetch(PDO::FETCH_ASSOC);
        if (!$row || ($status !== 'Scheduled' && $status !== 'Cancelled')) {
            Auth::flash('Đã lưu lịch.', true);
            return;
        }
        $when = date('H:i d/m/Y', strtotime($row['appointment_date']));
        $patient = $row['patient_name'] ?: 'Bệnh nhân';
        $doctor = $row['doctor_name'] ?: 'bác sĩ';
        if ($status === 'Scheduled') {
            Notify::send($this->db, 'patient', (int) $row['patient_id'], 'Lịch khám đã được xác nhận', 'Lịch #' . $id . ' với BS. ' . $doctor . ' lúc ' . $when . ' đã được lễ tân xếp. Vui lòng đến trước 15 phút, mang CCCD và thẻ BHYT.', '/index.php?action=my_appointments');
            Notify::send($this->db, 'doctor', (int) $row['doctor_id'], 'Lễ tân đã xếp lịch', 'Ca #' . $id . ' với ' . $patient . ' lúc ' . $when . ' đã được xác nhận. Triệu chứng: ' . $row['note'] . '.', '/index.php?action=doctor_records');
            $letter = "Kính gửi " . $patient . ",\n\n"
                . "Phòng Khám Đa Khoa Hưng Định đã xác nhận lịch khám của quý khách.\n\n"
                . "Mã lịch: #" . $id . "\n"
                . "Bác sĩ: " . $doctor . "\n"
                . "Thời gian: " . $when . "\n"
                . "Triệu chứng đã ghi: " . $row['note'] . "\n"
                . "Trạng thái: Đã lên lịch\n\n"
                . "Quý khách vui lòng có mặt trước 15 phút và mang theo CCCD cùng thẻ BHYT. "
                . "Nếu cần đổi giờ, gọi hotline 0931 999 309.\n\n"
                . "Trân trọng,\nPhòng Khám Đa Khoa Hưng Định\n128 Nguyễn Văn Trỗi, Phú Nhuận, TP. Hồ Chí Minh";
            $subject = 'Xác nhận lịch khám #' . $id . ' - Phòng Khám Hưng Định';
        } else {
            Notify::send($this->db, 'patient', (int) $row['patient_id'], 'Lịch khám đã hủy', 'Lịch #' . $id . ' với BS. ' . $doctor . ' lúc ' . $when . ' đã được hủy. Để đặt lại, vào Đặt lịch khám hoặc gọi 0931 999 309.', '/index.php?action=my_appointments');
            Notify::send($this->db, 'doctor', (int) $row['doctor_id'], 'Một ca đã hủy', 'Lịch #' . $id . ' với ' . $patient . ' lúc ' . $when . ' đã hủy. Bạn không cần đón ca này.', '/index.php?action=doctor_records');
            $letter = "Kính gửi " . $patient . ",\n\n"
                . "Phòng Khám Đa Khoa Hưng Định xin thông báo lịch khám sau đã được hủy.\n\n"
                . "Mã lịch: #" . $id . "\n"
                . "Bác sĩ: " . $doctor . "\n"
                . "Thời gian dự kiến: " . $when . "\n"
                . "Trạng thái: Đã hủy\n\n"
                . "Quý khách có thể đặt lịch mới trên trang phòng khám hoặc gọi hotline 0931 999 309.\n\n"
                . "Trân trọng,\nPhòng Khám Đa Khoa Hưng Định\n128 Nguyễn Văn Trỗi, Phú Nhuận, TP. Hồ Chí Minh";
            $subject = 'Thông báo hủy lịch #' . $id . ' - Phòng Khám Hưng Định';
        }
        $sent = Notify::mailPatient($this->db, (int) $row['patient_id'], $subject, $letter);
        Auth::flash($sent ? 'Đã lưu lịch và gửi email cho bệnh nhân.' : 'Đã lưu lịch. Email chưa gửi được: kiểm tra email bệnh nhân và USER/PASS trong mail.php.', $sent);
    }
}
?>
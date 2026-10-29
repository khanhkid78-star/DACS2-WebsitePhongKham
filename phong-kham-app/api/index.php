<?php
require_once __DIR__ . '/../app/helpers/Auth.php';
require_once __DIR__ . '/../app/config/database.php';

Auth::startSession();

$action = isset($_GET['action']) ? $_GET['action'] : 'home';

switch ($action) {

    // --- LUỒNG BỆNH NHÂN ---
    case 'booking':
        $db = (new Database())->getConnection();
        $stmt = $db->query("SELECT doctor_id, full_name, specialty FROM doctors");
        $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../app/views/patient/booking_form.php';
        break;

    case 'submit_booking':
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $db = (new Database())->getConnection();
            $patient_id = isset($_SESSION['patient_id']) ? $_SESSION['patient_id'] : 1; // ID mẫu nếu chưa login
            
            $stmt = $db->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, status, note) VALUES (?, ?, ?, 'Pending', ?)");
            $stmt->execute(array($patient_id, $_POST['doctor_id'], $_POST['appointment_date'], $_POST['note']));
            
            echo "<script>alert('Đặt lịch hẹn thành công!'); window.location.href='/index.php';</script>";
        }
        break;

    // --- LUỒNG LỄ TÂN ---
    case 'receptionist_appointments':
        Auth::checkRole(array('receptionist', 'admin')); //[cite: 25]
        $db = (new Database())->getConnection();
        $sql = "SELECT a.*, p.full_name as patient_name, p.phone as patient_phone, d.full_name as doctor_name 
                FROM appointments a 
                LEFT JOIN patients p ON a.patient_id = p.patient_id 
                LEFT JOIN doctors d ON a.doctor_id = d.doctor_id 
                ORDER BY a.appointment_date DESC";
        $appointments = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../app/views/receptionist/manage_appointments.php';
        break;

    case 'update_appointment_status':
        Auth::checkRole(array('receptionist', 'admin')); //[cite: 25]
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ?");
        $stmt->execute(array($_GET['status'], $_GET['id']));
        header("Location: /index.php?action=receptionist_appointments");
        break;

    // --- LUỒNG BÁC SĨ ---
    case 'doctor_records':
        Auth::checkRole(array('doctor', 'admin')); //[cite: 25]
        $db = (new Database())->getConnection();
        $records = $db->query("SELECT * FROM medical_records ORDER BY visit_date DESC")->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../app/views/doctor/medical_records.php';
        break;

    // --- LUỒNG Y TÁ ---
    case 'nurse_medicines':
        Auth::checkRole(array('nurse', 'admin')); //[cite: 25]
        $db = (new Database())->getConnection();
        $medicines = $db->query("SELECT * FROM medicines ORDER BY medicine_id ASC")->fetchAll(PDO::FETCH_ASSOC);
        require_once __DIR__ . '/../app/views/nurse/medicines.php';
        break;

    // --- TRANG CHỦ MẶC ĐỊNH ---
    case 'home':
    default:
        require_once __DIR__ . '/../app/views/partials/header.php';
        require_once __DIR__ . '/../app/views/partials/navbar.php';
        echo "<div style='text-align: center; padding: 40px 0;'>
                <h1 style='color: #004b93;'>TRUNG TÂM Y KHOA PHÚC KHANG</h1>
                <p>Chăm sóc sức khỏe toàn diện với đội ngũ Bác sĩ, Y tá và Lễ tân chuyên nghiệp.</p>
                <a href='/index.php?action=booking' style='background: #ff6b00; color: white; padding: 12px 25px; text-decoration: none; border-radius: 4px; font-weight: bold;'>ĐẶT LỊCH KHÁM NGAY</a>
              </div>";
        require_once __DIR__ . '/../app/views/partials/footer.php';
        break;
}
?>
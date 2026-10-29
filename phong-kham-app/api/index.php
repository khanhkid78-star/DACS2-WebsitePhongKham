<?php
require_once __DIR__ . '/../app/helpers/Auth.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/BookingController.php';
require_once __DIR__ . '/../app/controllers/RecordController.php';
require_once __DIR__ . '/../app/controllers/InvoiceController.php';
require_once __DIR__ . '/../app/controllers/MedicineController.php';
require_once __DIR__ . '/../app/controllers/AdminController.php';

Auth::startSession();

$action = isset($_GET['action']) ? $_GET['action'] : 'home';

switch ($action) {

    // --- XÁC THỰC ---
    case 'login':
        (new AuthController())->showLogin();
        break;
    case 'do_login':
        (new AuthController())->login();
        break;
    case 'logout':
        (new AuthController())->logout();
        break;

    // --- BỆNH NHÂN ---
    case 'booking':
        (new BookingController())->showForm();
        break;
    case 'submit_booking':
        (new BookingController())->submit();
        break;
    case 'my_appointments':
        (new BookingController())->myAppointments();
        break;

    // --- LỄ TÂN ---
    case 'receptionist_appointments':
        (new BookingController())->manageAppointments();
        break;
    case 'update_appointment_status':
        (new BookingController())->updateStatus();
        break;
    case 'invoices':
        (new InvoiceController())->listInvoices();
        break;

    // --- BÁC SĨ ---
    case 'doctor_records':
        (new RecordController())->listRecords();
        break;

    // --- Y TÁ ---
    case 'nurse_medicines':
        (new MedicineController())->listMedicines();
        break;

    // --- ADMIN ---
    case 'admin_users':
        (new AdminController())->listUsers();
        break;

    // --- TRANG CHỦ ---
    case 'home':
    default:
        require_once __DIR__ . '/../app/views/partials/header.php';
        require_once __DIR__ . '/../app/views/partials/navbar.php';
        echo "<div style='text-align: center; padding: 50px 0;'>
                <h1 style='color: #004b93;'>TRUNG TÂM Y KHOA PHÚC KHANG</h1>
                <p>Hệ thống Đặt lịch khám trực tuyến và Quản lý Bệnh án chuyên nghiệp.</p>
                <a href='/index.php?action=booking' style='background: #ff6b00; color: white; padding: 12px 25px; text-decoration: none; border-radius: 4px; font-weight: bold;'>ĐẶT LỊCH KHÁM NGAY</a>
              </div>";
        require_once __DIR__ . '/../app/views/partials/footer.php';
        break;
}
?>
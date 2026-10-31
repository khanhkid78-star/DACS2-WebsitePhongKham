<?php
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if ($path === '/style.css' || $path === '/public/css/style.css') {
    header('Content-Type: text/css; charset=UTF-8');
    readfile(__DIR__ . '/../public/css/style.css');
    exit;
}
require_once __DIR__ . '/../app/helpers/Auth.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if ($path === '/style.css' || $path === '/public/css/style.css') {
    header('Content-Type: text/css; charset=UTF-8');
    readfile(__DIR__ . '/../public/css/style.css');
    exit;
}
require_once __DIR__ . '/../app/controllers/AuthController.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if ($path === '/style.css' || $path === '/public/css/style.css') {
    header('Content-Type: text/css; charset=UTF-8');
    readfile(__DIR__ . '/../public/css/style.css');
    exit;
}

require_once __DIR__ . '/../app/controllers/BookingController.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if ($path === '/style.css' || $path === '/public/css/style.css') {
    header('Content-Type: text/css; charset=UTF-8');
    readfile(__DIR__ . '/../public/css/style.css');
    exit;
}

require_once __DIR__ . '/../app/controllers/RecordController.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if ($path === '/style.css' || $path === '/public/css/style.css') {
    header('Content-Type: text/css; charset=UTF-8');
    readfile(__DIR__ . '/../public/css/style.css');
    exit;
}
require_once __DIR__ . '/../app/controllers/InvoiceController.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if ($path === '/style.css' || $path === '/public/css/style.css') {
    header('Content-Type: text/css; charset=UTF-8');
    readfile(__DIR__ . '/../public/css/style.css');
    exit;
}
require_once __DIR__ . '/../app/controllers/MedicineController.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if ($path === '/style.css' || $path === '/public/css/style.css') {
    header('Content-Type: text/css; charset=UTF-8');
    readfile(__DIR__ . '/../public/css/style.css');
    exit;
}
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
            case 'about':
                require_once __DIR__ . '/../app/config/database.php';
                $db = (new Database())->getConnection();
                $doctors = $db->query(
                    "SELECT doctor_id, full_name, specialty
                    FROM doctors
                    ORDER BY full_name"
                )->fetchAll(PDO::FETCH_ASSOC);
                require __DIR__ . '/../app/views/home.php';
                break;

            default:
                header('Location: /index.php?action=home');
                exit();
        }
?>
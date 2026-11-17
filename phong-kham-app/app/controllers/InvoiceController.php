<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Invoice.php';
require_once __DIR__ . '/../helpers/Auth.php';

class InvoiceController {
    private function model() {
        return new Invoice((new Database())->getConnection());
    }

    public function listInvoices() {
        Auth::checkRole(array('receptionist', 'admin'));
        $model = $this->model();
        $invoices = $model->getAllWithPatient();
        $billable = $model->recordsWithoutInvoice();
        require_once __DIR__ . '/../views/receptionist/invoices.php';
    }

    public function create() {
        Auth::checkRole(array('receptionist', 'admin'));
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /index.php?action=invoices');
            exit();
        }
        $recordId = (int) ($_POST['record_id'] ?? 0);
        $message = $this->model()->createFromRecord($recordId, $_POST['exam_fee'] ?? '', $_POST['payment_method'] ?? '');

        $mailPatientId = 0;
        $emailSubject = '';
        $emailLetter = '';

        if ($message === '') {
            $db = (new Database())->getConnection();
            $row = $db->prepare("SELECT patient_id, patient_name FROM medical_records WHERE record_id = :id");
            $row->execute(array(':id' => $recordId));
            $info = $row->fetch(PDO::FETCH_ASSOC);
            if ($info) {
                require_once __DIR__ . '/../helpers/Notify.php';
                $inv = $db->prepare(
                    "SELECT invoice_id, total_amount, payment_method
                    FROM invoices WHERE record_id = :id ORDER BY invoice_id DESC LIMIT 1"
                );
                $inv->execute(array(':id' => $recordId));
                $bill = $inv->fetch(PDO::FETCH_ASSOC);
                $lines = array();
                if ($bill) {
                    $detail = $db->prepare("SELECT item_type, quantity, price FROM invoice_details WHERE invoice_id = :id ORDER BY invoice_detail_id");
                    $detail->execute(array(':id' => $bill['invoice_id']));
                    $lines = $detail->fetchAll(PDO::FETCH_ASSOC);
                }
                $amount = $bill ? number_format((float) $bill['total_amount'], 0, ',', '.') : '0';
                $method = $bill['payment_method'] ?? '';
                $invoiceId = (int) ($bill['invoice_id'] ?? 0);
                Notify::send(
                    $db,
                    'patient',
                    (int) $info['patient_id'],
                    'Có hóa đơn cần thanh toán',
                    'Hóa đơn #' . $invoiceId . ' tổng ' . $amount . ' đ, thanh toán bằng ' . $method . ', hiện chưa thu. Xem mục Hóa đơn.',
                    '/index.php?action=my_appointments#hoa-don'
                );
                $rows = '';
                foreach ($lines as $line) {
                    $rows .= '- ' . $line['item_type'] . ': ' . (int) $line['quantity'] . ' x ' . number_format((float) $line['price'], 0, ',', '.') . " đ\n";
                }
                $mailPatientId = (int) $info['patient_id'];
                $emailSubject = 'Hóa đơn #' . $invoiceId . ' - Phòng Khám Hưng Định';
                $emailLetter = "Kính gửi " . $info['patient_name'] . ",\n\n"
                    . "Phòng Khám Đa Khoa Hưng Định đã lập hóa đơn cho buổi khám của quý khách.\n\n"
                    . "Mã hóa đơn: #" . $invoiceId . "\n"
                    . "Mã bệnh án: #" . $recordId . "\n"
                    . "Hình thức: " . $method . "\n"
                    . "Trạng thái: Chưa thanh toán\n\n"
                    . "Chi tiết:\n" . ($rows !== '' ? $rows : "- Chưa có dòng chi tiết.\n") . "\n"
                    . "Tổng cộng: " . $amount . " đ\n\n"
                    . "Quý khách thanh toán tại quầy lễ tân. Hotline: 0931 999 309.\n\n"
                    . "Trân trọng,\nPhòng Khám Đa Khoa Hưng Định\n128 Nguyễn Văn Trỗi, Phú Nhuận, TP. Hồ Chí Minh";
            }
        }

        Auth::flash($message === '' ? 'Đã lập hóa đơn.' : $message, $message === '');
        $target = '/index.php?action=invoices';
        $tab = Auth::currentTab();
        if ($tab !== '') {
            $target .= '&tab=' . urlencode($tab);
        }
        header('Location: ' . $target);
        if ($emailLetter !== '' && function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        if ($emailLetter !== '') {
            Notify::mailPatient($db, $mailPatientId, $emailSubject, $emailLetter);
        }
        exit();
    }

    public function pay() {
        Auth::checkRole(array('receptionist', 'admin'));
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /index.php?action=invoices');
            exit();
        }
        if ($this->model()->markPaid((int) ($_POST['invoice_id'] ?? 0))) {
            $db = (new Database())->getConnection();
            $row = $db->prepare("SELECT patient_id, total_amount FROM invoices WHERE invoice_id = :id");
            $row->execute(array(':id' => (int) ($_POST['invoice_id'] ?? 0)));
            $info = $row->fetch(PDO::FETCH_ASSOC);
            
            if ($info) {
                require_once __DIR__ . '/../helpers/Notify.php';
                $more = $db->prepare(
                    "SELECT i.invoice_id, i.total_amount, i.payment_method, p.full_name
                    FROM invoices i
                    LEFT JOIN patients p ON p.patient_id = i.patient_id
                    WHERE i.invoice_id = :id"
                );
                $more->execute(array(':id' => (int) ($_POST['invoice_id'] ?? 0)));
                $bill = $more->fetch(PDO::FETCH_ASSOC);
                if ($bill) {
                    $amount = number_format((float) $bill['total_amount'], 0, ',', '.');
                    Notify::send(
                        $db,
                        'patient',
                        (int) $info['patient_id'],
                        'Đã thanh toán hóa đơn',
                        'Hóa đơn #' . (int) $bill['invoice_id'] . ' đã thu ' . $amount . ' đ bằng ' . $bill['payment_method'] . '.',
                        '/index.php?action=my_appointments#hoa-don'
                    );
                    $letter = "Kính gửi " . ($bill['full_name'] ?: 'quý khách') . ",\n\n"
                        . "Phòng Khám Đa Khoa Hưng Định đã nhận thanh toán.\n\n"
                        . "Mã hóa đơn: #" . (int) $bill['invoice_id'] . "\n"
                        . "Số tiền: " . $amount . " đ\n"
                        . "Hình thức: " . $bill['payment_method'] . "\n"
                        . "Trạng thái: Đã thanh toán\n\n"
                        . "Cảm ơn quý khách đã sử dụng dịch vụ. Giữ thư này như biên nhận.\n\n"
                        . "Trân trọng,\nPhòng Khám Đa Khoa Hưng Định\n128 Nguyễn Văn Trỗi, Phú Nhuận, TP. Hồ Chí Minh\nHotline: 0931 999 309";
                    Notify::mailPatient($db, (int) $info['patient_id'], 'Biên nhận hóa đơn #' . (int) $bill['invoice_id'] . ' - Phòng Khám Hưng Định', $letter);
                }
            }

            Auth::flash('Đã xác nhận thu tiền.', true);

        } else {

            Auth::flash('Hóa đơn này đã thanh toán, hoặc không còn.');
        }
        header('Location: /index.php?action=invoices');
        exit();
    }


    public function preview() {
        Auth::checkRole(array('receptionist', 'admin'));
        header('Content-Type: application/json; charset=UTF-8');
        $id = (int) ($_GET['record_id'] ?? 0);
        $row = $id > 0 ? $this->model()->quote($id) : null;
        echo json_encode($row ? array('ok' => true, 'data' => $row) : array('ok' => false), JSON_UNESCAPED_UNICODE);
        exit();
    }

    public function detail() {
        Auth::checkRole(array('receptionist', 'admin', 'patient'));
        header('Content-Type: application/json; charset=UTF-8');
        $id = (int) ($_GET['id'] ?? 0);
        $patientFilter = null;
        if (Auth::getRole() === 'patient') {
            $patientFilter = (int) ($_SESSION['patient_id'] ?? 0);
            if ($patientFilter < 1) {
                echo json_encode(array('ok' => false));
                exit();
            }
        }
        $row = $id > 0 ? $this->model()->fullView($id, $patientFilter) : null;
        echo json_encode($row ? array('ok' => true, 'data' => $row) : array('ok' => false, 'error' => 'Không thấy hóa đơn'), JSON_UNESCAPED_UNICODE);
        exit();
    }
}
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
        if ($message === '') {
            $db = (new Database())->getConnection();
            $row = $db->prepare("SELECT patient_id, patient_name FROM medical_records WHERE record_id = :id");
            $row->execute(array(':id' => $recordId));
            $info = $row->fetch(PDO::FETCH_ASSOC);
            if ($info) {
                require_once __DIR__ . '/../helpers/Notify.php';
                Notify::send($db, 'patient', (int) $info['patient_id'], 'Có hóa đơn mới', 'Phòng khám đã lập hóa đơn cho ' . $info['patient_name'] . '.', '/index.php?action=my_appointments#hoa-don');
            }
        }
        Auth::flash($message === '' ? 'Đã lập hóa đơn.' : $message, $message === '');
        header('Location: /index.php?action=invoices');
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
                Notify::send($db, 'patient', (int) $info['patient_id'], 'Đã thu hóa đơn', 'Hóa đơn ' . number_format((float) $info['total_amount']) . ' đ đã được thu.', '/index.php?action=my_appointments#hoa-don');
            }

            Auth::flash('Đã xác nhận thu tiền.', true);

        } else {

            Auth::flash('Hóa đơn này đã thanh toán, hoặc không còn.');
        }
        header('Location: /index.php?action=invoices');
        exit();
    }
}
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
        $message = $this->model()->createFromRecord(
            (int) ($_POST['record_id'] ?? 0),
            $_POST['exam_fee'] ?? '',
            $_POST['payment_method'] ?? ''
        );
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
            Auth::flash('Đã xác nhận thu tiền.', true);
        } else {
            Auth::flash('Hóa đơn này đã thanh toán, hoặc không còn.');
        }
        header('Location: /index.php?action=invoices');
        exit();
    }
}
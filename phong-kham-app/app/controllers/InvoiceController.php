<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/Auth.php';

class InvoiceController {
    public function listInvoices() {
        Auth::checkRole(array('receptionist', 'admin'));
        $db = (new Database())->getConnection();

        $sql = "SELECT i.*, p.full_name AS patient_name 
                FROM invoices i 
                LEFT JOIN patients p ON i.patient_id = p.patient_id 
                ORDER BY i.created_at DESC";
        $invoices = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../views/receptionist/invoices.php';
    }
}
?>
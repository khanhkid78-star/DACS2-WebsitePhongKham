<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/MedicalRecord.php';
require_once __DIR__ . '/../helpers/Auth.php';

class RecordController {
    public function listRecords() {
        Auth::checkRole(array('doctor', 'admin'));
        
        $db = (new Database())->getConnection();
        $recordModel = new MedicalRecord($db);
        $records = $recordModel->getAll();

        require_once __DIR__ . '/../views/doctor/medical_records.php';
    }
}
?>
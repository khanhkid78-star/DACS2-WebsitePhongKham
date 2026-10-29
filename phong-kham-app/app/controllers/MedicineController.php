<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Medicine.php';
require_once __DIR__ . '/../helpers/Auth.php';

class MedicineController {
    public function listMedicines() {
        Auth::checkRole(array('nurse', 'admin'));

        $db = (new Database())->getConnection();
        $medicineModel = new Medicine($db);
        $medicines = $medicineModel->getAll();

        require_once __DIR__ . '/../views/nurse/medicines.php';
    }
}
?>
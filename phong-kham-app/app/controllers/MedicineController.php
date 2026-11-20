<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Medicine.php';
require_once __DIR__ . '/../helpers/Auth.php';

class MedicineController {
    private function model() {
        return new Medicine((new Database())->getConnection());
    }

    public function listMedicines() {
        Auth::checkRole(array('nurse', 'admin'));
        $medicineModel = $this->model();
        $medicines = $medicineModel->search('', '');
        $units = $medicineModel->units();
        require_once __DIR__ . '/../views/nurse/medicines.php';
    }

    public function rows() {
        Auth::checkRole(array('nurse', 'admin'));
        $medicines = $this->model()->search(
            trim($_GET['q'] ?? ''),
            trim($_GET['unit'] ?? ''),
            trim($_GET['min_price'] ?? ''),
            trim($_GET['max_price'] ?? ''),
            trim($_GET['sort'] ?? '')
        );
        require __DIR__ . '/../views/nurse/_medicine_rows.php';
        exit();
    }

    public function save() {
        Auth::checkRole(array('nurse', 'admin'));
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /index.php?action=nurse_medicines');
            exit();
        }
        $id = (int) ($_POST['medicine_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $unit = trim($_POST['unit'] ?? '');
        $price = $_POST['price'] ?? '';
        $description = trim($_POST['description'] ?? '');
        if ($name === '' || $unit === '' || !is_numeric($price) || (float) $price < 0) {
            Auth::flash('Nhập đủ tên, đơn vị và đơn giá.');
            header('Location: /index.php?action=nurse_medicines');
            exit();
        }
        $model = $this->model();
        if ($id > 0) {
            $model->update($id, $name, $unit, (float) $price, $description);
            Auth::flash('Đã cập nhật thuốc.', true);
        } else {
            $model->create($name, $unit, (float) $price, $description);
            Auth::flash('Đã thêm thuốc.', true);
        }
        header('Location: /index.php?action=nurse_medicines');
        exit();
    }

    public function delete() {
        Auth::checkRole(array('nurse', 'admin'));
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /index.php?action=nurse_medicines');
            exit();
        }
        $id = (int) ($_POST['medicine_id'] ?? 0);
        try {
            if ($id > 0 && $this->model()->delete($id)) {
                Auth::flash('Đã xóa thuốc.', true);
            } else {
                Auth::flash('Không xóa được thuốc.');
            }
        } catch (PDOException $e) {
            Auth::flash('Thuốc đang nằm trong đơn, chưa xóa được.');
        }
        header('Location: /index.php?action=nurse_medicines');
        exit();
    }
}
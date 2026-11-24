<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Service.php';
require_once __DIR__ . '/../helpers/Auth.php';

class ServiceController {
    private function model() { return new Service((new Database())->getConnection()); }

    public function show() {
        Auth::checkRole(array('admin'));
        $services = $this->model()->all();
        $specialties = $this->model()->specialties();
        require __DIR__ . '/../views/admin/services.php';
    }

    public function save() {
        Auth::checkRole(array('admin'));
        $message = $this->model()->save((int) ($_POST['service_id'] ?? 0), (int) ($_POST['specialty_id'] ?? 0), $_POST['name'] ?? '', $_POST['price'] ?? '');
        Auth::flash($message === '' ? 'Đã lưu dịch vụ.' : $message, $message === '');
        header('Location: /index.php?action=services');
        exit();
    }

    public function delete() {
        Auth::checkRole(array('admin'));
        $message = $this->model()->delete((int) ($_POST['service_id'] ?? 0));
        Auth::flash($message === '' ? 'Đã xóa dịch vụ.' : $message, $message === '');
        header('Location: /index.php?action=services');
        exit();
    }

    public function options() {
        Auth::checkRole(array('doctor', 'admin'));
        header('Content-Type: application/json; charset=UTF-8');
        $data = $this->model()->catalog((int) ($_GET['appointment_id'] ?? 0), (int) ($_GET['record_id'] ?? 0));
        echo json_encode(array('ok' => true, 'data' => $data), JSON_UNESCAPED_UNICODE);
        exit();
    }
}
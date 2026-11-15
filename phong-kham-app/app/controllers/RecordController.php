<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/MedicalRecord.php';
require_once __DIR__ . '/../models/Appointment.php';
require_once __DIR__ . '/../models/Medicine.php';
require_once __DIR__ . '/../helpers/Auth.php';

class RecordController {
    private function bag() {
        $db = (new Database())->getConnection();
        return array(
            'records' => new MedicalRecord($db),
            'appointments' => new Appointment($db),
            'medicines' => new Medicine($db),
        );
    }

    public function listRecords() {
        Auth::checkRole(array('doctor', 'admin'));
        $bag = $this->bag();
        $role = Auth::getRole();
        $doctorId = (int) ($_SESSION['doctor_id'] ?? 0);

        if ($role === 'doctor' && $doctorId < 1) {
            $records = array();
            $openAppointments = array();
        } else {
            $filter = ($role === 'doctor') ? $doctorId : null;
            $records = $bag['records']->getList($filter, null);
            $openAppointments = $bag['appointments']->getOpenForRecord($filter);
        }

        $medicines = $bag['medicines']->getAll();
        $editRecord = null;
        $editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
        if ($editId > 0) {
            $editRecord = $bag['records']->find($editId, ($role === 'doctor') ? $doctorId : null);
        }

        require_once __DIR__ . '/../views/doctor/medical_records.php';
    }

    public function save() {
        Auth::checkRole(array('doctor', 'admin'));
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /index.php?action=doctor_records');
            exit();
        }

        $role = Auth::getRole();
        $doctorId = (int) ($_SESSION['doctor_id'] ?? 0);
        if ($role === 'doctor' && $doctorId < 1) {
            Auth::flash('Tài khoản bác sĩ chưa gắn hồ sơ bác sĩ.');
            header('Location: /index.php?action=doctor_records');
            exit();
        }

        $appointmentId = (int) ($_POST['appointment_id'] ?? 0);
        $symptoms = trim($_POST['symptoms'] ?? '');
        $diagnosis = trim($_POST['diagnosis'] ?? '');
        $conclusion = trim($_POST['conclusion'] ?? '');
        if ($appointmentId < 1 || $symptoms === '' || $diagnosis === '' || $conclusion === '') {
            Auth::flash('Chọn lịch và điền triệu chứng, chẩn đoán, kết luận.');
            header('Location: /index.php?action=doctor_records');
            exit();
        }

        $bag = $this->bag();
        $filter = ($role === 'doctor') ? $doctorId : null;
        $appointment = $bag['appointments']->findScheduled($appointmentId, $filter);
        if (!$appointment) {
            Auth::flash('Lịch này chưa được duyệt, hoặc không thuộc bạn.');
            header('Location: /index.php?action=doctor_records');
            exit();
        }

        $clean = array();
        foreach ($this->readLines() as $line) {
            $touched = $line['medicine_id'] || $line['quantity'] || $line['dosage'] !== '' || $line['usage'] !== '';
            if (!$touched) {
                continue;
            }
            if ($line['medicine_id'] < 1 || $line['quantity'] < 1 || $line['dosage'] === '' || $line['usage'] === '') {
                Auth::flash('Mỗi dòng thuốc cần đủ thuốc, số lượng, liều và cách dùng.');
                header('Location: /index.php?action=doctor_records');
                exit();
            }
            $clean[] = $line;
        }

        try {
            $recordId = $bag['records']->createWithPrescription(array(
                'patient_id' => (int) $appointment['patient_id'],
                'patient_name' => $appointment['patient_name'],
                'doctor_id' => (int) $appointment['doctor_id'],
                'doctor_name' => $appointment['doctor_name'],
                'appointment_id' => (int) $appointment['appointment_id'],
                'visit_date' => $appointment['appointment_date'],
                'symptoms' => $symptoms,
                'diagnosis' => $diagnosis,
                'conclusion' => $conclusion,
            ), $clean);
            if ($recordId) {
                Auth::flash('Đã lưu bệnh án. Lịch chuyển sang đã khám xong.', true);
            } else {
                Auth::flash('Lịch này đã có bệnh án.');
            }
        } catch (Exception $e) {
            Auth::flash('Không lưu được bệnh án.');
        }
        header('Location: /index.php?action=doctor_records');
        exit();
    }

    public function update() {
        Auth::checkRole(array('doctor', 'admin'));
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /index.php?action=doctor_records');
            exit();
        }
        $id = (int) ($_POST['record_id'] ?? 0);
        $symptoms = trim($_POST['symptoms'] ?? '');
        $diagnosis = trim($_POST['diagnosis'] ?? '');
        $conclusion = trim($_POST['conclusion'] ?? '');
        if ($id < 1 || $symptoms === '' || $diagnosis === '' || $conclusion === '') {
            Auth::flash('Điền đủ triệu chứng, chẩn đoán và kết luận.');
            header('Location: /index.php?action=doctor_records');
            exit();
        }

        $doctorFilter = null;
        if (Auth::getRole() === 'doctor') {
            $doctorFilter = (int) ($_SESSION['doctor_id'] ?? 0);
            if ($doctorFilter < 1) {
                Auth::flash('Tài khoản bác sĩ chưa gắn hồ sơ.');
                header('Location: /index.php?action=doctor_records');
                exit();
            }
        }

        $bag = $this->bag();
        if ($bag['records']->update($id, $symptoms, $diagnosis, $conclusion, $doctorFilter)) {
            Auth::flash('Đã cập nhật bệnh án.', true);
        } else {
            Auth::flash('Không sửa được bệnh án này.');
        }
        header('Location: /index.php?action=doctor_records');
        exit();
    }

    private function readLines() {
        $ids = isset($_POST['medicine_id']) ? (array) $_POST['medicine_id'] : array();
        $qtys = isset($_POST['quantity']) ? (array) $_POST['quantity'] : array();
        $dosages = isset($_POST['dosage']) ? (array) $_POST['dosage'] : array();
        $usages = isset($_POST['usage']) ? (array) $_POST['usage'] : array();
        $lines = array();
        $total = count($ids);
        for ($i = 0; $i < $total; $i++) {
            $lines[] = array(
                'medicine_id' => (int) (isset($ids[$i]) ? $ids[$i] : 0),
                'quantity' => (int) (isset($qtys[$i]) ? $qtys[$i] : 0),
                'dosage' => trim(isset($dosages[$i]) ? $dosages[$i] : ''),
                'usage' => trim(isset($usages[$i]) ? $usages[$i] : ''),
            );
        }
        return $lines;
    }
}
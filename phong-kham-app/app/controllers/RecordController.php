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

        $doctors = array();
        if ($role === 'admin') {
            $doctors = (new Database())->getConnection()->query("SELECT doctor_id, full_name FROM doctors ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
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

        $pendingMail = null;
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
                require_once __DIR__ . '/../helpers/Notify.php';
                $db = (new Database())->getConnection();
                $when = date('H:i d/m/Y', strtotime($appointment['appointment_date']));
                Notify::send(
                    $db,
                    'patient',
                    (int) $appointment['patient_id'],
                    'Đã có kết quả khám',
                    'BS. ' . $appointment['doctor_name'] . ' đã ghi bệnh án buổi ' . $when . '. Chẩn đoán: ' . $diagnosis . '. Xem mục Bệnh án sau khi đăng nhập.',
                    '/index.php?action=my_appointments#ho-so'
                );
                Notify::send(
                    $db,
                    'receptionists',
                    0,
                    'Bệnh nhân đã khám xong',
                    $appointment['patient_name'] . ' đã khám xong với BS. ' . $appointment['doctor_name'] . ' (lịch #' . (int) $appointment['appointment_id'] . '). Có thể lập hóa đơn.',
                    '/index.php?action=invoices'
                );
                $rx = '';
                if ($clean) {
                    $names = $db->prepare("SELECT medicine_id, name, unit FROM medicines WHERE medicine_id = :id");
                    foreach ($clean as $line) {
                        $names->execute(array(':id' => $line['medicine_id']));
                        $med = $names->fetch(PDO::FETCH_ASSOC);
                        $rx .= '- ' . ($med['name'] ?? 'Thuốc') . ' (' . ($med['unit'] ?? '') . '): ' . $line['quantity'] . ', liều ' . $line['dosage'] . '. ' . $line['usage'] . "\n";
                    }
                } else {
                    $rx = "- Chưa kê đơn thuốc.\n";
                }
                $letter = "Kính gửi " . $appointment['patient_name'] . ",\n\n"
                    . "Phòng Khám Đa Khoa Hưng Định đã lưu hồ sơ buổi khám của quý khách.\n\n"
                    . "Mã hồ sơ: #" . $recordId . "\n"
                    . "Mã lịch: #" . (int) $appointment['appointment_id'] . "\n"
                    . "Bác sĩ: " . $appointment['doctor_name'] . "\n"
                    . "Thời gian khám: " . $when . "\n"
                    . "Triệu chứng: " . $symptoms . "\n"
                    . "Chẩn đoán: " . $diagnosis . "\n"
                    . "Kết luận: " . $conclusion . "\n\n"
                    . "Đơn thuốc:\n" . $rx . "\n"
                    . "Lịch này đã chuyển sang trạng thái Đã khám xong. Quý khách đăng nhập để xem bệnh án và hóa đơn khi lễ tân lập.\n"
                    . "Dùng thuốc đúng liều. Nếu triệu chứng nặng hơn, gọi 0931 999 309.\n\n"
                    . "Trân trọng,\nPhòng Khám Đa Khoa Hưng Định\n128 Nguyễn Văn Trỗi, Phú Nhuận, TP. Hồ Chí Minh";
                    $pendingMail = array(
                        'db' => $db,
                        'patient_id' => (int) $appointment['patient_id'],
                        'subject' => 'Kết quả khám #' . $recordId . ' - Phòng Khám Hưng Định',
                        'letter' => $letter,
                    );
                    Auth::flash('Đã lưu bệnh án. Lịch chuyển sang đã khám xong.', true);

            } else {
                Auth::flash('Lịch này đã có bệnh án.');
            }
        } catch (Exception $e) {
            Auth::flash('Không lưu được bệnh án.');
        }
        $target = '/index.php?action=doctor_records';
        $tab = Auth::currentTab();
        if ($tab !== '') {
            $target .= '&tab=' . urlencode($tab);
        }
        header('Location: ' . $target);
        if ($pendingMail && function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        if ($pendingMail) {
            Notify::mailPatient($pendingMail['db'], $pendingMail['patient_id'], $pendingMail['subject'], $pendingMail['letter']);
        }
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

        $doctorFilter = null;
        if (Auth::getRole() === 'doctor') {
            $doctorFilter = (int) ($_SESSION['doctor_id'] ?? 0);
            if ($doctorFilter < 1) {
                Auth::flash('Tài khoản bác sĩ chưa gắn hồ sơ.');
                header('Location: /index.php?action=doctor_records');
                exit();
            }
        }

        if ($this->bag()['records']->updateWithPrescription($id, $symptoms, $diagnosis, $conclusion, $doctorFilter, $clean)) {
            Auth::flash('Đã cập nhật bệnh án.', true);
        } else {
            Auth::flash('Không sửa được bệnh án này.');
        }
        header('Location: /index.php?action=doctor_records');
        exit();
    }


    public function rows() {
        Auth::checkRole(array('doctor', 'admin'));
        $bag = $this->bag();
        $role = Auth::getRole();
        $doctorId = (int) ($_SESSION['doctor_id'] ?? 0);
        $filter = ($role === 'doctor') ? $doctorId : null;
        $picked = (int) ($_GET['doctor_id'] ?? 0);
        if ($role === 'admin' && $picked > 0) {
            $filter = $picked;
        }
        $records = ($role === 'doctor' && $doctorId < 1)
            ? array()
            : $bag['records']->getList($filter, null, trim($_GET['q'] ?? ''));
        require __DIR__ . '/../views/doctor/_record_rows.php';
        exit();
    }

    public function detail() {
        Auth::checkRole(array('doctor', 'admin'));
        header('Content-Type: application/json; charset=UTF-8');

        $id = (int) ($_GET['id'] ?? 0);
        if ($id < 1) {
            echo json_encode(array('ok' => false, 'error' => 'Thiếu mã hồ sơ'));
            exit();
        }

        $role = Auth::getRole();
        $doctorFilter = null;
        if ($role === 'doctor') {
            $doctorFilter = (int) ($_SESSION['doctor_id'] ?? 0);
            if ($doctorFilter < 1) {
                echo json_encode(array('ok' => false, 'error' => 'Tài khoản bác sĩ chưa gắn hồ sơ'));
                exit();
            }
        }

        $detail = $this->bag()['records']->getDetail($id, $doctorFilter);
        if (!$detail) {
            echo json_encode(array('ok' => false, 'error' => 'Không tìm thấy hồ sơ'));
            exit();
        }

        echo json_encode(array('ok' => true, 'data' => $detail), JSON_UNESCAPED_UNICODE);
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
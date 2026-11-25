<?php
class Service {
    private $conn;
    public function __construct($db) { $this->conn = $db; }

    public function all() {
        return $this->conn->query(
            "SELECT s.service_id, s.name, s.price, sp.specialty_id, sp.name AS specialty
             FROM services s JOIN specialties sp ON sp.specialty_id = s.specialty_id
             ORDER BY sp.name, s.name"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function specialties() {
        return $this->conn->query("SELECT specialty_id, name FROM specialties ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function save($id, $specialtyId, $name, $price) {
        $name = trim($name);
        if ($specialtyId < 1 || $name === '' || !is_numeric($price) || (float) $price < 0) {
            return 'Điền chuyên khoa, tên dịch vụ và giá.';
        }
        if ($id > 0) {
            $stmt = $this->conn->prepare("UPDATE services SET specialty_id = :sid, name = :name, price = :price WHERE service_id = :id");
            $stmt->execute(array(':sid' => $specialtyId, ':name' => $name, ':price' => $price, ':id' => $id));
            return '';
        }
        $stmt = $this->conn->prepare("INSERT INTO services (specialty_id, name, price) VALUES (:sid, :name, :price)");
        try {
            $stmt->execute(array(':sid' => $specialtyId, ':name' => $name, ':price' => $price));
        } catch (PDOException $e) {
            return 'Dịch vụ này đã có trong chuyên khoa.';
        }
        return '';
    }

    public function delete($id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM services WHERE service_id = :id");
            $stmt->execute(array(':id' => $id));
            return $stmt->rowCount() ? '' : 'Không thấy dịch vụ.';
        } catch (PDOException $e) {
            return 'Không xóa được vì đã có bệnh án dùng dịch vụ này.';
        }
    }

    public function catalog($appointmentId, $recordId) {
        if ($recordId > 0) {
            $who = $this->conn->prepare(
                "SELECT btrim(d.specialty) AS specialty
                 FROM medical_records m
                 JOIN doctors d ON d.doctor_id = m.doctor_id
                 WHERE m.record_id = :id"
            );
            $who->execute(array(':id' => (int) $recordId));
        } else {
            $who = $this->conn->prepare(
                "SELECT btrim(d.specialty) AS specialty
                 FROM appointments a
                 JOIN doctors d ON d.doctor_id = a.doctor_id
                 WHERE a.appointment_id = :id"
            );
            $who->execute(array(':id' => (int) $appointmentId));
        }
        $doctorSpecialty = trim((string) $who->fetchColumn());
        $rows = array();
        if ($doctorSpecialty !== '') {
            $stmt = $this->conn->prepare(
                "SELECT s.service_id, s.name, s.price, btrim(sp.name) AS specialty
                 FROM services s
                 JOIN specialties sp ON sp.specialty_id = s.specialty_id
                 WHERE lower(btrim(sp.name)) = lower(:specialty)
                 ORDER BY s.name"
            );
            $stmt->execute(array(':specialty' => $doctorSpecialty));
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $picked = array();
        if ($recordId > 0) {
            $sel = $this->conn->prepare("SELECT service_id FROM record_services WHERE record_id = :id");
            $sel->execute(array(':id' => (int) $recordId));
            $picked = array_map('intval', $sel->fetchAll(PDO::FETCH_COLUMN));
        }
        return array(
            'specialty' => $rows ? $rows[0]['specialty'] : $doctorSpecialty,
            'doctor_specialty' => $doctorSpecialty,
            'services' => $rows,
            'selected' => $picked,
        );
    }
}
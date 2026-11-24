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
            $sql = "SELECT s.service_id, s.name, s.price, sp.name AS specialty
                    FROM services s
                    JOIN specialties sp ON sp.specialty_id = s.specialty_id
                    JOIN doctors d ON d.specialty = sp.name
                    JOIN medical_records m ON m.doctor_id = d.doctor_id
                    WHERE m.record_id = :id ORDER BY s.name";
            $key = $recordId;
        } else {
            $sql = "SELECT s.service_id, s.name, s.price, sp.name AS specialty
                    FROM services s
                    JOIN specialties sp ON sp.specialty_id = s.specialty_id
                    JOIN doctors d ON d.specialty = sp.name
                    JOIN appointments a ON a.doctor_id = d.doctor_id
                    WHERE a.appointment_id = :id ORDER BY s.name";
            $key = $appointmentId;
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(array(':id' => (int) $key));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $picked = array();
        if ($recordId > 0) {
            $sel = $this->conn->prepare("SELECT service_id FROM record_services WHERE record_id = :id");
            $sel->execute(array(':id' => $recordId));
            $picked = array_map('intval', $sel->fetchAll(PDO::FETCH_COLUMN));
        }
        return array(
            'specialty' => $rows ? $rows[0]['specialty'] : '',
            'services' => $rows,
            'selected' => $picked,
        );
    }
}
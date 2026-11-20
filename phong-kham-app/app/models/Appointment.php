<?php
class Appointment {
    private $conn;
    private $table_name = "appointments";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Tạo lịch hẹn mới (Trạng thái mặc định: Pending)
    public function create($patient_id, $doctor_id, $appointment_date, $note) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (patient_id, doctor_id, appointment_date, status, note) 
                  VALUES (:patient_id, :doctor_id, :appointment_date, 'Pending', :note)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":patient_id", $patient_id);
        $stmt->bindParam(":doctor_id", $doctor_id);
        $stmt->bindParam(":appointment_date", $appointment_date);
        $stmt->bindParam(":note", $note);

        return $stmt->execute();
    }

    // Lấy danh sách lịch hẹn của 1 Bệnh nhân
    public function getByPatientId($patient_id) {
        $query = "SELECT a.appointment_id, a.appointment_date, a.status, a.note, 
                         d.full_name AS doctor_name, d.specialty 
                  FROM " . $this->table_name . " a 
                  JOIN doctors d ON a.doctor_id = d.doctor_id 
                  WHERE a.patient_id = :patient_id 
                  ORDER BY a.appointment_date DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":patient_id", $patient_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy tất cả lịch hẹn kèm thông tin Bệnh nhân & Bác sĩ (Dành cho Lễ tân)
    public function getAllWithDetails() {
        $query = "SELECT a.*, p.full_name AS patient_name, p.phone AS patient_phone, d.full_name AS doctor_name 
                  FROM " . $this->table_name . " a 
                  LEFT JOIN patients p ON a.patient_id = p.patient_id 
                  LEFT JOIN doctors d ON a.doctor_id = d.doctor_id 
                  ORDER BY a.appointment_date DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Cập nhật trạng thái duyệt lịch (Scheduled / Cancelled)
    public function updateStatus($appointment_id, $status) {
        if (!in_array($status, array('Scheduled', 'Cancelled'), true)) {
            return false;
        }
        $cur = $this->conn->prepare("SELECT status FROM appointments WHERE appointment_id = :id");
        $cur->execute(array(':id' => $appointment_id));
        $current = $cur->fetchColumn();
        if ($current === false || $current === 'Cancelled' || $current === 'Completed') {
            return false;
        }
        $has = $this->conn->prepare("SELECT 1 FROM medical_records WHERE appointment_id = :id");
        $has->execute(array(':id' => $appointment_id));
        if ($has->fetchColumn()) {
            return false;
        }
        $stmt = $this->conn->prepare("UPDATE appointments SET status = :status WHERE appointment_id = :id");
        return $stmt->execute(array(':status' => $status, ':id' => $appointment_id));
    }

    public function cancelOwn($appointment_id, $patient_id) {
        $query = "UPDATE " . $this->table_name . "
                  SET status = 'Cancelled'
                  WHERE appointment_id = :id
                    AND patient_id = :patient_id
                    AND status = 'Pending'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute(array(
            ':id' => $appointment_id,
            ':patient_id' => $patient_id,
        ));
        return $stmt->rowCount() > 0;
    }

    public function getOpenForRecord($doctor_id) {
        $sql = "SELECT a.appointment_id, a.appointment_date, a.note, a.patient_id, a.doctor_id,
                       p.full_name AS patient_name, d.full_name AS doctor_name
                FROM " . $this->table_name . " a
                JOIN patients p ON p.patient_id = a.patient_id
                JOIN doctors d ON d.doctor_id = a.doctor_id
                WHERE a.status = 'Scheduled'
                AND NOT EXISTS (
                    SELECT 1 FROM medical_records m WHERE m.appointment_id = a.appointment_id
                )";
        $params = array();
        if ($doctor_id !== null) {
            $sql .= " AND a.doctor_id = :doctor_id";
            $params[':doctor_id'] = $doctor_id;
        }
        $sql .= " ORDER BY a.appointment_date DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findScheduled($appointment_id, $doctor_id) {
        $sql = "SELECT a.appointment_id, a.appointment_date, a.note, a.patient_id, a.doctor_id,
                       p.full_name AS patient_name, d.full_name AS doctor_name
                FROM " . $this->table_name . " a
                JOIN patients p ON p.patient_id = a.patient_id
                JOIN doctors d ON d.doctor_id = a.doctor_id
                WHERE a.appointment_id = :id AND a.status = 'Scheduled'";
        $params = array(':id' => $appointment_id);
        if ($doctor_id !== null) {
            $sql .= " AND a.doctor_id = :doctor_id";
            $params[':doctor_id'] = $doctor_id;
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row : null;
    }


    public function syncCompleted() {
        $this->conn->exec(
            "UPDATE appointments a
            SET status = 'Completed'
            WHERE a.status <> 'Cancelled'
            AND a.status <> 'Completed'
            AND EXISTS (
                SELECT 1 FROM medical_records m
                WHERE m.appointment_id = a.appointment_id
            )"
        );
    }

    public function search($q, $status, $doctorId, $patientId, $from = '', $to = '') {
        $this->syncCompleted();
        $sql = "SELECT a.appointment_id, a.patient_id, a.doctor_id, a.appointment_date, a.status, a.note,
                       p.full_name AS patient_name, p.phone AS patient_phone,
                       d.full_name AS doctor_name, d.specialty
                FROM " . $this->table_name . " a
                LEFT JOIN patients p ON p.patient_id = a.patient_id
                LEFT JOIN doctors d ON d.doctor_id = a.doctor_id
                WHERE 1 = 1";
        $params = array();
        if ($patientId) {
            $sql .= " AND a.patient_id = :patient_id";
            $params[':patient_id'] = (int) $patientId;
        }
        if ($doctorId) {
            $sql .= " AND a.doctor_id = :doctor_id";
            $params[':doctor_id'] = (int) $doctorId;
        }
        $allowed = array('Pending', 'Scheduled', 'Completed', 'Cancelled');
        if (in_array($status, $allowed, true)) {
            $sql .= " AND a.status = :status";
            $params[':status'] = $status;
        }
        if ($q !== '') {
            $sql .= " AND (
                COALESCE(p.full_name, '') ILIKE :q
                OR COALESCE(p.phone, '') ILIKE :q
                OR COALESCE(d.full_name, '') ILIKE :q
                OR COALESCE(a.note, '') ILIKE :q
                OR CAST(a.appointment_id AS TEXT) = :exact
            )";
            $params[':q'] = '%' . $q . '%';
            $params[':exact'] = $q;
        }

        if ($from !== '') {
            $sql .= " AND a.appointment_date::date >= :from";
            $params[':from'] = $from;
        }
        if ($to !== '') {
            $sql .= " AND a.appointment_date::date <= :to";
            $params[':to'] = $to;
        }

        $sql .= " ORDER BY a.appointment_date DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateRow($id, $doctorId, $appointmentDate, $note, $status) {
        $cur = $this->conn->prepare("SELECT status FROM appointments WHERE appointment_id = :id");
        $cur->execute(array(':id' => $id));
        $current = $cur->fetchColumn();
        if ($current === false || $current === 'Cancelled') {
            return false;
        }
        $has = $this->conn->prepare("SELECT 1 FROM medical_records WHERE appointment_id = :id");
        $has->execute(array(':id' => $id));
        if ($has->fetchColumn()) {
            $status = 'Completed';
        } elseif (!in_array($status, array('Scheduled', 'Cancelled'), true)) {
            return false;
        }
        $stmt = $this->conn->prepare(
            "UPDATE appointments
            SET doctor_id = :doctor_id, appointment_date = :appointment_date, note = :note, status = :status
            WHERE appointment_id = :id"
        );
        return $stmt->execute(array(
            ':doctor_id' => $doctorId,
            ':appointment_date' => $appointmentDate,
            ':note' => $note,
            ':status' => $status,
            ':id' => $id,
        ));
    }
}
?>
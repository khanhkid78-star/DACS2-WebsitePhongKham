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
        $query = "UPDATE " . $this->table_name . " SET status = :status WHERE appointment_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":id", $appointment_id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
?>
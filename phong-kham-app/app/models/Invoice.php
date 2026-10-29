<?php
class Invoice {
    private $conn;
    private $table_name = "invoices";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAllWithPatient() {
        $query = "SELECT i.*, p.full_name AS patient_name 
                  FROM " . $this->table_name . " i 
                  LEFT JOIN patients p ON i.patient_id = p.patient_id 
                  ORDER BY i.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
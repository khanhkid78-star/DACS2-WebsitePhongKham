<?php
class User {
    private $conn;
    private $table_name = "users";

    public function __construct($db) {
        $this->conn =$db;
    }

    public function findByUsername($username) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE username = :username LIMIT 1";
        $stmt =$this->conn->prepare($query);$stmt->bindParam(":username", $username);$stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function registerPatient($data) {
        $this->conn->beginTransaction();
        try {
            $patient = $this->conn->prepare(
                "INSERT INTO patients
                    (full_name, date_of_birth, gender, phone, email, address, cccd, bhyt)
                VALUES
                    (:full_name, :date_of_birth, :gender, :phone, :email, :address, :cccd, :bhyt)
                RETURNING patient_id"
            );
            $patient->execute(array(
                ':full_name' => $data['full_name'],
                ':date_of_birth' => $data['date_of_birth'],
                ':gender' => $data['gender'],
                ':phone' => $data['phone'],
                ':email' => $data['email'],
                ':address' => $data['address'],
                ':cccd' => $data['cccd'],
                ':bhyt' => $data['bhyt'],
            ));
            $patient_id = (int) $patient->fetchColumn();

            $hash = password_hash($data['password'], PASSWORD_DEFAULT);
            $user = $this->conn->prepare(
                "INSERT INTO users (username, password, role, patient_id)
                VALUES (:username, :password, 'patient', :patient_id)"
            );
            $user->execute(array(
                ':username' => $data['username'],
                ':password' => $hash,
                ':patient_id' => $patient_id,
            ));
            $this->conn->commit();
            return $patient_id;
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }
}
?>
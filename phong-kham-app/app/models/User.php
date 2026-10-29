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
}
?>
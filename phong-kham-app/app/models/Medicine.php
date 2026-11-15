<?php
class Medicine {
    private $conn;
    private $table_name = "medicines";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY medicine_id ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id) {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . $this->table_name . " WHERE medicine_id = :id"
        );
        $stmt->execute(array(':id' => $id));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row : null;
    }

    public function create($name, $unit, $price, $description) {
        $stmt = $this->conn->prepare(
            "INSERT INTO " . $this->table_name . " (name, unit, price, description)
             VALUES (:name, :unit, :price, :description)"
        );
        return $stmt->execute(array(
            ':name' => $name,
            ':unit' => $unit,
            ':price' => $price,
            ':description' => $description,
        ));
    }

    public function update($id, $name, $unit, $price, $description) {
        $stmt = $this->conn->prepare(
            "UPDATE " . $this->table_name . "
             SET name = :name, unit = :unit, price = :price, description = :description
             WHERE medicine_id = :id"
        );
        $stmt->execute(array(
            ':name' => $name,
            ':unit' => $unit,
            ':price' => $price,
            ':description' => $description,
            ':id' => $id,
        ));
        return $stmt->rowCount() > 0;
    }

    public function delete($id) {
        $stmt = $this->conn->prepare(
            "DELETE FROM " . $this->table_name . " WHERE medicine_id = :id"
        );
        $stmt->execute(array(':id' => $id));
        return $stmt->rowCount() > 0;
    }
}
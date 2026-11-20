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

    public function units() {
        $stmt = $this->conn->query(
            "SELECT DISTINCT unit FROM medicines WHERE unit <> '' ORDER BY unit"
        );
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function search($q, $unit, $min = '', $max = '', $sort = '') {
        $sql = "SELECT * FROM medicines WHERE 1=1";
        $params = array();
        if ($q !== '') {
            $digits = preg_replace('/\D+/', '', $q);
            $sql .= " AND (
                name ILIKE :q
                OR COALESCE(description, '') ILIKE :q3
                OR CAST(medicine_id AS TEXT) ILIKE :q4
                OR CAST(price AS TEXT) ILIKE :q5";
            $like = '%' . $q . '%';
            $params[':q'] = $like;
            $params[':q3'] = $like;
            $params[':q4'] = $like;
            $params[':q5'] = '%' . ($digits !== '' ? $digits : $q) . '%';
            if ($digits !== '') {
                $sql .= " OR price = :exact";
                $params[':exact'] = (float) $digits;
            }
            $sql .= ")";
        }
        if ($min !== '' && is_numeric($min)) {
            $sql .= " AND price >= :pmin";
            $params[':pmin'] = (float) $min;
        }
        if ($max !== '' && is_numeric($max)) {
            $sql .= " AND price <= :pmax";
            $params[':pmax'] = (float) $max;
        }
        
        if ($sort === 'price_asc') {
            $sql .= " ORDER BY price ASC, medicine_id ASC";
        } elseif ($sort === 'price_desc') {
            $sql .= " ORDER BY price DESC, medicine_id ASC";
        } else {
            $sql .= " ORDER BY medicine_id ASC";
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
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
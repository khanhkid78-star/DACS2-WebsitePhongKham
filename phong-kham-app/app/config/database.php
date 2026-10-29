<?php
class Database {
    private $host = "aws-0-eu-west-1.pooler.supabase.com";
    private $port = "5432";
    private $db_name = "postgres";
    private $username = "postgres.ldlcbyjmcbxuagvhuear";
    private $password = "FAN MAGNA LUNA";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $dsn = "pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo "Lỗi kết nối CSDL: " . $e->getMessage();
        }
        return $this->conn;
    }
}
?>
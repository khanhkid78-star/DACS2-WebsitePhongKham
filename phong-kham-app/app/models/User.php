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

        public function unlinked($table, $idColumn) {
        $allowed = array(
            'doctors' => 'doctor_id',
            'nurses' => 'nurse_id',
            'receptionists' => 'receptionist_id',
        );
        if (!isset($allowed[$table]) || $allowed[$table] !== $idColumn) {
            return array();
        }
        $sql = "SELECT t." . $idColumn . " AS id, t.full_name
                FROM " . $table . " t
                LEFT JOIN users u ON u." . $idColumn . " = t." . $idColumn . "
                WHERE u.user_id IS NULL
                ORDER BY t.full_name";
        return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createStaff($username, $password, $role, $linkId) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            if ($role === 'admin') {
                $stmt = $this->conn->prepare(
                    "INSERT INTO users (username, password, role)
                     VALUES (:username, :password, 'admin')"
                );
                $stmt->execute(array(
                    ':username' => $username,
                    ':password' => $hash,
                ));
                return '';
            }

            $map = array(
                'doctor' => array('doctors', 'doctor_id'),
                'nurse' => array('nurses', 'nurse_id'),
                'receptionist' => array('receptionists', 'receptionist_id'),
            );
            if (!isset($map[$role]) || (int) $linkId < 1) {
                return 'Chọn đúng hồ sơ nhân sự.';
            }
            $rows = $this->unlinked($map[$role][0], $map[$role][1]);
            $found = false;
            foreach ($rows as $row) {
                if ((int) $row['id'] === (int) $linkId) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                return 'Hồ sơ này không có, hoặc đã có tài khoản.';
            }
            $column = $map[$role][1];
            $stmt = $this->conn->prepare(
                "INSERT INTO users (username, password, role, " . $column . ")
                 VALUES (:username, :password, :role, :link_id)"
            );
            $stmt->execute(array(
                ':username' => $username,
                ':password' => $hash,
                ':role' => $role,
                ':link_id' => (int) $linkId,
            ));
            return '';
        } catch (PDOException $e) {
            if ($e->getCode() === '23505') {
                return 'Tên đăng nhập đã tồn tại.';
            }
            return 'Không tạo được tài khoản.';
        }
    }

    public function resetPassword($username, $password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare(
            "UPDATE users SET password = :password WHERE username = :username"
        );
        $stmt->execute(array(
            ':password' => $hash,
            ':username' => $username,
        ));
        return $stmt->rowCount() > 0;
    }

    public function deleteById($user_id) {
        $find = $this->conn->prepare("SELECT role FROM users WHERE user_id = :id");
        $find->execute(array(':id' => $user_id));
        $row = $find->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return 'Không thấy tài khoản.';
        }
        if ($row['role'] === 'patient') {
            return 'Không xóa tài khoản bệnh nhân. Hãy đặt lại mật khẩu nếu họ quên.';
        }
        $stmt = $this->conn->prepare("DELETE FROM users WHERE user_id = :id");
        $stmt->execute(array(':id' => $user_id));
        return $stmt->rowCount() > 0 ? '' : 'Không xóa được.';
    }
}
?>
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

    public function updateLogin($userId, $username, $newPassword, $role) {
        $current = $this->findById($userId);
        if (!$current) {
            return 'Không thấy tài khoản.';
        }
        if ($current['role'] === 'patient') {
            return 'Không sửa tài khoản bệnh nhân ở đây.';
        }
        $allowed = array('admin', 'doctor', 'nurse', 'receptionist');
        $username = trim($username);
        if ($username === '' || !in_array($role, $allowed, true)) {
            return 'Nhập tên đăng nhập và chọn vai trò.';
        }
        if ($newPassword !== '' && strlen($newPassword) < 5) {
            return 'Mật khẩu mới tối thiểu 5 ký tự.';
        }
        $taken = $this->conn->prepare(
            "SELECT user_id FROM users WHERE username = :username AND user_id <> :id"
        );
        $taken->execute(array(
            ':username' => $username,
            ':id' => (int) $userId,
        ));
        if ($taken->fetch(PDO::FETCH_ASSOC)) {
            return 'Tên đăng nhập đã tồn tại.';
        }
        if ($newPassword !== '') {
            $stmt = $this->conn->prepare(
                "UPDATE users
                 SET username = :username, password = :password, role = :role
                 WHERE user_id = :id"
            );
            $stmt->execute(array(
                ':username' => $username,
                ':password' => password_hash($newPassword, PASSWORD_DEFAULT),
                ':role' => $role,
                ':id' => (int) $userId,
            ));
        } else {
            $stmt = $this->conn->prepare(
                "UPDATE users SET username = :username, role = :role WHERE user_id = :id"
            );
            $stmt->execute(array(
                ':username' => $username,
                ':role' => $role,
                ':id' => (int) $userId,
            ));
        }
        return '';
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

    public function findById($user_id) {
        $stmt = $this->conn->prepare(
            "SELECT user_id, username, role, doctor_id, nurse_id, receptionist_id, patient_id
             FROM users WHERE user_id = :id"
        );
        $stmt->execute(array(':id' => $user_id));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row : null;
    }

    public function accountBundle($user_id) {
        $user = $this->findById($user_id);
        if (!$user) {
            return null;
        }
        $kind = 'admin';
        $profile = null;
        $map = array(
            'patient_id' => array('patient', 'patients', 'patient_id'),
            'doctor_id' => array('doctor', 'doctors', 'doctor_id'),
            'nurse_id' => array('nurse', 'nurses', 'nurse_id'),
            'receptionist_id' => array('receptionist', 'receptionists', 'receptionist_id'),
        );
        foreach ($map as $column => $meta) {
            if (empty($user[$column])) {
                continue;
            }
            $kind = $meta[0];
            $stmt = $this->conn->prepare(
                "SELECT * FROM " . $meta[1] . " WHERE " . $meta[2] . " = :id"
            );
            $stmt->execute(array(':id' => (int) $user[$column]));
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            break;
        }
        return array('user' => $user, 'kind' => $kind, 'profile' => $profile);
    }

    public function updateAccount($user_id, $username, $newPassword, $input) {
        $bundle = $this->accountBundle($user_id);
        if (!$bundle) {
            return 'Không thấy tài khoản.';
        }
        $username = trim($username);
        if ($username === '') {
            return 'Nhập tên đăng nhập.';
        }
        if ($newPassword !== '' && strlen($newPassword) < 5) {
            return 'Mật khẩu mới tối thiểu 5 ký tự.';
        }

        $taken = $this->conn->prepare(
            "SELECT user_id FROM users WHERE username = :username AND user_id <> :id"
        );
        $taken->execute(array(':username' => $username, ':id' => $user_id));
        if ($taken->fetch(PDO::FETCH_ASSOC)) {
            return 'Tên đăng nhập đã tồn tại.';
        }

        $this->conn->beginTransaction();
        try {
            if ($newPassword !== '') {
                $stmt = $this->conn->prepare(
                    "UPDATE users SET username = :username, password = :password WHERE user_id = :id"
                );
                $stmt->execute(array(
                    ':username' => $username,
                    ':password' => password_hash($newPassword, PASSWORD_DEFAULT),
                    ':id' => $user_id,
                ));
            } else {
                $stmt = $this->conn->prepare(
                    "UPDATE users SET username = :username WHERE user_id = :id"
                );
                $stmt->execute(array(
                    ':username' => $username,
                    ':id' => $user_id,
                ));
            }

            $this->conn->commit();
            return '';
        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            if ($e->getCode() === '23505') {
                return 'Tên đăng nhập, số điện thoại, email hoặc CCCD đã tồn tại.';
            }
            return 'Không lưu được tài khoản.';
        }
    }

    private function checkProfile($kind, $input) {
        $gender = trim($input['gender'] ?? '');
        if (!in_array($gender, array('Nam', 'Nữ', 'Khác'), true)) {
            return 'Giới tính không hợp lệ.';
        }
        if ($kind === 'patient') {
            foreach (array('full_name', 'date_of_birth', 'phone', 'email', 'address', 'cccd', 'bhyt') as $key) {
                if (trim($input[$key] ?? '') === '') {
                    return 'Điền đủ thông tin bệnh nhân.';
                }
            }
            if (!preg_match('/^\d{10}$/', trim($input['phone']))) {
                return 'Số điện thoại phải đúng 10 chữ số.';
            }
            if (!preg_match('/^\d{12}$/', trim($input['cccd']))) {
                return 'CCCD phải đúng 12 chữ số.';
            }
            if (!preg_match('/^\d{10,15}$/', trim($input['bhyt']))) {
                return 'Số BHYT phải từ 10 đến 15 chữ số.';
            }
        }
        if ($kind === 'doctor') {
            foreach (array('full_name', 'specialty', 'phone', 'email', 'degree') as $key) {
                if (trim($input[$key] ?? '') === '') {
                    return 'Điền đủ hồ sơ bác sĩ.';
                }
            }
        }
        if ($kind === 'receptionist' && (trim($input['full_name'] ?? '') === '' || trim($input['phone'] ?? '') === '')) {
            return 'Điền đủ họ tên và số điện thoại.';
        }
        return '';
    }

    private function profileFields($kind, $input) {
        $dob = trim($input['date_of_birth'] ?? '');
        $dob = $dob === '' ? null : $dob;
        $gender = trim($input['gender'] ?? '');
        if ($kind === 'patient') {
            return array(
                'full_name' => trim($input['full_name']),
                'date_of_birth' => $dob,
                'gender' => $gender,
                'phone' => trim($input['phone']),
                'email' => trim($input['email']),
                'address' => trim($input['address']),
                'cccd' => trim($input['cccd']),
                'bhyt' => trim($input['bhyt']),
            );
        }
        if ($kind === 'doctor' || $kind === 'nurse') {
            return array(
                'full_name' => trim($input['full_name'] ?? ''),
                'gender' => $gender,
                'date_of_birth' => $dob,
                'specialty' => trim($input['specialty'] ?? ''),
                'phone' => trim($input['phone'] ?? ''),
                'email' => trim($input['email'] ?? ''),
                'degree' => trim($input['degree'] ?? ''),
            );
        }
        return array(
            'full_name' => trim($input['full_name'] ?? ''),
            'gender' => $gender,
            'date_of_birth' => $dob,
            'phone' => trim($input['phone'] ?? ''),
            'email' => trim($input['email'] ?? ''),
            'address' => trim($input['address'] ?? ''),
            'shift' => trim($input['shift'] ?? ''),
        );
    }

    public function updateProfile($user_id, $input, $photoValue, $clearPhoto) {
        $bundle = $this->accountBundle($user_id);
        if (!$bundle || empty($bundle['profile']) || $bundle['kind'] === 'admin') {
            return 'Không có hồ sơ để cập nhật.';
        }
        $kind = $bundle['kind'];
        $error = $this->checkProfile($kind, $input);
        if ($error !== '') {
            return $error;
        }
        $fields = $this->profileFields($kind, $input);
        if ($kind === 'patient' && $photoValue !== null) {
            $fields['photo'] = $photoValue;
        } elseif ($kind === 'patient' && $clearPhoto) {
            $fields['photo'] = null;
        }
        $idColumn = array(
            'patient' => 'patient_id',
            'doctor' => 'doctor_id',
            'nurse' => 'nurse_id',
            'receptionist' => 'receptionist_id',
        );
        $table = array(
            'patient' => 'patients',
            'doctor' => 'doctors',
            'nurse' => 'nurses',
            'receptionist' => 'receptionists',
        );
        $sets = array();
        $params = array(':id' => (int) $bundle['profile'][$idColumn[$kind]]);
        foreach ($fields as $column => $value) {
            $sets[] = $column . ' = :' . $column;
            $params[':' . $column] = $value;
        }
        $sql = 'UPDATE ' . $table[$kind] . ' SET ' . implode(', ', $sets)
            . ' WHERE ' . $idColumn[$kind] . ' = :id';
        $this->conn->prepare($sql)->execute($params);
        return '';
    }

    public function deleteSelf($user_id) {
        $user = $this->findById($user_id);
        if (!$user) {
            return 'Không thấy tài khoản.';
        }
        if ($user['role'] === 'admin') {
            $count = (int) $this->conn->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
            if ($count <= 1) {
                return 'Không xóa quản trị cuối cùng.';
            }
        }
        $this->conn->beginTransaction();
        try {
            $this->conn->prepare('DELETE FROM notifications WHERE user_id = :id')->execute(array(':id' => $user_id));
            $stmt = $this->conn->prepare('DELETE FROM users WHERE user_id = :id');
            $stmt->execute(array(':id' => $user_id));
            $this->conn->commit();
            return $stmt->rowCount() > 0 ? '' : 'Không xóa được tài khoản.';
        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return 'Không xóa được tài khoản.';
        }
    }
}
?>
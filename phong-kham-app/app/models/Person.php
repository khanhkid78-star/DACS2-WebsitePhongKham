<?php
class Person {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public static function spec($kind) {
        $all = array(
            'doctor' => array(
                'table' => 'doctors',
                'pk' => 'doctor_id',
                'fields' => array('full_name', 'gender', 'date_of_birth', 'specialty', 'phone', 'email', 'degree'),
                'required' => array('full_name', 'gender', 'specialty', 'phone', 'email', 'degree'),
                'search' => array('full_name', 'phone', 'email', 'specialty', 'degree'),
            ),
            'nurse' => array(
                'table' => 'nurses',
                'pk' => 'nurse_id',
                'fields' => array('full_name', 'gender', 'date_of_birth', 'specialty', 'phone', 'email', 'degree'),
                'required' => array('full_name', 'gender'),
                'search' => array('full_name', 'phone', 'email', 'specialty', 'degree'),
            ),
            'receptionist' => array(
                'table' => 'receptionists',
                'pk' => 'receptionist_id',
                'fields' => array('full_name', 'gender', 'date_of_birth', 'phone', 'email', 'address', 'shift', 'status'),
                'required' => array('full_name', 'gender', 'phone'),
                'search' => array('full_name', 'phone', 'email', 'address', 'shift'),
            ),
            'patient' => array(
                'table' => 'patients',
                'pk' => 'patient_id',
                'fields' => array('full_name', 'date_of_birth', 'gender', 'phone', 'email', 'address', 'cccd', 'bhyt'),
                'required' => array('full_name', 'date_of_birth', 'gender', 'phone', 'email', 'address', 'cccd', 'bhyt'),
                'search' => array('full_name', 'phone', 'email', 'address', 'cccd', 'bhyt'),
            ),
        );
        return isset($all[$kind]) ? $all[$kind] : null;
    }

    public function choices($kind, $column) {
        $spec = self::spec($kind);
        if (!$spec || !in_array($column, $spec['fields'], true)) {
            return array();
        }
        $sql = 'SELECT DISTINCT ' . $column . ' AS v FROM ' . $spec['table']
            . ' WHERE ' . $column . " IS NOT NULL AND " . $column . " <> '' ORDER BY 1";
        return $this->conn->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    }

    public function search($kind, $q, $filters) {
        $spec = self::spec($kind);
        $pk = $spec['pk'];
        $sql = 'SELECT ' . $pk . ' AS id, full_name, gender, date_of_birth, phone,'
            . ' (photo IS NOT NULL AND photo <> \'\') AS has_photo';
        foreach (array('specialty', 'email', 'degree', 'shift', 'status', 'cccd') as $col) {
            if (in_array($col, $spec['fields'], true)) {
                $sql .= ', ' . $col;
            }
        }
        $sql .= ' FROM ' . $spec['table'] . ' WHERE 1 = 1';
        $params = array();
        if ($q !== '') {
            $parts = array();
            foreach ($spec['search'] as $i => $col) {
                $key = ':q' . $i;
                $parts[] = 'COALESCE(' . $col . ", '') ILIKE " . $key;
                $params[$key] = '%' . $q . '%';
            }
            $parts[] = 'CAST(' . $pk . ' AS TEXT) = :exact';
            $params[':exact'] = $q;
            $sql .= ' AND (' . implode(' OR ', $parts) . ')';
        }
        if (!empty($filters['gender'])) {
            $sql .= ' AND gender = :gender';
            $params[':gender'] = $filters['gender'];
        }
        if (!empty($filters['specialty']) && in_array('specialty', $spec['fields'], true)) {
            $sql .= ' AND specialty = :specialty';
            $params[':specialty'] = $filters['specialty'];
        }
        if (!empty($filters['shift']) && in_array('shift', $spec['fields'], true)) {
            $sql .= ' AND shift = :shift';
            $params[':shift'] = $filters['shift'];
        }
        if (!empty($filters['status']) && in_array('status', $spec['fields'], true)) {
            $sql .= ' AND status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['from'])) {
            $sql .= ' AND date_of_birth >= :dfrom';
            $params[':dfrom'] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $sql .= ' AND date_of_birth <= :dto';
            $params[':dto'] = $filters['to'];
        }
        $sql .= ' ORDER BY ' . $pk . ' DESC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($kind, $id) {
        $spec = self::spec($kind);
        $cols = implode(', ', $spec['fields']);
        $sql = 'SELECT ' . $spec['pk'] . ' AS id, ' . $cols . ', created_at,'
            . ' (photo IS NOT NULL AND photo <> \'\') AS has_photo'
            . ' FROM ' . $spec['table'] . ' WHERE ' . $spec['pk'] . ' = :id';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(array(':id' => (int) $id));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row : null;
    }

    public function readPhoto($kind, $id) {
        $spec = self::spec($kind);
        $stmt = $this->conn->prepare('SELECT photo FROM ' . $spec['table'] . ' WHERE ' . $spec['pk'] . ' = :id');
        $stmt->execute(array(':id' => (int) $id));
        $raw = (string) $stmt->fetchColumn();
        $cut = strpos($raw, '|');
        if ($raw === '' || $cut === false) {
            return null;
        }
        return array(
            'mime' => substr($raw, 0, $cut),
            'bytes' => base64_decode(substr($raw, $cut + 1)),
        );
    }

    public function save($kind, $id, $input, $photoValue, $clearPhoto) {
        $spec = self::spec($kind);
        $data = array();
        foreach ($spec['fields'] as $field) {
            $value = trim((string) (isset($input[$field]) ? $input[$field] : ''));
            if (in_array($field, $spec['required'], true) && $value === '') {
                return 'Điền đủ các ô bắt buộc.';
            }
            $data[$field] = ($field === 'date_of_birth' && $value === '') ? null : $value;
        }
        if ($data['gender'] !== '' && !in_array($data['gender'], array('Nam', 'Nữ', 'Khác'), true)) {
            return 'Giới tính không hợp lệ.';
        }
        if ($kind === 'patient') {
            if (!preg_match('/^\d{10}$/', $data['phone'])) {
                return 'Số điện thoại phải đúng 10 chữ số.';
            }
            if (!preg_match('/^\d{12}$/', $data['cccd'])) {
                return 'CCCD phải đúng 12 chữ số.';
            }
            if (!preg_match('/^\d{10,15}$/', $data['bhyt'])) {
                return 'Số BHYT phải từ 10 đến 15 chữ số.';
            }
        }
        if ($kind === 'receptionist' && $data['status'] === '') {
            $data['status'] = 'ACTIVE';
        }
        if ($photoValue !== null) {
            $data['photo'] = $photoValue;
        } elseif ($clearPhoto && $id > 0) {
            $data['photo'] = null;
        }
        $params = array();
        foreach ($data as $key => $value) {
            $params[':' . $key] = $value;
        }
        if ($id > 0) {
            $sets = array();
            foreach ($data as $key => $value) {
                $sets[] = $key . ' = :' . $key;
            }
            $params[':id'] = $id;
            $sql = 'UPDATE ' . $spec['table'] . ' SET ' . implode(', ', $sets) . ' WHERE ' . $spec['pk'] . ' = :id';
        } else {
            $cols = array_keys($data);
            $holders = array();
            foreach ($cols as $col) {
                $holders[] = ':' . $col;
            }
            $sql = 'INSERT INTO ' . $spec['table'] . ' (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $holders) . ')';
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return '';
    }

    public function delete($kind, $id) {
        $spec = self::spec($kind);
        try {
            $stmt = $this->conn->prepare('DELETE FROM ' . $spec['table'] . ' WHERE ' . $spec['pk'] . ' = :id');
            $stmt->execute(array(':id' => (int) $id));
            return $stmt->rowCount() > 0 ? '' : 'Không tìm thấy hồ sơ.';
        } catch (PDOException $e) {
            return 'Không xóa được vì còn tài khoản, lịch hẹn, bệnh án hoặc hóa đơn gắn với hồ sơ này.';
        }
    }
}
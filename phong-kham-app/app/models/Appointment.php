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
        if (!in_array($status, array('Scheduled', 'Cancelled'), true)) {
            return false;
        }
        $cur = $this->conn->prepare("SELECT status FROM appointments WHERE appointment_id = :id");
        $cur->execute(array(':id' => $appointment_id));
        $current = $cur->fetchColumn();
        if ($current === false || $current === 'Cancelled' || $current === 'Completed' || $current === 'NoShow') {
            return false;
        }
        $has = $this->conn->prepare("SELECT 1 FROM medical_records WHERE appointment_id = :id");
        $has->execute(array(':id' => $appointment_id));
        if ($has->fetchColumn()) {
            return false;
        }
        $stmt = $this->conn->prepare("UPDATE appointments SET status = :status WHERE appointment_id = :id");
        return $stmt->execute(array(':status' => $status, ':id' => $appointment_id));
    }

    public function cancelOwn($appointment_id, $patient_id) {
        $query = "UPDATE " . $this->table_name . "
                  SET status = 'Cancelled'
                  WHERE appointment_id = :id
                    AND patient_id = :patient_id
                    AND status = 'Pending'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute(array(
            ':id' => $appointment_id,
            ':patient_id' => $patient_id,
        ));
        return $stmt->rowCount() > 0;
    }

    public function getOpenForRecord($doctor_id) {
        $sql = "SELECT a.appointment_id, a.appointment_date, a.note, a.patient_id, a.doctor_id,
                       p.full_name AS patient_name, d.full_name AS doctor_name
                FROM " . $this->table_name . " a
                JOIN patients p ON p.patient_id = a.patient_id
                JOIN doctors d ON d.doctor_id = a.doctor_id
                WHERE a.status = 'Scheduled'
                AND (a.appointment_date AT TIME ZONE 'Asia/Ho_Chi_Minh') > NOW() - INTERVAL '2 hours'
                AND NOT EXISTS (
                    SELECT 1 FROM medical_records m WHERE m.appointment_id = a.appointment_id
                )";
        $params = array();
        if ($doctor_id !== null) {
            $sql .= " AND a.doctor_id = :doctor_id";
            $params[':doctor_id'] = $doctor_id;
        }
        $sql .= " ORDER BY a.appointment_date DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findScheduled($appointment_id, $doctor_id) {
        $sql = "SELECT a.appointment_id, a.appointment_date, a.note, a.patient_id, a.doctor_id,
                       p.full_name AS patient_name, d.full_name AS doctor_name
                FROM " . $this->table_name . " a
                JOIN patients p ON p.patient_id = a.patient_id
                JOIN doctors d ON d.doctor_id = a.doctor_id
                WHERE a.appointment_id = :id AND a.status = 'Scheduled'
                AND (a.appointment_date AT TIME ZONE 'Asia/Ho_Chi_Minh') > NOW() - INTERVAL '2 hours'";
        $params = array(':id' => $appointment_id);
        if ($doctor_id !== null) {
            $sql .= " AND a.doctor_id = :doctor_id";
            $params[':doctor_id'] = $doctor_id;
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row : null;
    }


    public function syncCompleted() {
        $this->conn->exec(
            "UPDATE appointments a
            SET status = 'Completed'
            WHERE a.status <> 'Cancelled'
            AND a.status <> 'NoShow'
            AND a.status <> 'Completed'
            AND EXISTS (
                SELECT 1 FROM medical_records m
                WHERE m.appointment_id = a.appointment_id
            )"
        );
    }

    public function search($q, $status, $doctorId, $patientId, $from = '', $to = '') {
        $this->syncCompleted();
        $sql = "SELECT a.appointment_id, a.patient_id, a.doctor_id, a.appointment_date, a.status, a.note,
                       p.full_name AS patient_name, p.phone AS patient_phone,
                       d.full_name AS doctor_name, d.specialty
                FROM " . $this->table_name . " a
                LEFT JOIN patients p ON p.patient_id = a.patient_id
                LEFT JOIN doctors d ON d.doctor_id = a.doctor_id
                WHERE 1 = 1";
        $params = array();
        if ($patientId) {
            $sql .= " AND a.patient_id = :patient_id";
            $params[':patient_id'] = (int) $patientId;
        }
        if ($doctorId) {
            $sql .= " AND a.doctor_id = :doctor_id";
            $params[':doctor_id'] = (int) $doctorId;
        }
        $allowed = array('Pending', 'Scheduled', 'Completed', 'Cancelled', 'NoShow');
        if (in_array($status, $allowed, true)) {
            $sql .= " AND a.status = :status";
            $params[':status'] = $status;
        }
        if ($q !== '') {
            $sql .= " AND (
                COALESCE(p.full_name, '') ILIKE :q
                OR COALESCE(p.phone, '') ILIKE :q
                OR COALESCE(d.full_name, '') ILIKE :q
                OR COALESCE(a.note,
                    ((a.status = 'Scheduled')
                    AND (a.appointment_date AT TIME ZONE 'Asia/Ho_Chi_Minh') <= NOW()
                    AND (a.appointment_date AT TIME ZONE 'Asia/Ho_Chi_Minh') > NOW() - INTERVAL '2 hours') AS in_grace, '') ILIKE :q
                OR CAST(a.appointment_id AS TEXT) = :exact
            )";
            $params[':q'] = '%' . $q . '%';
            $params[':exact'] = $q;
        }

        if ($from !== '') {
            $sql .= " AND a.appointment_date::date >= :from";
            $params[':from'] = $from;
        }
        if ($to !== '') {
            $sql .= " AND a.appointment_date::date <= :to";
            $params[':to'] = $to;
        }

        $sql .= " ORDER BY a.appointment_date DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateRow($id, $doctorId, $appointmentDate, $note, $status) {
        $cur = $this->conn->prepare("SELECT status FROM appointments WHERE appointment_id = :id");
        $cur->execute(array(':id' => $id));
        $current = $cur->fetchColumn();

        if ($current === false || $current === 'Cancelled' || $current === 'NoShow') {
            return false;
        }
        $open = $this->conn->prepare(
            "SELECT 1 FROM appointments
             WHERE appointment_id = :id
               AND (appointment_date AT TIME ZONE 'Asia/Ho_Chi_Minh') > NOW() - INTERVAL '2 hours'"
        );
        $open->execute(array(':id' => $id));
        if (!$open->fetchColumn() && $current !== 'Completed') {
            return false;
        }
        
        $has = $this->conn->prepare("SELECT 1 FROM medical_records WHERE appointment_id = :id");
        $has->execute(array(':id' => $id));
        if ($has->fetchColumn()) {
            $status = 'Completed';
        } elseif (!in_array($status, array('Scheduled', 'Cancelled'), true)) {
            return false;
        }
        $stmt = $this->conn->prepare(
            "UPDATE appointments
            SET doctor_id = :doctor_id, appointment_date = :appointment_date, note = :note, status = :status
            WHERE appointment_id = :id"
        );
        return $stmt->execute(array(
            ':doctor_id' => $doctorId,
            ':appointment_date' => $appointmentDate,
            ':note' => $note,
            ':status' => $status,
            ':id' => $id,
        ));
    }

    public function sweep() {
        require_once __DIR__ . '/../helpers/Notify.php';
        $mails = array();
        $base = "SELECT a.appointment_id, a.patient_id, a.appointment_date, a.note,
                        p.full_name AS patient_name, d.full_name AS doctor_name,
                        d.specialty, d.degree
                 FROM appointments a
                 JOIN patients p ON p.patient_id = a.patient_id
                 JOIN doctors d ON d.doctor_id = a.doctor_id";

        $remind = $this->conn->query(
            "UPDATE appointments a
             SET reminded_at = NOW()
             WHERE a.status = 'Scheduled'
               AND a.reminded_at IS NULL
               AND (a.appointment_date AT TIME ZONE 'Asia/Ho_Chi_Minh') > NOW()
               AND (a.appointment_date AT TIME ZONE 'Asia/Ho_Chi_Minh') <= NOW() + INTERVAL '1 day'
             RETURNING a.appointment_id"
        );
        $ids = $remind->fetchAll(PDO::FETCH_COLUMN);
        if ($ids) {
            $rows = $this->conn->query($base . " WHERE a.appointment_id IN (" . implode(',', array_map('intval', $ids)) . ")");
            foreach ($rows as $row) {
                $when = date('H:i d/m/Y', strtotime($row['appointment_date']));
                Notify::send($this->conn, 'patient', (int) $row['patient_id'], 'Nhắc lịch khám ngày mai', 'Lịch #' . $row['appointment_id'] . ' với BS. ' . $row['doctor_name'] . ' lúc ' . $when . '.', '/index.php?action=my_appointments');
                $mails[] = array(
                    'patient_id' => (int) $row['patient_id'],
                    'subject' => 'Nhắc lịch khám ' . $when . ' - Phòng Khám Hưng Định',
                    'letter' => "Kính gửi " . $row['patient_name'] . ",\n\n"
                        . "Phòng Khám Đa Khoa Hưng Định nhắc quý khách có lịch khám trong 24 giờ tới.\n\n"
                        . "Mã lịch: #" . $row['appointment_id'] . "\n"
                        . "Bác sĩ: " . $row['degree'] . " " . $row['doctor_name'] . "\n"
                        . "Chuyên khoa: " . $row['specialty'] . "\n"
                        . "Giờ hẹn: " . $when . "\n"
                        . "Lý do khám: " . $row['note'] . "\n\n"
                        . "Quý khách vui lòng đến trước 10 phút, mang theo CCCD và thẻ BHYT nếu có. "
                        . "Nếu chưa đến trong vòng 2 giờ sau giờ hẹn, lịch sẽ chuyển sang Trễ hẹn và không còn hiệu lực.\n\n"
                        . "Địa chỉ: 128 Nguyễn Văn Trỗi, Phú Nhuận, TP. Hồ Chí Minh\n"
                        . "Hotline: 0931 999 309\n\n"
                        . "Trân trọng,\nPhòng Khám Đa Khoa Hưng Định",
                );
            }
        }

        $late = $this->conn->query(
            "UPDATE appointments a
             SET late_notice_at = NOW()
             WHERE a.status = 'Scheduled'
               AND a.late_notice_at IS NULL
               AND (a.appointment_date AT TIME ZONE 'Asia/Ho_Chi_Minh') <= NOW()
               AND (a.appointment_date AT TIME ZONE 'Asia/Ho_Chi_Minh') > NOW() - INTERVAL '2 hours'
             RETURNING a.appointment_id"
        );
        $ids = $late->fetchAll(PDO::FETCH_COLUMN);
        if ($ids) {
            $rows = $this->conn->query($base . " WHERE a.appointment_id IN (" . implode(',', array_map('intval', $ids)) . ")");
            foreach ($rows as $row) {
                $when = date('H:i d/m/Y', strtotime($row['appointment_date']));
                Notify::send($this->conn, 'patient', (int) $row['patient_id'], 'Đã đến giờ hẹn', 'Lịch #' . $row['appointment_id'] . ' đã đến giờ. Phòng khám giữ chỗ thêm 2 tiếng.', '/index.php?action=my_appointments');
                $mails[] = array(
                    'patient_id' => (int) $row['patient_id'],
                    'subject' => 'Đã đến giờ hẹn #' . $row['appointment_id'] . ' - Phòng Khám Hưng Định',
                    'letter' => "Kính gửi " . $row['patient_name'] . ",\n\n"
                        . "Đã đến giờ hẹn " . $when . " với BS. " . $row['doctor_name'] . " (" . $row['specialty'] . ").\n\n"
                        . "Phòng khám vẫn giữ chỗ trong 2 giờ kể từ giờ hẹn. Trong khoảng này bác sĩ vẫn lập được bệnh án. "
                        . "Quá 2 giờ, lịch chuyển sang Trễ hẹn và không thể dùng lịch này để khám.\n\n"
                        . "Nếu quý khách đang trên đường, vui lòng gọi 0931 999 309.\n\n"
                        . "Trân trọng,\nPhòng Khám Đa Khoa Hưng Định\n128 Nguyễn Văn Trỗi, Phú Nhuận, TP. Hồ Chí Minh",
                );
            }
        }

        $closed = $this->conn->query(
            "UPDATE appointments a
             SET status = 'NoShow'
             WHERE a.status IN ('Pending', 'Scheduled')
               AND (a.appointment_date AT TIME ZONE 'Asia/Ho_Chi_Minh') <= NOW() - INTERVAL '2 hours'
               AND NOT EXISTS (SELECT 1 FROM medical_records m WHERE m.appointment_id = a.appointment_id)
             RETURNING a.appointment_id"
        );
        $ids = $closed->fetchAll(PDO::FETCH_COLUMN);
        if ($ids) {
            $rows = $this->conn->query($base . " WHERE a.appointment_id IN (" . implode(',', array_map('intval', $ids)) . ")");
            foreach ($rows as $row) {
                $when = date('H:i d/m/Y', strtotime($row['appointment_date']));
                Notify::send($this->conn, 'patient', (int) $row['patient_id'], 'Lịch đã đóng vì trễ hẹn', 'Lịch #' . $row['appointment_id'] . ' quá 2 giờ nên chuyển sang Trễ hẹn.', '/index.php?action=booking');
                $mails[] = array(
                    'patient_id' => (int) $row['patient_id'],
                    'subject' => 'Lịch #' . $row['appointment_id'] . ' không còn hiệu lực - Phòng Khám Hưng Định',
                    'letter' => "Kính gửi " . $row['patient_name'] . ",\n\n"
                        . "Lịch khám #" . $row['appointment_id'] . " lúc " . $when . " với BS. " . $row['doctor_name']
                        . " đã quá 2 giờ so với giờ hẹn và chưa có bệnh án.\n\n"
                        . "Lịch này chuyển sang trạng thái Trễ hẹn, coi như không còn sử dụng được. "
                        . "Bác sĩ không lập bệnh án trên lịch này và lễ tân không sửa lịch này nữa.\n\n"
                        . "Quý khách đặt lịch mới trên trang phòng khám, hoặc gọi 0931 999 309.\n\n"
                        . "Trân trọng,\nPhòng Khám Đa Khoa Hưng Định\n128 Nguyễn Văn Trỗi, Phú Nhuận, TP. Hồ Chí Minh",
                );
            }
        }
        return $mails;
    }
}
?>
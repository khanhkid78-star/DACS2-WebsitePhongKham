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

    public function getByPatientId($patient_id) {
        $stmt = $this->conn->prepare(
            "SELECT * FROM " . $this->table_name . "
             WHERE patient_id = :patient_id
             ORDER BY created_at DESC"
        );
        $stmt->execute(array(':patient_id' => $patient_id));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function recordsWithoutInvoice() {
        $sql = "SELECT m.record_id, m.visit_date, m.diagnosis,
                    COALESCE(m.patient_name, p.full_name) AS patient_name,
                    COALESCE(m.doctor_name, d.full_name) AS doctor_name
                FROM medical_records m
                LEFT JOIN patients p ON p.patient_id = m.patient_id
                LEFT JOIN doctors d ON d.doctor_id = m.doctor_id
                WHERE NOT EXISTS (
                    SELECT 1 FROM invoices i WHERE i.record_id = m.record_id
                )
                ORDER BY m.record_id DESC";
        return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function quote($record_id) {
        $stmt = $this->conn->prepare(
            "SELECT m.record_id, m.appointment_id, m.visit_date, m.symptoms, m.diagnosis, m.conclusion,
                    COALESCE(m.patient_name, p.full_name) AS patient_name,
                    COALESCE(m.doctor_name, d.full_name) AS doctor_name
            FROM medical_records m
            LEFT JOIN patients p ON p.patient_id = m.patient_id
            LEFT JOIN doctors d ON d.doctor_id = m.doctor_id
            WHERE m.record_id = :id
            AND NOT EXISTS (SELECT 1 FROM invoices i WHERE i.record_id = m.record_id)"
        );
        $stmt->execute(array(':id' => (int) $record_id));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $meds = $this->conn->prepare(
            "SELECT med.name AS medicine_name, med.unit, med.price,
                    pd.quantity, pd.dosage, pd.usage_instructions
            FROM prescriptions pr
            JOIN prescription_details pd ON pd.prescription_id = pr.prescription_id
            JOIN medicines med ON med.medicine_id = pd.medicine_id
            WHERE pr.record_id = :id
            ORDER BY pd.id"
        );
        $meds->execute(array(':id' => (int) $record_id));
        $lines = $meds->fetchAll(PDO::FETCH_ASSOC);
        $drugTotal = 0;
        foreach ($lines as $i => $line) {
            $amount = (int) $line['quantity'] * (float) $line['price'];
            $lines[$i]['line_amount'] = $amount;
            $drugTotal += $amount;
        }
        $row['medicines'] = $lines;
        $row['drug_total'] = $drugTotal;
        return $row;
    }

    public function markPaid($invoice_id) {
        $stmt = $this->conn->prepare(
            "UPDATE " . $this->table_name . "
             SET status = 'Paid'
             WHERE invoice_id = :id AND status <> 'Paid'"
        );
        $stmt->execute(array(':id' => $invoice_id));
        return $stmt->rowCount() > 0;
    }

    public function createFromRecord($record_id, $exam_fee, $payment_method) {
        $allowed = array('Tiền mặt', 'Chuyển khoản', 'Thẻ');
        if (!in_array($payment_method, $allowed, true)) {
            return 'Chọn phương thức thanh toán.';
        }
        if (!is_numeric($exam_fee) || (float) $exam_fee < 0) {
            return 'Phí khám không hợp lệ.';
        }
        $exam_fee = (float) $exam_fee;

        $find = $this->conn->prepare(
            "SELECT record_id, patient_id FROM medical_records WHERE record_id = :id"
        );
        $find->execute(array(':id' => $record_id));
        $record = $find->fetch(PDO::FETCH_ASSOC);
        if (!$record) {
            return 'Không thấy bệnh án.';
        }

        $exists = $this->conn->prepare(
            "SELECT invoice_id FROM invoices WHERE record_id = :id LIMIT 1"
        );
        $exists->execute(array(':id' => $record_id));
        if ($exists->fetch(PDO::FETCH_ASSOC)) {
            return 'Bệnh án này đã có hóa đơn.';
        }

        $meds = $this->conn->prepare(
            "SELECT med.name, pd.quantity, med.price
             FROM prescriptions pr
             JOIN prescription_details pd ON pd.prescription_id = pr.prescription_id
             JOIN medicines med ON med.medicine_id = pd.medicine_id
             WHERE pr.record_id = :id"
        );
        $meds->execute(array(':id' => $record_id));
        $lines = $meds->fetchAll(PDO::FETCH_ASSOC);

        $this->conn->beginTransaction();
        try {
            $insert = $this->conn->prepare(
                "INSERT INTO invoices
                    (patient_id, record_id, total_amount, payment_method, status)
                 VALUES
                    (:patient_id, :record_id, 0, :payment_method, 'Unpaid')
                 RETURNING invoice_id"
            );
            $insert->execute(array(
                ':patient_id' => $record['patient_id'],
                ':record_id' => $record_id,
                ':payment_method' => $payment_method,
            ));
            $invoice_id = (int) $insert->fetchColumn();

            $detail = $this->conn->prepare(
                "INSERT INTO invoice_details (invoice_id, item_type, quantity, price)
                 VALUES (:invoice_id, :item_type, :quantity, :price)"
            );
            $detail->execute(array(
                ':invoice_id' => $invoice_id,
                ':item_type' => 'Công khám',
                ':quantity' => 1,
                ':price' => $exam_fee,
            ));
            $total = $exam_fee;
            foreach ($lines as $line) {
                $qty = (int) $line['quantity'];
                $price = (float) $line['price'];
                $detail->execute(array(
                    ':invoice_id' => $invoice_id,
                    ':item_type' => $line['name'],
                    ':quantity' => $qty,
                    ':price' => $price,
                ));
                $total += $qty * $price;
            }
            $update = $this->conn->prepare(
                "UPDATE invoices SET total_amount = :total WHERE invoice_id = :id"
            );
            $update->execute(array(
                ':total' => $total,
                ':id' => $invoice_id,
            ));
            $this->conn->commit();
            return '';
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return 'Không lập được hóa đơn.';
        }
    }

    public function fullView($invoice_id, $patient_id = null) {
        $sql = "SELECT i.invoice_id, i.total_amount, i.payment_method, i.status, i.created_at,
                    i.record_id,
                    COALESCE(m.patient_name, p.full_name) AS patient_name,
                    COALESCE(m.doctor_name, d.full_name) AS doctor_name,
                    m.appointment_id, m.visit_date, m.symptoms, m.diagnosis, m.conclusion
                FROM invoices i
                LEFT JOIN patients p ON p.patient_id = i.patient_id
                LEFT JOIN medical_records m ON m.record_id = i.record_id
                LEFT JOIN doctors d ON d.doctor_id = m.doctor_id
                WHERE i.invoice_id = :id";
        $params = array(':id' => (int) $invoice_id);
        if ($patient_id !== null) {
            $sql .= " AND i.patient_id = :patient_id";
            $params[':patient_id'] = (int) $patient_id;
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $items = $this->conn->prepare(
            "SELECT item_type, quantity, price
            FROM invoice_details WHERE invoice_id = :id ORDER BY invoice_detail_id"
        );
        $items->execute(array(':id' => (int) $invoice_id));
        $row['items'] = $items->fetchAll(PDO::FETCH_ASSOC);

        $meds = $this->conn->prepare(
            "SELECT med.name AS medicine_name, med.unit, med.price,
                    pd.quantity, pd.dosage, pd.usage_instructions
            FROM prescriptions pr
            JOIN prescription_details pd ON pd.prescription_id = pr.prescription_id
            JOIN medicines med ON med.medicine_id = pd.medicine_id
            WHERE pr.record_id = :id
            ORDER BY pd.id"
        );
        $meds->execute(array(':id' => (int) $row['record_id']));
        $row['medicines'] = $row['record_id'] ? $meds->fetchAll(PDO::FETCH_ASSOC) : array();
        unset($row['record_id']);
        return $row;
    }
}
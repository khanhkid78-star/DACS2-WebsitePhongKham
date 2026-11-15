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
        $sql = "SELECT m.record_id, m.patient_id, m.diagnosis, m.visit_date,
                       COALESCE(m.patient_name, p.full_name) AS patient_name
                FROM medical_records m
                LEFT JOIN patients p ON p.patient_id = m.patient_id
                WHERE NOT EXISTS (
                    SELECT 1 FROM invoices i WHERE i.record_id = m.record_id
                )
                ORDER BY m.visit_date DESC";
        return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
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
}
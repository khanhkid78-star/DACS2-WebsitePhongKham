<?php
class MedicalRecord {
    private $conn;
    private $table_name = "medical_records";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        return $this->getList(null, null);
    }

    public function getList($doctor_id, $patient_id, $q = '') {
        $sql = "SELECT m.record_id, m.patient_id, m.doctor_id, m.appointment_id,
                       m.visit_date, m.symptoms, m.diagnosis, m.conclusion,
                       COALESCE(m.patient_name, p.full_name) AS patient_name,
                       COALESCE(m.doctor_name, d.full_name) AS doctor_name,
                       (
                           SELECT string_agg(med.name || ' x' || pd.quantity::text, ', ')
                           FROM prescriptions pr
                           JOIN prescription_details pd ON pd.prescription_id = pr.prescription_id
                           JOIN medicines med ON med.medicine_id = pd.medicine_id
                           WHERE pr.record_id = m.record_id
                       ) AS prescription_text
                FROM " . $this->table_name . " m
                LEFT JOIN patients p ON p.patient_id = m.patient_id
                LEFT JOIN doctors d ON d.doctor_id = m.doctor_id
                WHERE 1 = 1";
        $params = array();
        if ($doctor_id !== null) {
            $sql .= " AND m.doctor_id = :doctor_id";
            $params[':doctor_id'] = $doctor_id;
        }
        if ($patient_id !== null) {
            $sql .= " AND m.patient_id = :patient_id";
            $params[':patient_id'] = $patient_id;
        }

        if ($q !== '') {
            $sql .= " AND (
                COALESCE(m.patient_name, p.full_name, '') ILIKE :q
                OR COALESCE(m.doctor_name, d.full_name, '') ILIKE :q
                OR COALESCE(m.diagnosis, '') ILIKE :q
                OR COALESCE(m.symptoms, '') ILIKE :q
                OR CAST(m.record_id AS TEXT) = :exact
            )";
            $params[':q'] = '%' . $q . '%';
            $params[':exact'] = $q;
        }

        $sql .= " ORDER BY m.visit_date DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($record_id, $doctor_id) {
        $sql = "SELECT * FROM " . $this->table_name . " WHERE record_id = :id";
        $params = array(':id' => $record_id);
        if ($doctor_id !== null) {
            $sql .= " AND doctor_id = :doctor_id";
            $params[':doctor_id'] = $doctor_id;
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row : null;
    }

    public function getDetail($record_id, $doctor_id = null) {
        $sql = "SELECT m.record_id,
                    COALESCE(m.patient_name, p.full_name) AS patient_name,
                    COALESCE(m.doctor_name, d.full_name) AS doctor_name,
                    m.appointment_id,
                    m.visit_date,
                    m.symptoms,
                    m.diagnosis,
                    m.conclusion,
                    m.created_at
                FROM " . $this->table_name . " m
                LEFT JOIN patients p ON p.patient_id = m.patient_id
                LEFT JOIN doctors d ON d.doctor_id = m.doctor_id
                WHERE m.record_id = :id";
        $params = array(':id' => (int) $record_id);
        if ($doctor_id !== null) {
            $sql .= " AND m.doctor_id = :doctor_id";
            $params[':doctor_id'] = (int) $doctor_id;
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        $medStmt = $this->conn->prepare(
            "SELECT pd.medicine_id,
                    med.name AS medicine_name,
                    med.unit,
                    med.price,
                    pd.quantity,
                    pd.dosage,
                    pd.usage_instructions
            FROM prescriptions pr
            JOIN prescription_details pd ON pd.prescription_id = pr.prescription_id
            JOIN medicines med ON med.medicine_id = pd.medicine_id
            WHERE pr.record_id = :id
            ORDER BY pd.id"
        );
        $medStmt->execute(array(':id' => (int) $record_id));
        $row['medicines'] = $medStmt->fetchAll(PDO::FETCH_ASSOC);
        return $row;
    }

    public function update($record_id, $symptoms, $diagnosis, $conclusion, $doctor_id) {
        $sql = "UPDATE " . $this->table_name . "
                SET symptoms = :symptoms, diagnosis = :diagnosis, conclusion = :conclusion
                WHERE record_id = :id";
        $params = array(
            ':symptoms' => $symptoms,
            ':diagnosis' => $diagnosis,
            ':conclusion' => $conclusion,
            ':id' => $record_id,
        );
        if ($doctor_id !== null) {
            $sql .= " AND doctor_id = :doctor_id";
            $params[':doctor_id'] = $doctor_id;
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    public function updateWithPrescription($record_id, $symptoms, $diagnosis, $conclusion, $doctor_id, $lines) {
        $this->conn->beginTransaction();
        try {
            $sql = "UPDATE " . $this->table_name . "
                    SET symptoms = :symptoms, diagnosis = :diagnosis, conclusion = :conclusion
                    WHERE record_id = :id";
            $params = array(
                ':symptoms' => $symptoms,
                ':diagnosis' => $diagnosis,
                ':conclusion' => $conclusion,
                ':id' => $record_id,
            );
            if ($doctor_id !== null) {
                $sql .= " AND doctor_id = :doctor_id";
                $params[':doctor_id'] = $doctor_id;
            }
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);

            $checkSql = "SELECT 1 FROM " . $this->table_name . " WHERE record_id = :id";
            $checkParams = array(':id' => $record_id);
            if ($doctor_id !== null) {
                $checkSql .= " AND doctor_id = :doctor_id";
                $checkParams[':doctor_id'] = $doctor_id;
            }
            $check = $this->conn->prepare($checkSql);
            $check->execute($checkParams);
            if (!$check->fetchColumn()) {
                $this->conn->rollBack();
                return false;
            }

            $this->conn->prepare(
                "DELETE FROM prescription_details
                WHERE prescription_id IN (SELECT prescription_id FROM prescriptions WHERE record_id = :id)"
            )->execute(array(':id' => $record_id));
            $this->conn->prepare("DELETE FROM prescriptions WHERE record_id = :id")->execute(array(':id' => $record_id));

            if (!empty($lines)) {
                $prescription = $this->conn->prepare(
                    "INSERT INTO prescriptions (record_id) VALUES (:record_id) RETURNING prescription_id"
                );
                $prescription->execute(array(':record_id' => $record_id));
                $prescription_id = (int) $prescription->fetchColumn();
                $detail = $this->conn->prepare(
                    "INSERT INTO prescription_details
                        (prescription_id, medicine_id, quantity, dosage, usage_instructions)
                    VALUES
                        (:prescription_id, :medicine_id, :quantity, :dosage, :usage)"
                );
                foreach ($lines as $line) {
                    $detail->execute(array(
                        ':prescription_id' => $prescription_id,
                        ':medicine_id' => $line['medicine_id'],
                        ':quantity' => $line['quantity'],
                        ':dosage' => $line['dosage'],
                        ':usage' => $line['usage'],
                    ));
                }
            }
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return false;
        }
    }

    public function createWithPrescription($data, $lines) {
        $check = $this->conn->prepare(
            "SELECT record_id FROM " . $this->table_name . " WHERE appointment_id = :id"
        );
        $check->execute(array(':id' => $data['appointment_id']));
        if ($check->fetch(PDO::FETCH_ASSOC)) {
            return false;
        }

        $this->conn->beginTransaction();
        try {
            $stmt = $this->conn->prepare(
                "INSERT INTO " . $this->table_name . "
                    (patient_id, patient_name, doctor_id, doctor_name, appointment_id,
                     visit_date, symptoms, diagnosis, conclusion)
                 VALUES
                    (:patient_id, :patient_name, :doctor_id, :doctor_name, :appointment_id,
                     :visit_date, :symptoms, :diagnosis, :conclusion)
                 RETURNING record_id"
            );
            $stmt->execute(array(
                ':patient_id' => $data['patient_id'],
                ':patient_name' => $data['patient_name'],
                ':doctor_id' => $data['doctor_id'],
                ':doctor_name' => $data['doctor_name'],
                ':appointment_id' => $data['appointment_id'],
                ':visit_date' => $data['visit_date'],
                ':symptoms' => $data['symptoms'],
                ':diagnosis' => $data['diagnosis'],
                ':conclusion' => $data['conclusion'],
            ));
            $record_id = (int) $stmt->fetchColumn();

            if (!empty($lines)) {
                $prescription = $this->conn->prepare(
                    "INSERT INTO prescriptions (record_id) VALUES (:record_id) RETURNING prescription_id"
                );
                $prescription->execute(array(':record_id' => $record_id));
                $prescription_id = (int) $prescription->fetchColumn();

                $detail = $this->conn->prepare(
                    "INSERT INTO prescription_details
                        (prescription_id, medicine_id, quantity, dosage, usage_instructions)
                     VALUES
                        (:prescription_id, :medicine_id, :quantity, :dosage, :usage)"
                );
                foreach ($lines as $line) {
                    $detail->execute(array(
                        ':prescription_id' => $prescription_id,
                        ':medicine_id' => $line['medicine_id'],
                        ':quantity' => $line['quantity'],
                        ':dosage' => $line['dosage'],
                        ':usage' => $line['usage'],
                    ));
                }
            }

            $done = $this->conn->prepare(
                "UPDATE appointments SET status = 'Completed' WHERE appointment_id = :id"
            );
            $done->execute(array(':id' => $data['appointment_id']));
            $this->conn->commit();
            return $record_id;
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }
}
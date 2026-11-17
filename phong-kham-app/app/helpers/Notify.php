<?php
require_once __DIR__ . '/Mailer.php';

class Notify {
    public static function send($db, $who, $linkId, $title, $body, $link) {
        try {
            if ($who === 'receptionists') {
                $ids = self::ids(
                    $db,
                    "SELECT user_id FROM users
                     WHERE role = 'receptionist' OR receptionist_id IS NOT NULL"
                );
            } elseif ($who === 'patient') {
                $ids = self::ids($db, "SELECT user_id FROM users WHERE patient_id = :id", array(':id' => (int) $linkId));
            } elseif ($who === 'doctor') {
                $ids = self::ids($db, "SELECT user_id FROM users WHERE doctor_id = :id", array(':id' => (int) $linkId));
            } else {
                $ids = array();
            }
            self::push($db, $ids, $title, $body, $link);
        } catch (Exception $e) {
            return;
        }
    }

    public static function mailPatient($db, $patientId, $subject, $text) {
        try {
            $stmt = $db->prepare("SELECT email, full_name FROM patients WHERE patient_id = :id");
            $stmt->execute(array(':id' => (int) $patientId));
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row || empty($row['email'])) {
                return false;
            }
            return Mailer::send($row['email'], $subject, $text);
        } catch (Exception $e) {
            return false;
        }
    }

    private static function ids($db, $sql, $params = array()) {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private static function push($db, $userIds, $title, $body, $link) {
        $userIds = array_values(array_unique(array_filter($userIds)));
        if (!$userIds) {
            return;
        }
        $ins = $db->prepare(
            "INSERT INTO notifications (user_id, title, body, link)
             VALUES (:user_id, :title, :body, :link)"
        );
        foreach ($userIds as $id) {
            $ins->execute(array(
                ':user_id' => $id,
                ':title' => $title,
                ':body' => $body,
                ':link' => $link,
            ));
        }
    }
}
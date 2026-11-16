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

        try {
            $mail = $db->prepare(
                "SELECT COALESCE(p.email, d.email, n.email, r.email) AS email
                FROM users u
                LEFT JOIN patients p ON p.patient_id = u.patient_id
                LEFT JOIN doctors d ON d.doctor_id = u.doctor_id
                LEFT JOIN nurses n ON n.nurse_id = u.nurse_id
                LEFT JOIN receptionists r ON r.receptionist_id = u.receptionist_id
                WHERE u.user_id = :id"
            );
            foreach ($userIds as $id) {
                try {
                    $mail->execute(array(':id' => $id));
                    $row = $mail->fetch(PDO::FETCH_ASSOC);
                    if ($row && !empty($row['email'])) {
                        Mailer::send($row['email'], $title, $body . "\n\nPhong Kham Da Khoa Hung Dinh");
                    }
                } catch (Exception $e) {
                    continue;
                }
            }
        } catch (Exception $e) {
            return;
        }
    }
}
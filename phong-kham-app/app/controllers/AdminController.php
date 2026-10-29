<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/Auth.php';

class AdminController {
    public function listUsers() {
        Auth::checkRole(array('admin'));

        $db = (new Database())->getConnection();
        $stmt = $db->query("SELECT * FROM users ORDER BY user_id ASC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../views/admin/users.php';
    }
}
?>
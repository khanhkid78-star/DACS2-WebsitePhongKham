<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Person.php';
require_once __DIR__ . '/../helpers/Auth.php';

class PersonController {
    private function db() {
        return (new Database())->getConnection();
    }

    private function guard($kind) {
        if (Person::spec($kind) === null) {
            header('Location: /index.php');
            exit();
        }
        if ($kind === 'patient') {
            Auth::checkRole(array('admin', 'receptionist'));
            return;
        }
        Auth::checkRole(array('admin'));
    }

    private function filters() {
        return array(
            'gender' => trim($_GET['gender'] ?? ''),
            'specialty' => trim($_GET['specialty'] ?? ''),
            'shift' => trim($_GET['shift'] ?? ''),
            'status' => trim($_GET['status'] ?? ''),
            'from' => trim($_GET['from'] ?? ''),
            'to' => trim($_GET['to'] ?? ''),
        );
    }

    public function show() {
        $role = Auth::getRole();
        if ($role === 'receptionist') {
            $kind = 'patient';
            $tabs = array('patient' => 'Bệnh nhân');
        } else {
            Auth::checkRole(array('admin'));
            $kind = $_GET['kind'] ?? 'doctor';
            if (Person::spec($kind) === null) {
                $kind = 'doctor';
            }
            $tabs = array(
                'doctor' => 'Bác sĩ',
                'nurse' => 'Y tá',
                'receptionist' => 'Lễ tân',
                'patient' => 'Bệnh nhân',
            );
        }
        $this->guard($kind);
        $model = new Person($this->db());
        $rows = $model->search($kind, '', array());
        $specialties = in_array($kind, array('doctor', 'nurse'), true) ? $model->choices($kind, 'specialty') : array();
        $shifts = $kind === 'receptionist' ? $model->choices($kind, 'shift') : array();
        $statuses = $kind === 'receptionist' ? $model->choices($kind, 'status') : array();
        require __DIR__ . '/../views/staff/list.php';
    }

    public function rows() {
        $kind = $_GET['kind'] ?? '';
        $this->guard($kind);
        $rows = (new Person($this->db()))->search($kind, trim($_GET['q'] ?? ''), $this->filters());
        require __DIR__ . '/../views/staff/_rows.php';
        exit();
    }

    public function detail() {
        $kind = $_GET['kind'] ?? '';
        $this->guard($kind);
        header('Content-Type: application/json; charset=UTF-8');
        $row = (new Person($this->db()))->find($kind, (int) ($_GET['id'] ?? 0));
        if (!$row) {
            echo json_encode(array('ok' => false, 'error' => 'Không tìm thấy hồ sơ'));
            exit();
        }
        $flag = $row['has_photo'];
        $row['has_photo'] = ($flag === true || $flag === 1 || $flag === '1' || $flag === 't');
        $row['photo_url'] = $row['has_photo']
            ? '/index.php?action=staff_photo&kind=' . urlencode($kind) . '&id=' . (int) $row['id']
            : '';
        echo json_encode(array('ok' => true, 'data' => $row), JSON_UNESCAPED_UNICODE);
        exit();
    }

    public function photo() {
        $kind = $_GET['kind'] ?? '';
        $this->guard($kind);
        $photo = (new Person($this->db()))->readPhoto($kind, (int) ($_GET['id'] ?? 0));
        if (!$photo) {
            http_response_code(404);
            exit();
        }
        header('Content-Type: ' . $photo['mime']);
        header('Cache-Control: private, max-age=3600');
        echo $photo['bytes'];
        exit();
    }

    public function save() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /index.php?action=staff');
            exit();
        }
        $kind = $_POST['kind'] ?? '';
        $this->guard($kind);
        $photoValue = null;
        if (!empty($_FILES['photo']['tmp_name'])) {
            $photoValue = $this->packPhoto($_FILES['photo']);
            if ($photoValue === '') {
                Auth::flash('Ảnh phải là JPG, PNG hoặc WEBP và nhỏ hơn 2MB.');
                header('Location: /index.php?action=staff&kind=' . urlencode($kind));
                exit();
            }
        }
        $error = (new Person($this->db()))->save(
            $kind,
            (int) ($_POST['id'] ?? 0),
            $_POST,
            $photoValue,
            !empty($_POST['clear_photo'])
        );
        Auth::flash($error === '' ? 'Đã lưu hồ sơ.' : $error, $error === '');
        header('Location: /index.php?action=staff&kind=' . urlencode($kind));
        exit();
    }

    public function remove() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /index.php?action=staff');
            exit();
        }
        $kind = $_POST['kind'] ?? '';
        $this->guard($kind);
        $error = (new Person($this->db()))->delete($kind, (int) ($_POST['id'] ?? 0));
        Auth::flash($error === '' ? 'Đã xóa hồ sơ.' : $error, $error === '');
        header('Location: /index.php?action=staff&kind=' . urlencode($kind));
        exit();
    }

    private function packPhoto($file) {
        if (($file['error'] ?? 1) !== UPLOAD_ERR_OK || ($file['size'] ?? 0) > 2 * 1024 * 1024) {
            return '';
        }
        $raw = file_get_contents($file['tmp_name']);
        $info = @getimagesizefromstring($raw);
        $allowed = array('image/jpeg', 'image/png', 'image/webp');
        if ($raw === false || !$info || !in_array($info['mime'], $allowed, true)) {
            return '';
        }
        $mime = $info['mime'];
        if (function_exists('imagecreatefromstring')) {
            $img = @imagecreatefromstring($raw);
            if ($img) {
                $w = imagesx($img);
                $h = imagesy($img);
                if ($w > 640) {
                    $nh = (int) round($h * 640 / $w);
                    $dst = imagecreatetruecolor(640, max(1, $nh));
                    imagecopyresampled($dst, $img, 0, 0, 0, 0, 640, max(1, $nh), $w, $h);
                    imagedestroy($img);
                    $img = $dst;
                }
                ob_start();
                imagejpeg($img, null, 72);
                $raw = ob_get_clean();
                imagedestroy($img);
                $mime = 'image/jpeg';
            }
        }
        return $mime . '|' . base64_encode($raw);
    }
}
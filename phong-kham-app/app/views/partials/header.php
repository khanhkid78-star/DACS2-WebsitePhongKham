<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phòng Khám Đa Khoa Hưng Định</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&family=Source+Serif+4:opsz,wght@8..60,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/public/css/style.css">

    <script>
    (function () {
        var id = sessionStorage.getItem("pk_tab");
        if (!id) {
            id = Math.random().toString(36).slice(2, 12) + Date.now().toString(36);
            sessionStorage.setItem("pk_tab", id);
        }
        var url = new URL(location.href);
        if (url.searchParams.get("tab") !== id) {
            url.searchParams.set("tab", id);
            location.replace(url.toString());
        }
    })();
    </script>
</head>
<?php
require_once __DIR__ . '/../../helpers/Auth.php';
Auth::startSession();
?>
<body data-user="<?php echo (int) ($_SESSION['user_id'] ?? 0); ?>">
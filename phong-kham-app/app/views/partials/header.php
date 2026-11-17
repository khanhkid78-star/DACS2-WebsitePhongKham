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
                var key = "pk_tab";
                var id = "";
                try {
                    id = sessionStorage.getItem(key) || "";
                } catch (e) {
                    id = "";
                }
                if (!/^[A-Za-z0-9]{8,40}$/.test(id)) {
                    id = (Date.now().toString(36) + Math.random().toString(36).slice(2)).replace(/[^A-Za-z0-9]/g, "").slice(0, 20);
                    try { sessionStorage.setItem(key, id); } catch (e) {}
                }
                var url = new URL(location.href);
                if (url.searchParams.get("tab") !== id) {
                    url.searchParams.set("tab", id);
                    location.replace(url.toString());
                    return;
                }
                document.addEventListener("DOMContentLoaded", function () {
                    document.querySelectorAll("form").forEach(function (form) {
                        var field = form.querySelector("input[name=tab]");
                        if (!field) {
                            field = document.createElement("input");
                            field.type = "hidden";
                            field.name = "tab";
                            form.appendChild(field);
                        }
                        field.value = id;
                    });
                });
            })();
        </script>
</head>
<?php
require_once __DIR__ . '/../../helpers/Auth.php';
Auth::startSession();
?>
<body data-user="<?php echo (int) ($_SESSION['user_id'] ?? 0); ?>">
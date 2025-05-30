<?php
session_start();

// Nếu chưa đăng nhập, chuyển về trang đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../frontend/router.php?page=login");
    exit();
}

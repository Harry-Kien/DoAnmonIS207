<?php
session_start();
require_once "../config/config.php";
require_once "notification_handler.php";

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if notification_id is provided
if (!isset($_POST['notification_id']) || empty($_POST['notification_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Notification ID is required']);
    exit;
}

$notification_id = intval($_POST['notification_id']);
$user_id = $_SESSION['user_id'];

// Initialize notification handler
$notificationHandler = new NotificationHandler($conn);

// Mark notification as read
$result = $notificationHandler->markAsRead($notification_id, $user_id);

// Return response
header('Content-Type: application/json');
if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to mark notification as read']);
}
?> 
<?php
session_start();
require_once '../../config/db.php';

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Invalid CSRF token.");
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Unauthorized access.";
    header("Location: ../profile.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$old_password = $_POST['old_password'] ?? '';
$new_password = $_POST['password'] ?? '';

try {
    // Fetch current user details
    $query = "SELECT full_name, email, password FROM users WHERE user_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['error'] = "User not found.";
        header("Location: ../profile.php");
        exit();
    }

    // Detect if any changes were made
    $changes = [];
    
    if ($full_name !== $user['full_name']) {
        $changes['full_name'] = $full_name;
    }
    if ($email !== $user['email']) {
        if (empty($old_password) || !password_verify($old_password, $user['password'])) {
            $_SESSION['error'] = "Old password is required to update email.";
            header("Location: ../profile.php");
            exit();
        }
        $changes['email'] = $email;
    }
    if (!empty($new_password)) {
        if (empty($old_password) || !password_verify($old_password, $user['password'])) {
            $_SESSION['error'] = "Incorrect old password.";
            header("Location: ../profile.php");
            exit();
        }
        $changes['password'] = password_hash($new_password, PASSWORD_DEFAULT);
    }

    // If no changes, prevent unnecessary update
    if (empty($changes)) {
        $_SESSION['message'] = "No changes were made.";
        header("Location: ../profile.php");
        exit();
    }

    // Build dynamic update query
    $update_fields = [];
    foreach ($changes as $column => $value) {
        $update_fields[] = "$column = :$column";
    }
    $update_query = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE user_id = :user_id";
    $update_stmt = $conn->prepare($update_query);
    
    foreach ($changes as $column => $value) {
        $update_stmt->bindValue(":$column", $value, PDO::PARAM_STR);
    }
    $update_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

    if ($update_stmt->execute()) {
        $_SESSION['message'] = "Profile updated successfully.";
    } else {
        $_SESSION['error'] = "Failed to update profile.";
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    $_SESSION['error'] = "An error occurred. Please try again.";
}

header("Location: ../profile.php");
exit();
?>

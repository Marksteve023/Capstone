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
    $query = "SELECT password FROM users WHERE user_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['error'] = "User not found.";
        header("Location: ../profile.php");
        exit();
    }
   // If updating Email, verify old password
    if (!empty($email) && $email !== $user['email']) {
        if (empty($old_password) || !password_verify($old_password, $user['password'])) {
            $_SESSION['error'] = "Old password is required to update email.";
            header("Location: ../profile.php");
            exit();
        }
    }

    // If updating password, verify old password
    if (!empty($new_password)) {
        if (empty($old_password) || !password_verify($old_password, $user['password'])) {
            $_SESSION['error'] = "Incorrect old password.";
            header("Location: ../profile.php");
            exit();
        }
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    } else {
        $hashed_password = $user['password']; // Keep current password
    }


    // Check if new password is being set but old password is missing
    if (!empty($new_password) && empty($old_password)) {
        $_SESSION['error'] = "Old password is required to set a new password.";
        header("Location: ../profile.php");
        exit();
    }

    // If updating password, verify old password
    if (!empty($new_password)) {
        if (!password_verify($old_password, $user['password'])) {
            $_SESSION['error'] = "Incorrect old password.";
            header("Location: ../profile.php");
            exit();
        }
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    } else {
        $hashed_password = $user['password']; // Keep current password
    }

    // Update user details
    $update_query = "UPDATE users SET full_name = :full_name, email = :email, password = :password WHERE user_id = :user_id";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bindParam(':full_name', $full_name, PDO::PARAM_STR);
    $update_stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $update_stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
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

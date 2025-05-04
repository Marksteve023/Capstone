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
$upload_dir = "../../assets/uploads/";
$allowed_types = ["jpg", "jpeg", "png", "gif"];
$max_size = 2 * 1024 * 1024; // 2MB
$new_file_name = null;

try {
    // Fetch user info
    $stmt = $conn->prepare("SELECT password, email, full_name FROM users WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['error'] = "User not found.";
        header("Location: ../profile.php");
        exit();
    }

    // Determine if any sensitive data is being changed
    $changing_name = $full_name !== $user['full_name'];
    $changing_email = $email !== $user['email'];
    $changing_password = !empty($new_password);
    $uploading_picture = !empty($_FILES['profile_picture']['name']);

    if (
        ($changing_name || $changing_email || $changing_password || $uploading_picture) &&
        (empty($old_password) || !password_verify($old_password, $user['password']))
    ) {
        $_SESSION['error'] = "Old password is required and must be correct to update your profile.";
        header("Location: ../profile.php");
        exit();
    }

    // Set password
    $hashed_password = $changing_password ? password_hash($new_password, PASSWORD_DEFAULT) : $user['password'];

    // Handle picture upload
    if ($uploading_picture) {
        $file = $_FILES['profile_picture'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($file_ext, $allowed_types)) {
            $_SESSION['error'] = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
            header("Location: ../profile.php");
            exit();
        }

        if ($file['size'] > $max_size) {
            $_SESSION['error'] = "File is too large. Maximum size is 2MB.";
            header("Location: ../profile.php");
            exit();
        }

        $new_file_name = "User_" . $full_name  . "_" . time() . "." . $file_ext;
        $target_file = $upload_dir . $new_file_name;

        if (!move_uploaded_file($file['tmp_name'], $target_file)) {
            $_SESSION['error'] = "Failed to upload profile picture.";
            header("Location: ../profile.php");
            exit();
        }
    }

    // Build update query
    $update_query = "UPDATE users SET full_name = :full_name, email = :email, password = :password";
    if ($new_file_name) {
        $update_query .= ", picture = :picture";
    }
    $update_query .= " WHERE user_id = :user_id";

    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bindParam(':full_name', $full_name, PDO::PARAM_STR);
    $update_stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $update_stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
    if ($new_file_name) {
        $update_stmt->bindParam(':picture', $new_file_name, PDO::PARAM_STR);
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
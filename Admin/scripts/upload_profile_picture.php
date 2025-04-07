<?php
session_start();
require_once '../../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
    exit();
}

$user_id = $_SESSION['user_id'];
$upload_dir = "../../assets/uploads/";
$allowed_types = ["jpg", "jpeg", "png", "gif"];
$max_size = 2 * 1024 * 1024; // 2MB

if (isset($_FILES['profile_picture'])) {
    $file = $_FILES['profile_picture'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $new_file_name = "user_" . $user_id . "_" . time() . "." . $file_ext;
    $target_file = $upload_dir . $new_file_name;

    if (!in_array($file_ext, $allowed_types)) {
        echo json_encode(["status" => "error", "message" => "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed."]);
        exit();
    }

    if ($file['size'] > $max_size) {
        echo json_encode(["status" => "error", "message" => "File is too large. Maximum size is 2MB."]);
        exit();
    }

    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        // Update user's profile picture in database
        $query = "UPDATE users SET picture = :picture WHERE user_id = :user_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':picture', $new_file_name, PDO::PARAM_STR);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode(["status" => "success", "file_name" => $new_file_name, "message" => "Profile picture updated successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to upload file."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "No file uploaded."]);
}
?>

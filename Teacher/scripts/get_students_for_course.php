<?php
require_once '../../config/db.php';

// Get course ID and set group from POST data
$course_id = $_POST['course_id'];
$set_group = $_POST['set_group'];

// Query to fetch the class start time
$query = "SELECT class_start_time FROM courses WHERE course_id = :course_id AND set_group = :set_group";
$stmt = $conn->prepare($query);
$stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
$stmt->bindParam(':set_group', $set_group, PDO::PARAM_STR);
$stmt->execute();

// Fetch the class start time
$classStartTime = $stmt->fetchColumn();

// If class start time is found, return it as a JSON response
if ($classStartTime) {
    echo json_encode(['success' => true, 'class_start_time' => $classStartTime]);
} else {
    echo json_encode(['success' => false]);
}
?>

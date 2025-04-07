<?php
session_start();
include '../../config/db.php';

header('Content-Type: application/json');

// Debugging logs
error_log("DELETE Request received");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the POST variable is set
    if (isset($_POST['student_course_id'])) {
        $student_course_id = intval($_POST['student_course_id']);

        try {
            // Debugging log
            error_log("Deleting student_course_id: " . $student_course_id);

            // Delete query
            $sql = "DELETE FROM student_courses WHERE student_course_id = :student_course_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':student_course_id', $student_course_id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                echo json_encode(["success" => true, "message" => "Enrolled student course deleted successfully"]);
            } else {
                echo json_encode(["success" => false, "message" => "Enrolled student course not found or already deleted"]);
            }
        } catch (PDOException $e) {
            error_log("SQL Error: " . $e->getMessage());
            echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Missing student_course_id"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method"]);
}
?>

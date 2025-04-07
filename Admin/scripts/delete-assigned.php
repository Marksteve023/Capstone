<?php
session_start();
include '../../config/db.php';

header('Content-Type: application/json');

// Debugging logs
error_log("DELETE Request received");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the POST variable is set
    if (isset($_POST['assigned_course_id'])) {
        $assigned_course_id = intval($_POST['assigned_course_id']);

        try {
            // Debugging log
            error_log("Deleting assigned_course_id: " . $assigned_course_id);

            // Delete query
            $sql = "DELETE FROM assigned_courses WHERE assigned_course_id = :assigned_course_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':assigned_course_id', $assigned_course_id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                echo json_encode(["success" => true, "message" => "Assigned course deleted successfully"]);
            } else {
                echo json_encode(["success" => false, "message" => "Assigned course not found or already deleted"]);
            }
        } catch (PDOException $e) {
            error_log("SQL Error: " . $e->getMessage());
            echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
        }
    }
} 

?>

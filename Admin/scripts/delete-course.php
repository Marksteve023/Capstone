<?php
session_start();
include '../../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id'])) {
    $course_id = intval($_POST['course_id']); 

    try {
        $sql = "DELETE FROM courses WHERE course_id = :course_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo json_encode(["success" => true, "message" => "Course & Section deleted successfully"]);
        } else {
            echo json_encode(["success" => false, "message" => "Course & Section not found or already deleted"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
}
?>

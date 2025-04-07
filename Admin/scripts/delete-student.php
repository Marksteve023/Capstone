<?php
session_start();
include '../../config/db.php';

header('Content-Type: application/json');

file_put_contents('log.txt', print_r($_POST, true)); 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id'])) {
    $student_id = intval($_POST['student_id']); 

    try {
        $sql = "DELETE FROM students WHERE student_id = :student_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo json_encode(["success" => true, "message" => "Student record deleted successfully"]);
        } else {
            echo json_encode(["success" => false, "message" => "Student record not found or already deleted"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
}
?>

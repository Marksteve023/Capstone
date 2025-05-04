<?php
include '../config.php'; // Include your database connection file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $statuses = $_POST['statuses'];

    foreach ($statuses as $statusRecord) {
        $attendance_id = $statusRecord['attendance_id'];
        $status = $statusRecord['status'];

        // Update the status in the database
        $stmt = $conn->prepare("UPDATE attendance SET status = :status WHERE attendance_id = :attendance_id");
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':attendance_id', $attendance_id);
        $stmt->execute();
    }

    echo json_encode(['message' => 'Status updated successfully']);
}
?>

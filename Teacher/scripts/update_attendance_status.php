<?php
include '../../config/db.php';

// Check if status updates are provided
if (isset($_POST['status_updates'])) {
    $statusUpdates = json_decode($_POST['status_updates'], true);

    try {
        // Begin a transaction to ensure data integrity
        $conn->beginTransaction();

        // Loop through each status update and update the attendance table
        foreach ($statusUpdates as $update) {
            $attendanceId = $update['attendance_id'];
            $status = $update['status'];

            // Update only the 'status' column and keep the 'timestamp' unchanged
            $sql = "UPDATE attendance SET status = :status, timestamp = timestamp WHERE attendance_id = :attendance_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':attendance_id', $attendanceId, PDO::PARAM_INT);
            $stmt->execute();
        }

        // Commit the transaction
        $conn->commit();

        echo "Attendance statuses updated successfully!";
    } catch (PDOException $e) {
        // Rollback the transaction in case of error
        $conn->rollBack();
        echo "Error updating attendance: " . $e->getMessage();
    }
}
?>

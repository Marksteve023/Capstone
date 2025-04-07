<?php
require_once '../../config/db.php';

// Retrieve the attendance data from the POST request
$attendanceData = json_decode($_POST['attendance'], true);

// Check if the attendance data is empty or not valid
if (empty($attendanceData)) {
    echo "No attendance data provided.";
    exit;
}

// Start a transaction to ensure all records are inserted together
try {
    $conn->beginTransaction();

    // Prepare an insert query to save attendance records
    $stmt = $conn->prepare("INSERT INTO attendance (student_id, course_id, status, attendance_date, attendance_time, timestamp) 
                            VALUES (:student_id, :course_id, :status, :attendance_date, :attendance_time, :timestamp)");

    // Loop through the attendance data and insert each record
    foreach ($attendanceData as $attendance) {
        // Validate data presence
        if (!isset($attendance['student_id'], $attendance['course_id'], $attendance['status'], $attendance['timestamp'])) {
            throw new Exception("Missing required attendance data for student_id: " . $attendance['student_id']);
        }

        $stmt->bindParam(':student_id', $attendance['student_id']);
        $stmt->bindParam(':course_id', $attendance['course_id']);
        $stmt->bindParam(':status', $attendance['status']);
        
        // Convert the timestamp to a date and time format
        $attendanceTimestamp = new DateTime($attendance['timestamp']);
        $attendanceDate = $attendanceTimestamp->format('Y-m-d');
        $attendanceTime = $attendanceTimestamp->format('H:i:s');
        
        $stmt->bindParam(':attendance_date', $attendanceDate);
        $stmt->bindParam(':attendance_time', $attendanceTime);
        $stmt->bindParam(':timestamp', $attendance['timestamp']);

        // Execute the query for each student record
        $stmt->execute();
    }

    // Commit the transaction if everything went fine
    $conn->commit();
    echo "Attendance saved successfully.";
} catch (Exception $e) {
    // Rollback the transaction in case of an error
    $conn->rollBack();
    echo "Failed to save attendance: " . $e->getMessage();
}
?>

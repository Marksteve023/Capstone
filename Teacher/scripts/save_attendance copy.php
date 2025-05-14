<?php
session_start();
require_once '../../config/db.php';

// Ensure the user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    echo 'Unauthorized';
    exit();
}

// Check if attendance data is received via POST
if (isset($_POST['attendance_data'])) {
    $attendanceData = json_decode($_POST['attendance_data'], true);

    try {
        $conn->beginTransaction();

        foreach ($attendanceData as $attendance) {
            if (isset($attendance['student_id'], $attendance['course_id'], $attendance['status'], $attendance['attendance_date'], $attendance['attendance_time'])) {

                // Determine timestamp (only for Present or Late)
                $timestamp = null;
                if ($attendance['status'] === 'Present' || $attendance['status'] === 'Late') {
                    $timestamp = date("Y-m-d H:i:s");
                }

                $query = "INSERT INTO attendance (
                            student_id, 
                            course_id, 
                            status,
                            attendance_date, 
                            attendance_time, 
                            timestamp,
                            set_group
                          ) VALUES (
                            :student_id, 
                            :course_id, 
                            :status, 
                            :attendance_date, 
                            :attendance_time, 
                            :timestamp,
                            :set_group
                          )";

                $stmt = $conn->prepare($query);
                $stmt->bindParam(':student_id', $attendance['student_id'], PDO::PARAM_INT);
                $stmt->bindParam(':course_id', $attendance['course_id'], PDO::PARAM_INT);
                $stmt->bindParam(':status', $attendance['status'], PDO::PARAM_STR);
                $stmt->bindParam(':attendance_date', $attendance['attendance_date'], PDO::PARAM_STR);
                $stmt->bindParam(':attendance_time', $attendance['attendance_time'], PDO::PARAM_STR);
                $stmt->bindParam(':timestamp', $timestamp, PDO::PARAM_STR);
                $stmt->bindParam(':set_group', $attendance['setGroup'], PDO::PARAM_STR);
                $stmt->execute();
            }
        }

        $conn->commit();
        echo 'success';
    } catch (Exception $e) {
        $conn->rollBack();
        echo 'error: ' . $e->getMessage();
    }
} else {
    echo 'No data received';
}
?>

<?php
session_start();
include '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $course_id = $_POST['course_id'];
    $section = $_POST['section'];
    $set_group = $_POST['set_group'];
    $attendance_date = $_POST['attendance_date'];
    $attendance_time = $_POST['attendance_time'];

    // Corrected SQL using JOIN since section is in the courses table
    $sql = "DELETE a FROM attendance a
            JOIN courses c ON a.course_id = c.course_id
            WHERE a.course_id = :course_id
              AND c.section = :section
              AND a.set_group = :set_group
              AND a.attendance_date = :attendance_date
              AND a.attendance_time = :attendance_time";

    $stmt = $conn->prepare($sql);
    $success = $stmt->execute([
        ':course_id' => $course_id,
        ':section' => $section,
        ':set_group' => $set_group,
        ':attendance_date' => $attendance_date,
        ':attendance_time' => $attendance_time
    ]);

    echo $success ? 'success' : 'error';
}
?>

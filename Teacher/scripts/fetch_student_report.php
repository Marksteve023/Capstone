<?php
include '../../config/db.php';

$student_id = $_GET['student_id'];
$course_name = $_GET['course_name'];
$section = $_GET['section'];

$sql = "SELECT 
            a.attendance_date,
            a.attendance_time,
            a.timestamp,
            a.status,
            c.course_name,
            c.section,
            a.set_group
        FROM attendance a
        JOIN courses c ON c.course_id = a.course_id
        WHERE a.student_id = :student_id
          AND c.course_name = :course_name
          AND c.section = :section
        ORDER BY a.attendance_date DESC, a.attendance_time DESC";

$stmt = $conn->prepare($sql);
$stmt->execute([
    ':student_id' => $student_id,
    ':course_name' => $course_name,
    ':section' => $section
]);

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($results);
?>

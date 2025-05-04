<?php
session_start();
header('Content-Type: application/json');
include '../../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    echo json_encode([]);
    exit();
}

// Get and sanitize parameters
$course_name = $_GET['course_name'] ?? '';
$section = $_GET['section'] ?? '';
$set_group = $_GET['set_group'] ?? '';
$attendance_date = $_GET['attendance_date'] ?? '';
$attendance_time = $_GET['attendance_time'] ?? '';

$sql = "
    SELECT 
        a.attendance_id,
        s.school_student_id,
        s.student_name,
        a.set_group,
        a.timestamp,
        a.status
    FROM attendance a
    INNER JOIN students s ON a.student_id = s.student_id
    INNER JOIN courses c ON a.course_id = c.course_id
    WHERE 
        c.course_name = :course_name AND
        c.section = :section AND
        a.set_group = :set_group AND
        a.attendance_date = :attendance_date AND
        a.attendance_time = :attendance_time
    ORDER BY s.student_name ASC
";

$stmt = $conn->prepare($sql);
$stmt->execute([
    ':course_name' => $course_name,
    ':section' => $section,
    ':set_group' => $set_group,
    ':attendance_date' => $attendance_date,
    ':attendance_time' => $attendance_time,
]);

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($data);
?>

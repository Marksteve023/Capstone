<?php
session_start();
include '../../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['student_id']) || empty($_POST['course_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$student_id = intval($_SESSION['student_id']);
$course_id = intval($_POST['course_id']);
$attendance_date = $_POST['attendance_date'] ?? null;
$attendance_month = $_POST['attendance_month'] ?? null;

try {
    $sql = "SELECT 
                c.course_name, 
                c.section, 
                a.set_group, 
                a.attendance_date, 
                a.attendance_time, 
                a.timestamp, 
                a.status
            FROM attendance a
            INNER JOIN courses c ON a.course_id = c.course_id
            WHERE a.student_id = :student_id AND a.course_id = :course_id";

    if (!empty($attendance_date)) {
        $sql .= " AND a.attendance_date = :attendance_date";
    } elseif (!empty($attendance_month)) {
        $sql .= " AND DATE_FORMAT(a.attendance_date, '%Y-%m') = :attendance_month";
    }

    $sql .= " ORDER BY a.attendance_date DESC, a.attendance_time DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':student_id', $student_id, PDO::PARAM_INT);
    $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);

    if (!empty($attendance_date)) {
        $stmt->bindValue(':attendance_date', $attendance_date);
    } elseif (!empty($attendance_month)) {
        $stmt->bindValue(':attendance_month', $attendance_month);
    }

    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $data]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>

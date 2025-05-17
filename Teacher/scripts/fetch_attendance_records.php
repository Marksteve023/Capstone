<?php
session_start();
include '../../config/db.php';

// Ensure that the user is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../index.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);

// Get and sanitize the POST data
$week = isset($_POST['week']) ? filter_var($_POST['week'], FILTER_SANITIZE_SPECIAL_CHARS) : '';
$month = isset($_POST['month']) ? filter_var($_POST['month'], FILTER_SANITIZE_SPECIAL_CHARS) : '';

// Date filtering (week or month)
if ($week) {
    $yearWeek = explode("-W", $week);
    if (count($yearWeek) == 2) {
        $year = $yearWeek[0];
        $week_num = $yearWeek[1];
        $start_date = new DateTime();
        $start_date->setISODate($year, $week_num);
        $end_date = clone $start_date;
        $end_date->modify('+6 days');
        $from_date = $start_date->format('Y-m-d');
        $to_date = $end_date->format('Y-m-d');
    } else {
        $from_date = date('Y-m-01');
        $to_date = date('Y-m-t');
    }
} elseif ($month) {
    list($year, $month_num) = explode('-', $month);
    $from_date = "$year-$month_num-01";
    $to_date = date("Y-m-t", strtotime($from_date));
} else {
    $from_date = date('Y-m-01');
    $to_date = date('Y-m-t');
}

// Main query — filter by date range and assigned teacher
$sql = "SELECT DISTINCT 
    c.course_id,
    c.course_name,
    c.section,
    a.set_group,
    a.attendance_date,
    a.attendance_time,
    u.full_name AS teacher_name
FROM attendance a
INNER JOIN courses c ON c.course_id = a.course_id
INNER JOIN assigned_courses ac ON ac.course_id = c.course_id
INNER JOIN users u ON u.user_id = ac.user_id
WHERE a.attendance_date BETWEEN ? AND ?
  AND ac.user_id = ?
  AND u.role = 'teacher'
ORDER BY a.attendance_date DESC, c.course_name;";

$stmt = $conn->prepare($sql);
$stmt->execute([$from_date, $to_date, $user_id]);

$attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($attendance_records);
?>
<?php
session_start();
include '../../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../index.php");
    exit();
}

require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

if (!isset($_GET['course_name'], $_GET['section'], $_GET['set_group'], $_GET['attendance_date'], $_GET['attendance_time'])) {
    die('Missing required parameters.');
}

$course_name = htmlspecialchars($_GET['course_name']);
$section = htmlspecialchars($_GET['section']);
$set_group = htmlspecialchars($_GET['set_group']);
$attendance_date = htmlspecialchars($_GET['attendance_date']);
$attendance_time_raw = htmlspecialchars($_GET['attendance_time']);
$formatted_attendance_time = date('g:i A', strtotime($attendance_time_raw));

// Fetch attendance data
$sql = "
    SELECT a.attendance_id, s.school_student_id, s.student_name, a.set_group, a.timestamp, a.status
    FROM attendance a
    INNER JOIN students s ON a.student_id = s.student_id
    INNER JOIN courses c ON a.course_id = c.course_id
    WHERE c.course_name = :course_name 
      AND c.section = :section 
      AND a.set_group = :set_group 
      AND a.attendance_date = :attendance_date 
      AND a.attendance_time = :attendance_time
    ORDER BY s.student_name ASC
";

$stmt = $conn->prepare($sql);
$stmt->bindParam(':course_name', $course_name);
$stmt->bindParam(':section', $section);
$stmt->bindParam(':set_group', $set_group);
$stmt->bindParam(':attendance_date', $attendance_date);
$stmt->bindParam(':attendance_time', $attendance_time_raw);
$stmt->execute();
$attendance_details = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($attendance_details) == 0) {
    die('No attendance data found.');
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setCellValue('A1', "Attendance - $course_name - $section - $set_group - $attendance_date - $formatted_attendance_time");
$sheet->mergeCells('A1:F1');
$titleStyle = $sheet->getStyle('A1:F1');
$titleStyle->getFont()->setBold(true)->setSize(14);
$titleStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
$titleStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

// Header row
$headers = ['#', 'School Student ID', 'Student Name', 'Set Group', 'Timestamp', 'Status'];
$sheet->fromArray($headers, NULL, 'A3');

// Apply style to header
$headerStyle = $sheet->getStyle('A3:F3');
$headerStyle->getFont()->setBold(true);
$headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$headerStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

// Add attendance data
$row = 4;
$count = 1;
foreach ($attendance_details as $record) {
    if ($record['timestamp'] === null) {
        $formatted_timestamp = '';
    } else {
        $formatted_timestamp = date('g:i A', strtotime($record['timestamp']));
    }

    $sheet->setCellValue('A' . $row, $count++)
          ->setCellValue('B' . $row, $record['school_student_id'])
          ->setCellValue('C' . $row, $record['student_name'])
          ->setCellValue('D' . $row, $record['set_group'])
          ->setCellValue('E' . $row, $formatted_timestamp)
          ->setCellValue('F' . $row, $record['status']);
    $row++;
}

// Apply borders and center alignment to data cells
$dataRowStart = 4;
$dataRowEnd = $row - 1;
$styleRange = "A{$dataRowStart}:F{$dataRowEnd}";

$sheet->getStyle($styleRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle($styleRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Auto-size columns
foreach (range('A', 'F') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Export
$filename_time = str_replace([':', ' '], ['-', ''], $formatted_attendance_time);
$filename = "Attendance_{$course_name}_{$section}_{$attendance_date}_{$filename_time}.xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment;filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>

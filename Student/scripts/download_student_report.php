<?php
require '../../vendor/autoload.php';
require_once '../../config/db.php';
session_start();

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Chart\Legend;

// Check session
if (!isset($_SESSION['student_id'])) {
    die("Unauthorized access.");
}

$student_id = intval($_SESSION['student_id']);
$course_id = $_GET['course_id'] ?? '';
$course_name = $_GET['course_name'] ?? '';
$section = $_GET['section'] ?? '';
$attendance_date = $_GET['attendance_date'] ?? '';
$attendance_month = $_GET['attendance_month'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Get student name
$stmt = $conn->prepare("SELECT student_name FROM students WHERE student_id = :student_id");
$stmt->bindParam(':student_id', $student_id);
$stmt->execute();
$student = $stmt->fetch(PDO::FETCH_ASSOC);
$student_name = $student ? $student['student_name'] : 'Unknown';

// Prepare SQL
$params = [':student_id' => $student_id, ':course_id' => $course_id];
$sql = "SELECT c.course_name, c.section, a.set_group, a.attendance_date, a.attendance_time, a.timestamp, a.status
        FROM attendance a
        INNER JOIN courses c ON a.course_id = c.course_id
        WHERE a.student_id = :student_id AND a.course_id = :course_id";

if (!empty($attendance_date)) {
    $sql .= " AND a.attendance_date = :attendance_date";
    $params[':attendance_date'] = $attendance_date;
} elseif (!empty($attendance_month)) {
    $sql .= " AND DATE_FORMAT(a.attendance_date, '%Y-%m') = :attendance_month";
    $params[':attendance_month'] = $attendance_month;
}

if (!empty($status_filter)) {
    $sql .= " AND a.status = :status_filter";
    $params[':status_filter'] = $status_filter;
}

$sql .= " ORDER BY a.attendance_date DESC, a.attendance_time DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$records) {
    die("No attendance data found.");
}

// Count status
$statusCount = ['Present' => 0, 'Late' => 0, 'Absent' => 0, 'Excused' => 0];
foreach ($records as $r) {
    if (isset($statusCount[$r['status']])) {
        $statusCount[$r['status']]++;
    }
}

// Create spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Attendance");

// Report Title
$filter_info = '';
if (!empty($attendance_date)) {
    $filter_info = " - Date: " . date('F j, Y', strtotime($attendance_date));
} elseif (!empty($attendance_month)) {
    $filter_info = " - Month: " . date('F Y', strtotime($attendance_month . "-01"));
}
if (!empty($status_filter)) {
    $filter_info .= " - Status: $status_filter";
}

$title = "Attendance Report for $student_name ($course_name - $section)$filter_info";
$sheet->mergeCells('A1:H1');
$sheet->setCellValue('A1', $title);
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A1:H1')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

// Header row
$headers = ['#', 'Course', 'Section', 'Set Group', 'Attendance Date', 'Attendance Time', 'Timestamp', 'Status'];
$sheet->fromArray($headers, null, 'A3');
$sheet->getStyle('A3:H3')->getFont()->setBold(true);
$sheet->setAutoFilter('A3:H3');

// Attendance data
$row = 4;
$statusColorMap = [
    'Present' => '4472C4',
    'Late'    => 'E74C3C',
    'Absent'  => 'A9D18E',
    'Excused' => '9E7CC3'
];

foreach ($records as $i => $record) {
    $sheet->setCellValue("A$row", $i + 1);
    $sheet->setCellValue("B$row", $record['course_name']);
    $sheet->setCellValue("C$row", $record['section']);
    $sheet->setCellValue("D$row", $record['set_group'] ?? 'N/A');
    $sheet->setCellValue("E$row", $record['attendance_date']);
    $sheet->setCellValue("F$row", $record['attendance_time'] ? date('g:i A', strtotime($record['attendance_time'])) : '');
    $sheet->setCellValue("G$row", $record['timestamp'] ? date('g:i A', strtotime($record['timestamp'])) : '');
    $sheet->setCellValue("H$row", $record['status']);

    if (isset($statusColorMap[$record['status']])) {
        $sheet->getStyle("H$row")->getFill()->setFillType(Fill::FILL_SOLID)
              ->getStartColor()->setRGB($statusColorMap[$record['status']]);
    }

    $row++;
}

// Style data rows
$sheet->getStyle("A4:H" . ($row - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle("A4:H" . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
foreach (range('A', 'H') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Summary
$summaryStart = $row + 2;
$sheet->setCellValue("A$summaryStart", 'Attendance Summary:');
$sheet->getStyle("A$summaryStart")->getFont()->setBold(true)->setSize(12);

$i = 1;
foreach ($statusCount as $status => $count) {
    $sr = $summaryStart + $i;
    $sheet->setCellValue("A$sr", $status);
    $sheet->setCellValue("B$sr", $count);
    $sheet->getStyle("A$sr:B$sr")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("A$sr:B$sr")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

    if (isset($statusColorMap[$status])) {
        $sheet->getStyle("A$sr")->getFill()->setFillType(Fill::FILL_SOLID)
              ->getStartColor()->setRGB($statusColorMap[$status]);
    }

    $i++;
}

// Chart
$categoryRange = "'Attendance'!\$A" . ($summaryStart + 1) . ":\$A" . ($summaryStart + 4);
$valueRange = "'Attendance'!\$B" . ($summaryStart + 1) . ":\$B" . ($summaryStart + 4);

$categories = [new DataSeriesValues('String', $categoryRange, null, 4)];
$values = [new DataSeriesValues('Number', $valueRange, null, 4)];

$series = new DataSeries(DataSeries::TYPE_PIECHART, null, [0], [], $categories, $values);
$plotArea = new PlotArea(null, [$series]);
$legend = new Legend(Legend::POSITION_RIGHT, null, false);
$chartTitle = new Title('Attendance Status Distribution');
$chart = new Chart('Pie Chart', $chartTitle, $legend, $plotArea);

$chart->setTopLeftPosition('D' . ($summaryStart + 1));
$chart->setBottomRightPosition('L' . ($summaryStart + 15));
$sheet->addChart($chart);

// Build filename
$datePart = '';
if (!empty($attendance_date)) {
    $datePart = "_Date_" . date('Y-m-d', strtotime($attendance_date));
} elseif (!empty($attendance_month)) {
    $datePart = "_Month_" . date('Y-m', strtotime($attendance_month . "-01"));
}
if (!empty($status_filter)) {
    $datePart .= "_Status_" . preg_replace('/[^a-zA-Z0-9]/', '', $status_filter);
}

$sanitized_student = preg_replace('/[^a-zA-Z0-9_]/', '_', $student_name);
$sanitized_course = preg_replace('/[^a-zA-Z0-9_]/', '_', $course_name);
$sanitized_section = preg_replace('/[^a-zA-Z0-9_]/', '_', $section);

$filename = "Attendance_Report_{$sanitized_student}_{$sanitized_course}_{$sanitized_section}{$datePart}.xlsx";

// Output file
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->setIncludeCharts(true);
$writer->save('php://output');
exit;
?>

<?php
session_start();
include '../../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require '../../vendor/autoload.php';

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

// Validate GET parameters
if (!isset($_GET['course_name'], $_GET['section'], $_GET['set_group'], $_GET['attendance_date'], $_GET['attendance_time'])) {
    die('Missing required parameters.');
}

// Sanitize input
$course_name = htmlspecialchars($_GET['course_name']);
$section = htmlspecialchars($_GET['section']);
$set_group = htmlspecialchars($_GET['set_group']);
$attendance_date = htmlspecialchars($_GET['attendance_date']);
$attendance_time_raw = htmlspecialchars($_GET['attendance_time']);

if (!strtotime($attendance_time_raw)) {
    die('Invalid attendance time format.');
}

$formatted_attendance_time = date('g:i A', strtotime($attendance_time_raw));

// Fetch attendance data
try {
    $sql = "
        SELECT a.attendance_id, s.school_student_id, s.student_name, a.set_group, a.timestamp, a.status, 
               c.course_name, c.section
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

} catch (PDOException $e) {
    die("Error fetching attendance data: " . $e->getMessage());
}

// Count attendance statuses
$statusCount = [
    'Present' => 0,
    'Late' => 0,
    'Absent' => 0,
    'Excused' => 0
];

foreach ($attendance_details as $record) {
    if (isset($statusCount[$record['status']])) {
        $statusCount[$record['status']]++;
    }
}

// Initialize Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Title Row
$sheet->mergeCells('A1:H1');
$sheet->setCellValue('A1', "Attendance Report for $course_name - $section - $set_group - $attendance_date - $formatted_attendance_time");
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Add border to the title cell
$sheet->getStyle('A1:H1')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

// Header Row
$headers = ['#', 'School Student ID', 'Student Name', 'Course', 'Section', 'Set Group', 'Timestamp', 'Status'];
$sheet->fromArray($headers, NULL, 'A3');
$sheet->getStyle('A3:H3')->getFont()->setBold(true);
// Apply AutoFilter
$sheet->setAutoFilter('A3:H3');

// Add attendance data
$row = 4;
$count = 1;
$statusColorMap = [
    'Present' => '4472C4',
    'Late'    => 'ED7D31',
    'Absent'  => 'A9D18E',
    'Excused' => '9E7CC3'
];

foreach ($attendance_details as $record) {
    $formatted_timestamp = $record['timestamp'] ? date('g:i A', strtotime($record['timestamp'])) : '';

    $sheet->setCellValue('A' . $row, $count++)
          ->setCellValue('B' . $row, $record['school_student_id'])
          ->setCellValue('C' . $row, $record['student_name'])
          ->setCellValue('D' . $row, $record['course_name'])
          ->setCellValue('E' . $row, $record['section'])
          ->setCellValue('F' . $row, $record['set_group'])
          ->setCellValue('G' . $row, $formatted_timestamp)
          ->setCellValue('H' . $row, $record['status']);

    // Apply background color based on status
    if (isset($statusColorMap[$record['status']])) {
        $sheet->getStyle('H' . $row)->getFill()->setFillType(Fill::FILL_SOLID)
              ->getStartColor()->setRGB($statusColorMap[$record['status']]);
    }

    $row++;
}

// Apply borders and alignment
$sheet->getStyle('A4:H' . ($row - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle('A4:H' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Auto-size columns
foreach (range('A', 'H') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Attendance Summary
$summaryStartRow = $row + 2;
$sheet->setCellValue('A' . $summaryStartRow, 'Attendance Summary:');
$sheet->getStyle('A' . $summaryStartRow)->getFont()->setBold(true)->setSize(12);

// Insert Summary
$statuses = ['Present', 'Late', 'Absent', 'Excused'];
foreach ($statuses as $i => $status) {
    $sheet->setCellValue('A' . ($summaryStartRow + 1 + $i), $status);
    $sheet->setCellValue('B' . ($summaryStartRow + 1 + $i), $statusCount[$status]);
    $sheet->getStyle('A' . ($summaryStartRow + 1 + $i) . ':B' . ($summaryStartRow + 1 + $i))
          ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
}

// Apply border and color to summary
$summaryEndRow = $summaryStartRow + 4;
$sheet->getStyle('A' . ($summaryStartRow + 1) . ':B' . $summaryEndRow)
    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

// Background color for summary rows
foreach ($statuses as $i => $status) {
    $currentRow = $summaryStartRow + 1 + $i;
    if (isset($statusColorMap[$status])) {
        $sheet->getStyle('A' . $currentRow)
              ->getFill()->setFillType(Fill::FILL_SOLID)
              ->getStartColor()->setRGB($statusColorMap[$status]);
    }
}

// Create Pie Chart
$categories = [
    new DataSeriesValues('String', "'Worksheet'!\$A" . ($summaryStartRow + 1) . ":\$A" . ($summaryStartRow + 4), null, 4)
];
$values = [
    new DataSeriesValues('Number', "'Worksheet'!\$B" . ($summaryStartRow + 1) . ":\$B" . ($summaryStartRow + 4), null, 4)
];

$series = new DataSeries(
    DataSeries::TYPE_PIECHART,
    null,
    [0],
    [],
    $categories,
    $values
);

$plotArea = new PlotArea(null, [$series]);
$legend = new Legend(Legend::POSITION_RIGHT, null, false);
$title = new Title('Attendance Status Distribution');

$chart = new Chart(
    'Attendance Pie Chart',
    $title,
    $legend,
    $plotArea
);

// Position the chart
$chart->setTopLeftPosition('D' . ($summaryStartRow + 1));
$chart->setBottomRightPosition('L' . ($summaryStartRow + 15));
$sheet->addChart($chart);

// Output Excel
$filename_time = str_replace([':', ' '], ['-', ''], $formatted_attendance_time);
$filename = "Attendance_Report_{$course_name}_{$section}_{$attendance_date}_{$filename_time}.xlsx";
$filename = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $filename);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->setIncludeCharts(true);
$writer->save('php://output');
exit;
?>

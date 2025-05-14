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

// Validate required GET parameters
if (!isset($_GET['student_id'], $_GET['course_name'], $_GET['section'])) {
    die('Missing required parameters.');
}

$student_id = htmlspecialchars($_GET['student_id']);
$course_name = htmlspecialchars($_GET['course_name']);
$section = htmlspecialchars($_GET['section']);
$month = isset($_GET['month']) && preg_match('/^\d{4}-\d{2}$/', $_GET['month']) ? $_GET['month'] : null;
$status = isset($_GET['status']) && in_array(ucfirst(strtolower($_GET['status'])), ['Present', 'Late', 'Absent', 'Excused']) ? ucfirst(strtolower($_GET['status'])) : null;

try {
    // Build query
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
              AND c.section = :section";

    $params = [
        ':student_id' => $student_id,
        ':course_name' => $course_name,
        ':section' => $section
    ];

    if ($month) {
        $sql .= " AND DATE_FORMAT(a.attendance_date, '%Y-%m') = :month";
        $params[':month'] = $month;
    }

    if ($status) {
        $sql .= " AND a.status = :status";
        $params[':status'] = $status;
    }

    $sql .= " ORDER BY a.attendance_date DESC, a.attendance_time DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $StudentReport = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($StudentReport) == 0) {
        die('No attendance data found.');
    }

    // Get student name
    $stmt2 = $conn->prepare("SELECT student_name FROM students WHERE student_id = :student_id");
    $stmt2->bindParam(':student_id', $student_id);
    $stmt2->execute();
    $student = $stmt2->fetch(PDO::FETCH_ASSOC);
    $student_name = $student ? $student['student_name'] : 'Unknown';

} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}

// Count attendance statuses
$statusCount = ['Present' => 0, 'Late' => 0, 'Absent' => 0, 'Excused' => 0];
foreach ($StudentReport as $record) {
    if (isset($statusCount[$record['status']])) {
        $statusCount[$record['status']]++;
    }
}

// Create Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Title
$sheet->mergeCells('A1:H1');
$filterInfo = ($month ? " | Month: $month" : "") . ($status ? " | Status: $status" : "");
$sheet->setCellValue('A1', "Attendance Report for $student_name - $course_name - Section $section$filterInfo");
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A1:H1')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

// Headers
$headers = ['#', 'Date', 'Time', 'Timestamp', 'Course', 'Section', 'Set Group', 'Status'];
$sheet->fromArray($headers, null, 'A3');
$sheet->getStyle('A3:H3')->getFont()->setBold(true);
$sheet->setAutoFilter('A3:H3');

// Attendance Data
$row = 4;
$count = 1;
$statusColorMap = [
    'Present' => '4472C4',
    'Late'    => 'E74C3C',
    'Absent'  => 'A9D18E',
    'Excused' => '9E7CC3'
];

foreach ($StudentReport as $record) {
    $formatted_time = $record['attendance_time'] ? date('g:i A', strtotime($record['attendance_time'])) : '';
    $formatted_timestamp = $record['timestamp'] ? date('g:i A', strtotime($record['timestamp'])) : '';

    $sheet->setCellValue('A' . $row, $count++)
          ->setCellValue('B' . $row, $record['attendance_date'])
          ->setCellValue('C' . $row, $formatted_time)
          ->setCellValue('D' . $row, $formatted_timestamp)
          ->setCellValue('E' . $row, $record['course_name'])
          ->setCellValue('F' . $row, $record['section'])
          ->setCellValue('G' . $row, $record['set_group'] ?? 'N/A')
          ->setCellValue('H' . $row, $record['status']);

    if (isset($statusColorMap[$record['status']])) {
        $sheet->getStyle('H' . $row)->getFill()->setFillType(Fill::FILL_SOLID)
              ->getStartColor()->setRGB($statusColorMap[$record['status']]);
    }

    $row++;
}

// Format table
$sheet->getStyle("A4:H" . ($row - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle("A4:H" . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
foreach (range('A', 'H') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Summary
$summaryStartRow = $row + 2;
$sheet->setCellValue("A$summaryStartRow", 'Attendance Summary:');
$sheet->getStyle("A$summaryStartRow")->getFont()->setBold(true)->setSize(12);

$statuses = array_keys($statusCount);
foreach ($statuses as $i => $status) {
    $summaryRow = $summaryStartRow + $i + 1;
    $sheet->setCellValue("A$summaryRow", $status);
    $sheet->setCellValue("B$summaryRow", $statusCount[$status]);

    $sheet->getStyle("A$summaryRow:B$summaryRow")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("A$summaryRow:B$summaryRow")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

    if (isset($statusColorMap[$status])) {
        $sheet->getStyle("A$summaryRow")->getFill()->setFillType(Fill::FILL_SOLID)
              ->getStartColor()->setRGB($statusColorMap[$status]);
    }
}

// Chart
$categoryRange = "'Worksheet'!\$A" . ($summaryStartRow + 1) . ":\$A" . ($summaryStartRow + 4);
$valueRange = "'Worksheet'!\$B" . ($summaryStartRow + 1) . ":\$B" . ($summaryStartRow + 4);

$categories = [new DataSeriesValues('String', $categoryRange, null, 4)];
$values = [new DataSeriesValues('Number', $valueRange, null, 4)];

$series = new DataSeries(DataSeries::TYPE_PIECHART, null, [0], [], $categories, $values);
$plotArea = new PlotArea(null, [$series]);
$legend = new Legend(Legend::POSITION_RIGHT, null, false);
$chartTitle = new Title('Attendance Status Distribution');
$chart = new Chart('Attendance Pie Chart', $chartTitle, $legend, $plotArea);

$chart->setTopLeftPosition('D' . ($summaryStartRow + 1));
$chart->setBottomRightPosition('L' . ($summaryStartRow + 15));
$sheet->addChart($chart);

// Export
$filename = "Student_Attendance_Report_{$student_name}.xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->setIncludeCharts(true);
$writer->save('php://output');
exit;

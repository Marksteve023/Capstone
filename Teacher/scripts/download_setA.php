<?php
// Include necessary PHPSpreadsheet classes
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

include '../../config/db.php';
require '../../vendor/autoload.php';

if (!isset($_GET['course_id'])) {
    http_response_code(400);  
    die("Course ID is required.");
}

$course_id = $_GET['course_id'];

// Fetch course details
$sql = "SELECT course_name, section FROM courses WHERE course_id = :course_id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
$stmt->execute();
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    http_response_code(404);  // Not Found error
    die("Course not found.");
}

$filename = $course['course_name'] . '_' . $course['section'] . 'Set-A';
$filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $filename); // Sanitize filename

// Create a new Spreadsheet object
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set headers for Excel
$sheet->setCellValue('A1', '#')
      ->setCellValue('B1', 'Student ID')
      ->setCellValue('C1', 'Student Name')
      ->setCellValue('D1', 'Course')
      ->setCellValue('E1', 'Section')
      ->setCellValue('F1', 'Academic Year')
      ->setCellValue('G1', 'Set Group');

// Style header (bold, center-aligned horizontally and vertically)
$sheet->getStyle('A1:G1')->getFont()->setBold(true);
$sheet->getStyle('A1:G1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A1:G1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

// Fetch student data
$sql = "SELECT DISTINCT sc.student_course_id, 
                TRIM(s.school_student_id) AS school_student_id, 
                TRIM(s.student_name) AS student_name, 
                TRIM(c.course_name) AS course_name, 
                TRIM(c.section) AS section, 
                TRIM(c.academic_year) AS academic_year, 
                TRIM(sc.set_group) AS set_group
        FROM student_courses sc
        INNER JOIN students s ON sc.student_id = s.student_id
        INNER JOIN courses c ON sc.course_id = c.course_id AND sc.set_group = 'Set A'
        WHERE sc.course_id = :course_id
        ORDER BY s.student_name ASC";

$stmt = $conn->prepare($sql);
$stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Start inserting student data into the Excel file
$row = 2; // Start from row 2 since row 1 has the headers
$index = 1;
foreach ($students as $student) {
    $sheet->setCellValue('A' . $row, $index++) 
          ->setCellValue('B' . $row, trim($student['school_student_id']))
          ->setCellValue('C' . $row, trim($student['student_name']))
          ->setCellValue('D' . $row, trim($student['course_name']))
          ->setCellValue('E' . $row, trim($student['section']))
          ->setCellValue('F' . $row, trim($student['academic_year']))
          ->setCellValue('G' . $row, trim($student['set_group']) ?: 'N/A');
    $row++;
}

// Center align the entire data table (including rows from 2 onward)
$sheet->getStyle('A2:G' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A2:G' . ($row - 1))->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

// Add borders around the table
$tableRange = 'A1:G' . ($row - 1); // Adjusting for the data rows
$sheet->getStyle($tableRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

// Set column widths for better readability
$sheet->getColumnDimension('A')->setWidth(5);
$sheet->getColumnDimension('B')->setWidth(12);
$sheet->getColumnDimension('C')->setWidth(25);
$sheet->getColumnDimension('D')->setWidth(15);
$sheet->getColumnDimension('E')->setWidth(10);
$sheet->getColumnDimension('F')->setWidth(18);
$sheet->getColumnDimension('G')->setWidth(12);

// Write the Excel file to output
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
?>

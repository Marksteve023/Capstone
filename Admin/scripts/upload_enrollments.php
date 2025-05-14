<?php
session_start(); // Ensure session is started
require '../../vendor/autoload.php';
include '../../config/db.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (isset($_POST['submit'])) {
    $filePath = $_FILES['file']['tmp_name'];

    try {
        // Determine file type
        $fileExtension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        $rows = [];

        if ($fileExtension === 'csv') {
            if (($handle = fopen($filePath, "r")) !== false) {
                while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                    $rows[] = $data;
                }
                fclose($handle);
            }
        } else {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
        }

        // Prepare SQL queries
        $getStudentId = $conn->prepare("SELECT student_id FROM students WHERE school_student_id = :school_id");
        $getCourseIds = $conn->prepare("SELECT course_id FROM courses WHERE section = :section");
        $checkEnrollment = $conn->prepare("SELECT COUNT(*) FROM student_courses WHERE student_id = :student_id AND course_id = :course_id");
        $insertEnrollment = $conn->prepare("INSERT INTO student_courses (student_id, course_id, set_group) VALUES (:student_id, :course_id, :set_group)");

        $set_group = 'N/A';
        $inserted = 0;
        $skipped = 0;

        // Loop through data (skip header row)
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];

            // Check if required columns exist
            if (count($row) < 3 || empty($row[0]) || empty($row[2])) {
                $skipped++;
                continue;
            }

            $school_id = trim($row[0]); // School student ID
            $section = trim($row[2]);   // Section

            // Get student ID
            $getStudentId->execute([':school_id' => $school_id]);
            $student = $getStudentId->fetch();

            if (!$student) {
                $skipped++;
                continue;
            }

            // Get all course IDs for the section
            $getCourseIds->execute([':section' => $section]);
            $courses = $getCourseIds->fetchAll();

            if (!$courses) {
                $skipped++;
                continue;
            }

            foreach ($courses as $course) {
                $checkEnrollment->execute([
                    ':student_id' => $student['student_id'],
                    ':course_id' => $course['course_id']
                ]);

                if ($checkEnrollment->fetchColumn() == 0) {
                    $insertEnrollment->execute([
                        ':student_id' => $student['student_id'],
                        ':course_id' => $course['course_id'],
                        ':set_group' => $set_group
                    ]);
                    $inserted++;
                } else {
                    $skipped++;
                }
            }
        }

        $_SESSION['message'] = "Enrollment upload successful!<br>Inserted: $inserted<br>Skipped (already enrolled or invalid): $skipped";
        header("Location: ../enrollment.php");
        exit;

    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header("Location: ../enrollment.php");
        exit;
    }
}
?>
    
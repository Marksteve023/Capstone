<?php
session_start();
require '../../vendor/autoload.php';
include '../../config/db.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (isset($_POST['submit'])) {
    $filePath = $_FILES['file']['tmp_name'];

    try {
        // Check file extension to determine if it's an Excel or CSV file
        $fileExtension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        $rows = [];

        if ($fileExtension === 'csv') {
            // Read CSV file
            if (($handle = fopen($filePath, "r")) !== false) {
                while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                    $rows[] = $data;
                }
                fclose($handle);
            }
        } else {
            // Read Excel file
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
        }

        // Prepare SQL queries
        $checkExisting = $conn->prepare("SELECT COUNT(*) FROM students WHERE school_student_id = :school_id");
        $stmt = $conn->prepare("INSERT INTO students 
            (school_student_id, student_name, rfid_tag, program, year_level, email, password) 
            VALUES (:school_id, :name, :rfid, :program, :year, :email, :password)");

        $inserted = 0;
        $skipped = 0;

        // Skip the header row and loop through the rest
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];

            // Check for missing data
            if (empty($row[0]) || empty($row[1]) || empty($row[2]) || empty($row[3]) || empty($row[4]) || empty($row[5]) || empty($row[6])) {
                $_SESSION['error'] = "Missing required data at row $i. Please check the file.";
                header("Location: ../manage-students.php");
                exit;
            }

            // Check if student already exists
            $checkExisting->execute([':school_id' => $row[0]]);
            if ($checkExisting->fetchColumn() > 0) {
                $skipped++;
                continue;
            }

            // Insert student data
            $stmt->execute([
                ':school_id' => $row[0],
                ':name' => $row[1],
                ':rfid' => $row[2],
                ':program' => $row[3],
                ':year' => $row[4],
                ':email' => $row[5],
                ':password' => password_hash($row[6], PASSWORD_DEFAULT)
            ]);
            $inserted++;
        }

        $_SESSION['message'] = "Upload successful! Inserted: $inserted, Skipped (duplicates): $skipped";
        header("Location: ../manage-students.php");
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header("Location: ../manage-students.php");
        exit;
    }
}
?>

<?php
session_start();
require '../../vendor/autoload.php';
include '../../config/db.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (isset($_POST['submit'])) {
    $filePath = $_FILES['file']['tmp_name'];

    try {
        $fileExtension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        $rows = [];

        // Read file
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

        // Prepare update statement
        $updateStmt = $conn->prepare("UPDATE students SET rfid_tag = :rfid WHERE school_student_id = :school_id");

        $updated = 0;
        $skipped = 0;

        // Loop through rows (skip header)
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $school_id = trim($row[0]);
            $rfid_tag = trim($row[1]);

            // Validate
            if (empty($school_id) || empty($rfid_tag)) {
                continue; // Skip incomplete rows
            }

            // Execute update
            $updateStmt->execute([
                ':rfid' => $rfid_tag,
                ':school_id' => $school_id
            ]);

            if ($updateStmt->rowCount() > 0) {
                $updated++;
            } else {
                $skipped++;
            }
        }

        $_SESSION['message'] = "RFID update completed. Updated: $updated | Skipped (not found or unchanged): $skipped";
        header("Location: ../manage-students.php");
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header("Location: ../manage-students.php");
        exit;
    }
}
?>

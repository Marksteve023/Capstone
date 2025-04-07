<?php
include '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['students'])) {
    $students = json_decode($_POST['students'], true);

    if (!empty($students)) {
        foreach ($students as $student) {
            $sql = "UPDATE student_courses SET set_group = :set_group WHERE student_course_id = :student_course_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':set_group', $student['set_group'], PDO::PARAM_STR);
            $stmt->bindParam(':student_course_id', $student['student_course_id'], PDO::PARAM_INT);
            $stmt->execute();
        }
        echo "Set groups updated successfully!";
    } else {
        echo "No valid data received.";
    }
} else {
    echo "Invalid request.";
}
?>

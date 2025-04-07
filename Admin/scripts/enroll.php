<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $conn->beginTransaction(); // Start transaction

        // Get form data
        $edit_enrollment_id = isset($_POST['edit_enrollment_id']) && is_numeric($_POST['edit_enrollment_id']) ? (int) $_POST['edit_enrollment_id'] : null;
        $re_enroll_id = isset($_POST['re_enroll_id']) && is_numeric($_POST['re_enroll_id']) ? (int) $_POST['re_enroll_id'] : null;
        $school_student_id = trim($_POST['school_student_id']);
        $student_name = trim($_POST['student_name']);
        $course_ids = isset($_POST['course_id']) ? $_POST['course_id'] : [];

        // Validate required fields
        if (empty($school_student_id) || empty($student_name)) {
            $_SESSION['error'] = "You must provide a student name and student ID.";
            header("Location: ../enrollment.php");
            exit();
        }

        // Fetch student ID using school_student_id
        $stmt = $conn->prepare("SELECT student_id FROM students WHERE school_student_id = ?");
        $stmt->execute([$school_student_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            $_SESSION['error'] = "Student not found.";
            header("Location: ../enrollment.php");
            exit();
        }

        $student_id = $student['student_id'];

        // Check existing enrollments
        $stmt = $conn->prepare("SELECT course_id FROM student_courses WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $existing_courses = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // If editing enrollment
        if ($edit_enrollment_id) {
            $new_courses = array_diff($course_ids, $existing_courses);
            $removed_courses = array_diff($existing_courses, $course_ids);

            if (empty($new_courses) && empty($removed_courses)) {
                $_SESSION['message'] = "No changes detected in enrollment.";
                header("Location: ../enrollment.php");
                exit();
            }

            // Remove previous courses
            $stmt = $conn->prepare("DELETE FROM student_courses WHERE student_id = ?");
            $stmt->execute([$student_id]);

            // Insert new courses
            $stmt = $conn->prepare("INSERT INTO student_courses (student_id, course_id, enrolled_at) VALUES (?, ?, NOW())");
            foreach ($course_ids as $course_id) {
                $stmt->execute([$student_id, $course_id]);
            }

            $conn->commit();
            $_SESSION['message'] = "Enrollment updated successfully!";
            header("Location: ../enrollment.php");
            exit();
        }

        // If re-enrolling
        if ($re_enroll_id) {
            $stmt = $conn->prepare("DELETE FROM student_courses WHERE student_course_id = ?");
            $stmt->execute([$re_enroll_id]);

            // Check if new courses are the same as before
            if (empty(array_diff($course_ids, $existing_courses))) {
                $_SESSION['message'] = "No changes detected in re-enrollment.";
                header("Location: ../enrollment.php");
                exit();
            }
        }

        // Prevent duplicate enrollments
        if (!empty(array_intersect($course_ids, $existing_courses))) {
            $_SESSION['error'] = "Student is already enrolled in one or more selected courses.";
            header("Location: ../enrollment.php");
            exit();
        }

        // Insert new enrollment
        $stmt = $conn->prepare("INSERT INTO student_courses (student_id, course_id, enrolled_at) VALUES (?, ?, NOW())");
        foreach ($course_ids as $course_id) {
            $stmt->execute([$student_id, $course_id]);
        }

        $conn->commit(); // Commit transaction
        $_SESSION['message'] = "Enrollment successful!";
        header("Location: ../enrollment.php");
        exit();
    } catch (Exception $e) {
        $conn->rollBack(); // Rollback transaction on error
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header("Location: ../enrollment.php");
        exit();
    }
} else {
    $_SESSION['error'] = "Invalid request.";
    header("Location: ../enrollment.php");
    exit();
}
?>

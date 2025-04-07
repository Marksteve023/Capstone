<?php
session_start();
include '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Sanitize inputs
        $course_name = strtoupper($_POST['course_name'] ?? '');
        $section = strtoupper(trim($_POST['section'] ?? ''));
        $semester = trim($_POST['semester'] ?? '');
        $academic_year = trim($_POST['academic_year'] ?? '');
        $course_id = $_POST['course_id'] ?? null;

        // Validate required fields
        if (empty($course_name) || empty($section) || empty($semester) || empty($academic_year)) {
            $_SESSION['error'] = 'All fields are required!';
            header("Location: ../course-section.php");
            exit;
        }

        // Validate academic year format (YYYY-YYYY)
        if (!preg_match('/^\d{4}-\d{4}$/', $academic_year)) {
            $_SESSION['error'] = 'Invalid academic year format! (Expected: YYYY-YYYY)';
            header("Location: ../course-section.php");
            exit;
        }

        // Validate semester
        $valid_semesters = ['1st Trimester', '2nd Trimester', '3rd Trimester'];
        if (!in_array($semester, $valid_semesters, true)) {
            $_SESSION['error'] = 'Invalid semester value!';
            header("Location: ../course-section.php");
            exit;
        }

        if ($course_id) {
            // Check for duplicates excluding the current course
            $stmt = $conn->prepare("SELECT course_id FROM courses WHERE course_name = :course_name AND section = :section AND course_id != :course_id");
            $stmt->execute([':course_name' => $course_name, ':section' => $section, ':course_id' => $course_id]);

            if ($stmt->rowCount() > 0) {
                $_SESSION['error'] = 'A course with this name and section already exists!';
                header("Location: ../course-section.php");
                exit;
            }

            // Update course
            $stmt = $conn->prepare("UPDATE courses SET course_name = :course_name, section = :section, semester = :semester, academic_year = :academic_year WHERE course_id = :course_id");
            $stmt->execute([':course_id' => $course_id, ':course_name' => $course_name, ':section' => $section, ':semester' => $semester, ':academic_year' => $academic_year]);

            $_SESSION['message'] = ($stmt->rowCount() > 0) ? 'Course updated successfully!' : 'No changes made!';
        } else {
            // Check if course already exists
            $stmt = $conn->prepare("SELECT course_id FROM courses WHERE course_name = :course_name AND section = :section");
            $stmt->execute([':course_name' => $course_name, ':section' => $section]);

            if ($stmt->rowCount() > 0) {
                $_SESSION['error'] = 'A course with this name and section already exists!';
                header("Location: ../course-section.php");
                exit;
            }

            // Insert new course
            $stmt = $conn->prepare("INSERT INTO courses (course_name, section, semester, academic_year) VALUES (:course_name, :section, :semester, :academic_year)");
            $stmt->execute([':course_name' => $course_name, ':section' => $section, ':semester' => $semester, ':academic_year' => $academic_year]);

            $_SESSION['message'] = ($stmt->rowCount() > 0) ? 'Course created successfully!' : 'Course creation failed!';
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $_SESSION['error'] = 'An unexpected error occurred. Please try again later.';
    }

    header("Location: ../course-section.php");
    exit;
}
?>

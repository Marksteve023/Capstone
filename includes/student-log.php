<?php
session_start();
require_once __DIR__ . '/../config/db.php';  

if (isset($_POST['studentLogIn'])) {
    $school_student_id = trim($_POST['school_student_id']);
    $password = $_POST['password'];

    // Validate input
    if (empty($school_student_id) || empty($password)) {
        $_SESSION['student_error'] = "All fields are required.";
        header("Location: ../login.php");
        exit;
    }

    try {
        // Prepare and execute the query using PDO
        $stmt = $conn->prepare("SELECT * FROM students WHERE school_student_id = :school_id");
        $stmt->execute(['school_id' => $school_student_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student) {
            if (password_verify($password, $student['password'])) {
                $_SESSION['student_id'] = $student['student_id'];
                $_SESSION['school_student_id'] = $student['school_student_id'];
                $_SESSION['student_name'] = $student['student_name'];

                header("Location: ../Student/dashboard.php");
                exit;
            } else {
                $_SESSION['student_error'] = "Incorrect password.";
            }
        } else {
            $_SESSION['student_error'] = "Student ID not found.";
        }

    } catch (PDOException $e) {
        // Optional: log this error in production
        $_SESSION['student_error'] = "Login failed. Please try again.";
    }

    header("Location: ../login.php");
    exit;
} else {
    header("Location: ../login.php");
    exit;
}

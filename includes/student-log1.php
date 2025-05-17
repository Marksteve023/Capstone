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
        $stmt = $conn->prepare("SELECT * FROM students WHERE school_student_id = :school_id");
        $stmt->execute(['school_id' => $school_student_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student) {
            // Lockout settings
            $lockout_duration = 10 * 60; // 10 minutes in seconds
            $now = time();
            $last_failed = strtotime($student['last_failed_login']);

            // Check for lockout
            if ($student['failed_attempts'] >= 5 && ($now - $last_failed) < $lockout_duration) {
                $unlock_time = date("g:i A", $last_failed + $lockout_duration); // 12-hour format
                $_SESSION['student_error'] = "Account locked due to multiple failed login attempts. Try again at $unlock_time.";
                header("Location: ../login.php");
                exit;
            }

            // Verify password
            if (password_verify($password, $student['password'])) {
                // Reset failed attempts
                $reset = $conn->prepare("UPDATE students SET failed_attempts = 0, last_failed_login = NULL WHERE student_id = :id");
                $reset->execute(['id' => $student['student_id']]);

                // Set session
                $_SESSION['student_id'] = $student['student_id'];
                $_SESSION['school_student_id'] = $student['school_student_id'];
                $_SESSION['student_name'] = $student['student_name'];

                header("Location: ../Student/dashboard.php");
                exit;
            } else {
                // Increment failed attempts
                $update = $conn->prepare("UPDATE students SET failed_attempts = failed_attempts + 1, last_failed_login = NOW() WHERE student_id = :id");
                $update->execute(['id' => $student['student_id']]);
                $_SESSION['student_error'] = "Incorrect password.";
            }
        } else {
            $_SESSION['student_error'] = "Student ID not found.";
        }

    } catch (PDOException $e) {
        $_SESSION['student_error'] = "Login failed. Please try again.";
    }

    header("Location: ../login.php");
    exit;
} else {
    header("Location: ../login.php");
    exit;
}

?>
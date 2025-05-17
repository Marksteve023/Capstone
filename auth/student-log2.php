<?php
session_start();

// Set timezone explicitly (example: Manila timezone)
date_default_timezone_set('Asia/Manila');

include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // ✅ CSRF token check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Invalid CSRF token!";
        header("Location: ../login/student-login.php");
        exit;
    }

    $school_student_id = trim($_POST['school_student_id'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($school_student_id) || empty($password)) {
        $_SESSION['error'] = "All fields are required!";
        header("Location: ../login/student-login.php");
        exit;
    } else {
        try {
            $sql = "SELECT student_id, school_student_id, password, failed_attempts, lock_until
                    FROM students 
                    WHERE school_student_id = :student_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':student_id', $school_student_id);
            $stmt->execute();
            $student = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($student) {
                $now = new DateTime();
                $lock_until = $student['lock_until'] ? new DateTime($student['lock_until']) : null;

                // 🔒 Check if account is locked
                if ($lock_until && $now < $lock_until) {
                    $unlock_time = $lock_until->format("g:i A");
                    $_SESSION['error'] = "Account locked due to multiple failed login attempts. Try again at $unlock_time.";
                    header("Location: ../login/student-login.php");
                    exit;
                }

                // ✅ Correct password
                if (password_verify($password, $student['password'])) {
                    // Reset failed attempts and lock_until
                    $reset_sql = "UPDATE students SET failed_attempts = 0, lock_until = NULL WHERE student_id = :student_id";
                    $reset_stmt = $conn->prepare($reset_sql);
                    $reset_stmt->bindParam(':student_id', $student['student_id']);
                    $reset_stmt->execute();

                    // ✅ Set session and unset CSRF token after successful login
                    $_SESSION['student_id'] = $student['student_id'];
                    $_SESSION['school_student_id'] = $student['school_student_id'];
                    unset($_SESSION['csrf_token']);

                    header("Location: ../Student/dashboard.php");
                    exit;
                } else {
                    // ❌ Wrong password
                    $failed_attempts = $student['failed_attempts'] + 1;
                    $lock_duration_minutes = 5;

                    if ($failed_attempts >= 6) {
                        $lock_until_new = (new DateTime())->modify("+$lock_duration_minutes minutes")->format('Y-m-d H:i:s');
                        $update_sql = "UPDATE students SET failed_attempts = :attempts, lock_until = :lock_until WHERE student_id = :student_id";
                        $update_stmt = $conn->prepare($update_sql);
                        $update_stmt->bindParam(':attempts', $failed_attempts);
                        $update_stmt->bindParam(':lock_until', $lock_until_new);
                        $update_stmt->bindParam(':student_id', $student['student_id']);
                    } else {
                        $update_sql = "UPDATE students SET failed_attempts = :attempts WHERE student_id = :student_id";
                        $update_stmt = $conn->prepare($update_sql);
                        $update_stmt->bindParam(':attempts', $failed_attempts);
                        $update_stmt->bindParam(':student_id', $student['student_id']);
                    }

                    $update_stmt->execute();
                    
                   $_SESSION['error'] = "Invalid email or password. You can use 'Forgot Password' to reset your credentials.";
                    header("Location: ../login/student-login.php");
                    exit;
                }

            } else {
                $_SESSION['error'] = "Invalid Student ID or password.";
                header("Location: ../login/student-login.php");
                exit;
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
            header("Location: ../login/student-login.php");
            exit;
        }
    }
}
?>

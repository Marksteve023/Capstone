<?php
session_start();
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // ✅ CSRF token check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Invalid CSRF token!";
        header("Location: ../login.php");
        exit;
    }

    $role = trim($_POST['role'] ?? '');
    $email_add = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST['password'] ?? '');

    if (empty($role) || empty($email_add) || empty($password)) {
        $_SESSION['error'] = "All fields are required!";
        header("Location: ../login.php");
        exit;
    } elseif (!filter_var($email_add, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format!";
        header("Location: ../login.php");
        exit;
    } else {
        try {
            $sql = "SELECT user_id, email, password, role, failed_attempts, last_failed_login 
                    FROM users 
                    WHERE email = :email AND role = :role";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':email', $email_add);
            $stmt->bindParam(':role', $role);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $lockout_duration = 5 * 60; // ⏱ 5 minutes
                $now = time();
                $last_failed_time = strtotime($user['last_failed_login']);

                // 🔄 Reset failed attempts if lockout expired
                if ($user['failed_attempts'] >= 5 && ($now - $last_failed_time) >= $lockout_duration) {
                    $reset_sql = "UPDATE users SET failed_attempts = 0, last_failed_login = NULL WHERE user_id = :user_id";
                    $reset_stmt = $conn->prepare($reset_sql);
                    $reset_stmt->bindParam(':user_id', $user['user_id']);
                    $reset_stmt->execute();

                    $user['failed_attempts'] = 0;
                    $user['last_failed_login'] = null;
                }

                // 🔒 Still locked?
                if ($user['failed_attempts'] >= 5 && ($now - $last_failed_time) < $lockout_duration) {
                    $unlock_time = date("g:i A", $last_failed_time + $lockout_duration);
                    $_SESSION['error'] = "Account locked due to multiple failed login attempts. Try again at $unlock_time.";
                    header("Location: ../login.php");
                    exit;
                }

                // ✅ Correct password
                if (password_verify($password, $user['password'])) {
                    $reset_sql = "UPDATE users SET failed_attempts = 0, last_failed_login = NULL WHERE user_id = :user_id";
                    $reset_stmt = $conn->prepare($reset_sql);
                    $reset_stmt->bindParam(':user_id', $user['user_id']);
                    $reset_stmt->execute();

                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['email'] = $user['email'];
                    unset($_SESSION['csrf_token']);

                    if ($role === 'admin') {
                        header("Location: ../Admin/dashboard.php");
                    } elseif ($role === 'teacher') {
                        header("Location: ../Teacher/dashboard.php");
                    } else {
                        $_SESSION['error'] = "Unauthorized role.";
                        header("Location: ../login.php");
                    }
                    exit;
                } else {
                    // ❌ Wrong password
                    $update_sql = "UPDATE users SET failed_attempts = failed_attempts + 1, last_failed_login = NOW() WHERE user_id = :user_id";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bindParam(':user_id', $user['user_id']);
                    $update_stmt->execute();

                    $_SESSION['error'] = "Invalid email or password.";
                    header("Location: ../login.php");
                    exit;
                }
            } else {
                $_SESSION['error'] = "Invalid email or password.";
                header("Location: ../login.php");
                exit;
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
            header("Location: ../login.php");
            exit;
        }
    }
}
?>

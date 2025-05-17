<?php
session_start();

// Set timezone explicitly (example: Manila timezone)
date_default_timezone_set('Asia/Manila');

include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // ✅ CSRF token check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Invalid CSRF token!";
        header("Location: ../login/user-login.php");
        exit;
    }

    $role = trim($_POST['role'] ?? '');
    $email_add = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST['password'] ?? '');

    if (empty($role) || empty($email_add) || empty($password)) {
        $_SESSION['error'] = "All fields are required!";
        header("Location: ../login/user-login.php");
        exit;
    } elseif (!filter_var($email_add, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format!";
        header("Location: ../login/user-login.php");
        exit;
    } else {
        try {
            $sql = "SELECT user_id, email, password, role, failed_attempts, lock_until
                    FROM users 
                    WHERE email = :email AND role = :role";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':email', $email_add);
            $stmt->bindParam(':role', $role);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $now = new DateTime();
                $lock_until = $user['lock_until'] ? new DateTime($user['lock_until']) : null;

                // 🔒 Check if account is locked
                if ($lock_until && $now < $lock_until) {
                    $unlock_time = $lock_until->format("g:i A");
                    $_SESSION['error'] = "Account locked due to multiple failed login attempts. Try again at $unlock_time.";
                    header("Location: ../login/user-login.php");
                    exit;
                }

                // ✅ Correct password
                if (password_verify($password, $user['password'])) {
                    // Reset failed attempts and lock_until
                    $reset_sql = "UPDATE users SET failed_attempts = 0, lock_until = NULL WHERE user_id = :user_id";
                    $reset_stmt = $conn->prepare($reset_sql);
                    $reset_stmt->bindParam(':user_id', $user['user_id']);
                    $reset_stmt->execute();

                    // ✅ Set session and unset CSRF token after successful login
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
                        header("Location: ../login/user-login.php");
                    }
                    exit;
                } else {
                    // ❌ Wrong password
                    $failed_attempts = $user['failed_attempts'] + 1;     
                    $lock_duration_minutes = 5;

                    if ($failed_attempts >= 5) {
                        $lock_until_new = (new DateTime())->modify("+$lock_duration_minutes minutes")->format('Y-m-d H:i:s');
                        $update_sql = "UPDATE users SET failed_attempts = :attempts, lock_until = :lock_until WHERE user_id = :user_id";
                        $update_stmt = $conn->prepare($update_sql);
                        $update_stmt->bindParam(':attempts', $failed_attempts);
                        $update_stmt->bindParam(':lock_until', $lock_until_new);
                        $update_stmt->bindParam(':user_id', $user['user_id']);
                    } else {
                        $update_sql = "UPDATE users SET failed_attempts = :attempts WHERE user_id = :user_id";
                        $update_stmt = $conn->prepare($update_sql);
                        $update_stmt->bindParam(':attempts', $failed_attempts);
                        $update_stmt->bindParam(':user_id', $user['user_id']);
                    }

                    $update_stmt->execute();
                    $_SESSION['error'] = "Invalid email or password. You can use 'Forgot Password' to reset your credentials.";
                    header("Location: ../login/user-login.php");
                    exit;
                }
            } else {
                $_SESSION['error'] = "Invalid email or password.";
                header("Location: ../login/user-login.php");
                exit;
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
            header("Location: ../login/user-login.php");
            exit;
        }
    }
}
?>

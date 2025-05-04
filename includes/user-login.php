<?php
session_start();
require_once __DIR__ . '/../config/db.php';  

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect and sanitize form data
    $role = trim($_POST['role'] ?? '');  
    $email_add = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST['password'] ?? '');

    // Validate form inputs
    if (empty($role) || empty($email_add) || empty($password)) {
        $_SESSION['error'] = "All fields are required!";
        header("Location: login.php");
        exit;
    } elseif (!filter_var($email_add, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format!";
        header("Location: ../login.php");
        exit;
    } else {
        try {
            // Query to get the user from the database
            $sql = "SELECT user_id, email, password, role FROM users WHERE email = :email AND role = :role";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':email', $email_add, PDO::PARAM_STR);
            $stmt->bindParam(':role', $role, PDO::PARAM_STR);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Check password (ensure passwords are hashed in DB)
            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id']; 
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];

                // Redirect based on role
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
                $_SESSION['error'] = "Invalid email or password.";
                header("Location: ../login.php");
                exit;
            }   
        } catch (PDOException $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
            header("Location: login.php");
            exit;
        }
    }
}
?>

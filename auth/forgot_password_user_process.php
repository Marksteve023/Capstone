<?php
session_start();

include '../config/db.php';
require_once '../vendor/autoload.php';    // PHPMailer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$site_url = "http://localhost/your_project_folder";  // Change this to your base URL

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get raw input for role and email
    $role = $_POST['role'] ?? '';
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

    // Validate role against allowed values
    if (!in_array($role, ['admin', 'teacher'], true)) {
        $_SESSION['error'] = "Please select a valid role.";
        header('Location: ../forgot_password_user.php');
        exit;
    }

    if (!$email) {
        $_SESSION['error'] = "Please enter a valid email address.";
        header('Location: ../forgot_password_user.php');
        exit;
    }

    try {
        // Find user in DB
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND role = :role LIMIT 1");
        $stmt->execute(['email' => $email, 'role' => $role]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // Don't reveal whether the email exists for security
            $_SESSION['success'] = "If an account with that email exists, a reset link will be sent.";
            header('Location: ../forgot_password_user.php');
            exit;
        }

        // Generate token and expiry (1 hour)
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Save token in DB
        $stmt = $pdo->prepare("UPDATE users SET reset_token = :token, reset_token_expires = :expires WHERE email = :email AND role = :role");
        $stmt->execute([
            'token' => $token,
            'expires' => $expires_at,
            'email' => $email,
            'role' => $role
        ]);

        // Create reset link
        $reset_link = $site_url . "/reset_password.php?token=" . $token . "&role=" . urlencode($role);

        // Send email with PHPMailer
        $mail = new PHPMailer(true);
        try {
            // SMTP setup (update with your SMTP details)
            $mail->isSMTP();
            $mail->Host       = 'smtp.example.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'your_smtp_username';
            $mail->Password   = 'your_smtp_password';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('no-reply@yourdomain.com', 'Smart Attendance Monitoring System');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body    = "
                <p>Hello,</p>
                <p>You requested a password reset.</p>
                <p>Click the link below to reset your password (valid for 1 hour):</p>
                <p><a href='{$reset_link}'>Reset Password</a></p>
                <p>If you did not request this, please ignore this email.</p>
                <p>Thanks,<br>Your Website Team</p>
            ";
            $mail->AltBody = "Hello,\n\nYou requested a password reset.\n\nReset your password here (valid for 1 hour): {$reset_link}\n\nIf you did not request this, please ignore this email.\n\nThanks,\nYour Website Team";

            $mail->send();

            $_SESSION['success'] = "If an account with that email exists, a reset link will be sent.";

        } catch (Exception $e) {
            $_SESSION['error'] = "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }

        header('Location: ../forgot_password_user.php');
        exit;

    } catch (PDOException $e) {
        // Log $e->getMessage() securely if needed
        $_SESSION['error'] = "Something went wrong. Please try again later.";
        header('Location: ../forgot_password_user.php');
        exit;
    }
} else {
    http_response_code(405);
    echo "Method Not Allowed";
}

<?php
session_start();
include '../config/db.php';

if (!isset($_GET['token'], $_GET['role'])) {
    http_response_code(400);
    exit('Invalid request.');
}

$token = $_GET['token'];
$role = $_GET['role'];

if (!in_array($role, ['admin', 'teacher'], true)) {
    http_response_code(400);
    exit('Invalid role.');
}

try {
    // Validate token and check expiry
    $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = :token AND reset_token_expires > NOW() AND role = :role LIMIT 1");
    $stmt->execute(['token' => $token, 'role' => $role]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        exit('Invalid or expired token.');
    }

    // If form submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $new_password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($new_password) || strlen($new_password) < 6) {
            $error = "Password must be at least 6 characters.";
        } elseif ($new_password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            // Hash password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update password and invalidate token
            $stmt = $pdo->prepare("UPDATE users SET password = :password, reset_token = NULL, reset_token_expires = NULL WHERE id = :id");
            $stmt->execute(['password' => $hashed_password, 'id' => $user['id']]);

            $_SESSION['success'] = "Your password has been reset successfully. You can now log in.";
            header('Location: login.php');
            exit;
        }
    }

} catch (PDOException $e) {
    error_log($e->getMessage());
    exit('Something went wrong. Please try again later.');
}
?>

<!-- Simple Reset Password Form -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
</head>
<body>
    <h2>Reset Password</h2>
    <?php if (isset($error)): ?>
        <p style="color:red"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="POST">
        <label>New Password:</label><br>
        <input type="password" name="password" required><br><br>
        <label>Confirm Password:</label><br>
        <input type="password" name="confirm_password" required><br><br>
        <button type="submit">Reset Password</button>
    </form>
</body>
</html>

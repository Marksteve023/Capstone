<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="./assets/css/bootstrap.min.css">
</head>
<body class="d-flex justify-content-center align-items-center vh-100">
<div class="container">
    <div class="col-md-6 mx-auto">
        <div class="card p-4 shadow">
            <h2 class="text-center mb-4">Reset Password</h2>
            <form action="process_forgot_password.php" method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">Enter your email</label>
                    <input type="email" class="form-control" name="email" id="email" required placeholder="example@gmail.com">
                </div>
                <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
            </form>
            <?php if (isset($_SESSION['forgot_message'])): ?>
                <div class="alert alert-info mt-3"><?= htmlspecialchars($_SESSION['forgot_message']) ?></div>
                <?php unset($_SESSION['forgot_message']); ?>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>

<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - Admin/Teacher</title>
    <link rel="stylesheet" href="../assets/css/login.css">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" >
    <link rel="stylesheet" href="../assets/css/bootstrap-icons.css">
</head>
<body class="d-flex justify-content-center align-items-center vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-4 col-md-6 col-sm-8 col-10">
                <!-- Admin/Teacher Forgot Password -->
                <div id="forgotUser" class="card shadow p-4">
                    <h2 class="text-center mb-3">Forgot Password</h2>
                    <form action="../includes/forgot_password_user_process.php" method="post" id="forgotPass" autocomplete="off">
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select id="role" name="role" class="form-select" required>
                                <option value="" disabled selected>Select your role</option>
                                <option value="admin">Admin</option>
                                <option value="teacher">Teacher</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Registered Email</label>
                            <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
                        </div>

                        <div class="mt-4 mb-3">
                            <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
                        </div>
                    </form>
                    <p class="mt-3 text-center">
                        <a href="user-login.php" class="btn btn-link p-0 text-primary text-decoration-none">Back to Login</a>
                    </p>
                    
                </div>
            </div>
        </div>
    </div>

    <script src="./assets/js/bootstrap.min.js"></script>
</body>
</html>

<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - Student</title>
    <link rel="stylesheet" href="../assets/css/login.css">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" >
    <link rel="stylesheet" href="../assets/css/bootstrap-icons.css">
</head>
</head>
<body class="d-flex justify-content-center align-items-center vh-100">

  <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-4 col-md-6 col-sm-8 col-10">
                <!-- Student Forgot Password -->
                <div id="forgotStudent" class="card shadow p-4">
                    <h2 class="text-center mb-3">Forgot Password</h2>
                    <form action="../includes/forgot_password_student_process.php" method="post" autocomplete="off" id="forgotPass">
                         <div class="mb-3">
                            <label for="school_student_id" class="form-label">Student ID</label>
                            <input type="text" id="school_student_id" name="school_student_id" class="form-control" placeholder="Enter your student ID" required>
                        </div>

                        <div class="mt-4 mb-3">
                             <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
                        </div>
                    </form>
                    <p class="mt-3 text-center">
                        <a href="student-login.php" class="btn btn-link p-0 text-primary text-decoration-none">Back to Login</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/bootstrap.min.js"></script>
</body>
</html>

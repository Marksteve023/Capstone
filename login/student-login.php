<?php 
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="../assets/css/login.css">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" >
    <link rel="stylesheet" href="../assets/css/bootstrap-icons.css">
</head>
<body class="d-flex justify-content-center align-items-center vh-100">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-4 col-md-6 col-sm-8 col-10">
            <!-- Student Login -->
            <div id="studentFormContainer" class="card shadow p-4 <?= isset($_SESSION['student_error']) ? 'border-danger' : '' ?>">
                <h2 class="text-center mb-4">Student Login</h2>
                
                <form action="../auth/student-log2.php" method="POST" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                    <div class="mb-3">
                        <label for="school_student_id" class="form-label">Student ID</label>
                        <input type="text" id="school_student_id" name="school_student_id" class="form-control"
                               placeholder="Enter your student ID"
                               required
                               value="<?= isset($_SESSION['student_input']) ? htmlspecialchars($_SESSION['student_input']) : ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label for="student_password" class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" id="student_password" name="password" class="form-control" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('student_password', 'toggleIcon2')">
                                <i id="toggleIcon2" class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <button type="submit" name="studentLogIn" class="btn btn-primary w-100">Login</button>
                    </div>
                </form>

                <div class="text-end">
                    <a href="forgot_password_student.php" class="text-dark text-decoration-none forgot">Forgot Password?</a>
                </div>

                <p class="mt-3 text-center">
                    <a href="../index.php" class="btn btn-link p-0 text-primary text-decoration-none">Back to Smart Attendance Monitoring System</a>
                </p>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger mt-2"><?= htmlspecialchars($_SESSION['error']) ?></div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="./assets/js/bootstrap.min.js"></script>
<script>
function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (input.type === "password") {
        input.type = "text";
        icon.classList.replace("bi-eye", "bi-eye-slash");
    } else {
        input.type = "password";
        icon.classList.replace("bi-eye-slash", "bi-eye");
    }
}

document.addEventListener("DOMContentLoaded", () => {
    setTimeout(() => {
        document.querySelectorAll(".alert").forEach(alert => {
            alert.classList.add("fade");
            alert.style.transition = "opacity 0.5s ease-out";
            alert.style.opacity = "0";
            setTimeout(() => alert.remove(), 500);
        });
    }, 3000);
});
</script>
</body>
</html>

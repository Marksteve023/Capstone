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

            <!-- Admin/Teacher Login -->
            <div id="userFormContainer" class="card shadow p-4 <?= isset($_SESSION['student_error']) ? 'd-none' : '' ?>">
                <h2 class="text-center mb-3">Login</h2>
                <form action="../auth/user-login2.php" method="post" id="logIn" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select name="role" id="role" class="form-select" required>
                            <option value="" disabled selected>Select your role</option>
                            <option value="admin" <?= (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                            <option value="teacher" <?= (isset($_POST['role']) && $_POST['role'] === 'teacher') ? 'selected' : ''; ?>>Teacher</option>
                        </select>
                        <div id="roleError" class="text-danger small mt-1" style="display:none;">Please select a role.</div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="example@gmail.com"
                               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="admin_password" class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" id="admin_password" name="password" class="form-control" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('admin_password', 'toggleIcon1')">
                                <i id="toggleIcon1" class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mt-4 mb-3">
                        <button type="submit" name="LogIn" class="btn btn-primary w-100">Login</button>
                    </div>
                </form>
                
                <div class="text-end">
                    <a href="forgot_password_user.php" class="text-dark text-decoration-none forgot">Forgot Password?</a>
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

document.getElementById('logIn').addEventListener('submit', function (e) {
    const role = document.getElementById('role');
    const roleError = document.getElementById('roleError');
    if (!role.value) {
        e.preventDefault();
        roleError.style.display = 'block';
    } else {
        roleError.style.display = 'none';
    }
});

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

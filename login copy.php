<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="./assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="./assets/css/login.css">
    <link rel="stylesheet" href="./assets/css/bootstrap-icons.css">
</head>
<body class="d-flex justify-content-center align-items-center vh-100">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-4 col-md-6 col-sm-8 col-10">

            <!-- Admin/Teacher Login -->
            <div id="userFormContainer" class="card shadow p-4 <?= isset($_SESSION['student_error']) ? 'd-none' : '' ?>">
                <h2 class="text-center mb-3">Login</h2>
                <form action="./includes/user-login.php" method="post" id="logIn" autocomplete="off">
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
                    <div class="text-end">
                        <a href="forgot_password.php" class="text-dark text-decoration-none forgot">Forgot Password?</a>
                    </div>
                </form>

                

                <p class="mt-3 text-center">
                    <button type="button" class="btn btn-link p-0 text-primary text-decoration-none" onclick="toggleForms()">Login as Student</button>
                </p>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger mt-2"><?= htmlspecialchars($_SESSION['error']) ?></div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
            </div>

            <!-- Student Login -->
            <div id="studentFormContainer" class="card shadow p-4 <?= isset($_SESSION['student_error']) ? '' : 'd-none' ?>">
                <h2 class="text-center mb-4">Student Login</h2>
                <form action="./includes/student-log.php" method="POST" autocomplete="off">
                    <div class="mb-3">
                        <label for="school_student_id" class="form-label">Student ID</label>
                        <input type="text" id="school_student_id" name="school_student_id"
                               class="form-control"
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

                    <?php if (isset($_SESSION['student_error'])): ?>
                        <div class="alert alert-danger mt-2"><?= htmlspecialchars($_SESSION['student_error']) ?></div>
                        <?php unset($_SESSION['student_error'], $_SESSION['student_input']); ?>
                    <?php endif; ?>

                    
                </form>
              
                <div class="text-end">
                    <a href="#" class="text-dark text-decoration-none forgot">Forgot Password?</a>
                </div>
                
                <p class="mt-3 text-center">
                    <button type="button" class="btn btn-link p-0 text-primary text-decoration-none" onclick="toggleForms()">Login as Admin/Teacher</button>
                </p>
            </div>
        </div>
    </div>
</div>

<script src="./assets/js/bootstrap.min.js"></script>
<script>
function toggleForms() {
    document.getElementById('userFormContainer').classList.toggle('d-none');
    document.getElementById('studentFormContainer').classList.toggle('d-none');
}

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

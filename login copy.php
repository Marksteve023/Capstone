<?php include './includes/auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="./assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./assets/css/login.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
</head>
<body class="d-flex justify-content-center align-items-center vh-100">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-4 col-md-6 col-sm-8 col-10">
            <!-- User/Staff Login Form -->
            <div id="userFormContainer" class="userFormContainer card shadow p-4 mx-auto">
                <h2 class="text-center mb-3">Login</h2>
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" class="login-form" id="logIn" autocomplete="off">
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select name="role" id="role" class="form-select" required>
                            <option value="" disabled selected>Select your role</option>
                            <option value="admin" <?php echo (isset($role) && $role == 'admin') ? 'selected' : ''; ?>>Admin</option>
                            <option value="teacher" <?php echo (isset($role) && $role == 'teacher') ? 'selected' : ''; ?>>Teacher</option>
                        </select>
                        <div id="roleError" class="text-danger small mt-1" style="display:none;">Please select a role.</div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="example@gmail.com" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" id="password" name="password" class="form-control" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password', 'toggleIcon1')">
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
                    <div class="alert alert-danger mt-2">
                        <?php echo htmlspecialchars($_SESSION['error']); ?>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
            </div>

            <!-- Student Login Form -->
            <div id="studentFormContainer" class="userFormContainer card shadow p-4 mx-auto d-none">
                <h2 class="text-center mb-4">Student Login</h2>
                <form action="./includes/student-login.php" method="post">
                    <div class="mb-3">
                        <label for="schoolId" class="form-label">School ID</label>
                        <input type="text" id="schoolId" name="schoolId" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="studentPassword" class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" id="studentPassword" name="studentPassword" class="form-control" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('studentPassword', 'toggleIcon2')">
                                <i id="toggleIcon2" class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mt-4 mb-3">
                    <button type="submit" name="studentLogIn" class="btn btn-success w-100">Login</button>
                    </div>

                    <div class="text-end">
                        <a href="#" class="text-dark text-decoration-none forgot">Forgot Password?</a>
                    </div>
                    
                </form>
                <p class="mt-3 text-center">
                    <button type="button" class="btn btn-link p-0 text-primary text-decoration-none" onclick="toggleForms()">Login as Staff</button>
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
        if (roleError) roleError.style.display = 'block';
    } else {
        if (roleError) roleError.style.display = 'none';
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

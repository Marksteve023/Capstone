<?php
session_start();
require_once '../config/db.php';

// Ensure user is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$student_id = $_SESSION['student_id'];

try {
    // Fetch user details using PDO
    $query = "SELECT student_name, email, picture FROM students WHERE student_id = :student_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("Student not found.");
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    $_SESSION['error'] = "An error occurred. Please try again later.";
    header("Location: error.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'head.php'; ?>
    <title>Profile - Smart Attendance Monitoring System</title>
</head>
<body>

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?> 

    <!-- Main Content -->
    <main class="main" id="main">
        <h1>Profile</h1>
        <div class="container mt-4">
            <div class="row justify-content-center pt-4 mb-4">
                <div class="col-md-6 col-lg-5 p-4 rounded shadow" id="profile-container">
                    <h2 class="text-center">Edit Profile</h2>

                    <div class="text-center">
                        <div class="position-relative d-inline-block">
                            <img id="profilePic" 
                                src="<?php echo !empty($user['picture']) ? '../assets/uploads/' . htmlspecialchars($user['picture']) : '../assets/img/default-profile.png'; ?>"
                                alt="Profile Picture" 
                                class="rounded-circle border border-secondary img-thumbnail sidebar-img"
                                style="width: 150px; height: 150px; object-fit: cover;">

                            <button type="button" class="btn btn-light btn-sm position-absolute bottom-0 end-0 border rounded-circle shadow-sm p-2" 
                                onclick="document.getElementById('fileInput').click()">
                                ✏️
                            </button>
                        </div>
                    </div>

                    <form action="../Student/scripts/update_student_profile.php" method="POST" enctype="multipart/form-data" class="mt-3 row g-3 ">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                        <input type="file" id="fileInput" name="profile_picture" accept="image/*" class="d-none">

                        <div class="mb-3">
                            <label for="student_name" class="form-label">Full Name:</label>
                            <input type="text" id="student_name" name="student_name" class="form-control" 
                                value="<?php echo htmlspecialchars($user['student_name']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email:</label>
                            <input type="email" id="email" name="email" class="form-control" 
                                value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="old_password" class="form-label">Old Password:</label>
                            <input type="password" id="old_password" name="old_password" class="form-control" placeholder="Password must be at least 8 characters." minlength="8">
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">New Password:</label>
                            <input type="password" id="password" name="password" class="form-control" placeholder="Leave blank to keep current password" placeholder="Password must be at least 8 characters." minlength="8">
                        </div>

                        <div class="d-flex justify-content-center">
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </div>

                        <!-- Error & Success Messages -->
                        <?php if (!empty($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($_SESSION['message'])): ?>
                            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                                <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <div id="message-container"></div>   
                    </form>
                </div>
            </div>
        </div>
    </main>
    <!--=============== MAIN JS ===============-->
    <script src="../assets/js/global.js"></script>
    
    <!-- Bootstrap JS and Popper.js -->
    <script src="../assets/js/popper.min.js"></script>
    <!--<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>-->
    
    <script src="../assets/js/bootstrap.min.js"></script>
    <!--<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>-->

   
    <script>

        // When a new file is selected, show preview
        document.getElementById('fileInput').addEventListener('change', function (event) {
            const file = event.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('profilePic').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Ensure Messages Fade Out After Page Load
        document.addEventListener("DOMContentLoaded", () => {
        setTimeout(() => {
            document.querySelectorAll(".alert").forEach(alert => {
                alert.classList.add("fade"); // Add fade class
                alert.style.transition = "opacity 0.5s ease-out"; 
                alert.style.opacity = "0"; 

                setTimeout(() => alert.remove(), 500); // Remove after fading
            });
        }, 3000);
    });
    </script>
</body>
</html>

<?php
session_start();
require_once '../config/db.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user_id = $_SESSION['user_id'];

try {
    // Fetch user details using PDO
    $query = "SELECT full_name, email, picture FROM users WHERE user_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("User not found.");
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
        <div class="container-fluid">
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
                        <input type="file" id="fileInput" name="profile_picture" accept="image/*" class="d-none">
                    </div>

                    <form action="../Teacher/scripts/update_profile.php" method="POST" enctype="multipart/form-data" class="mt-3">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name:</label>
                            <input type="text" id="full_name" name="full_name" class="form-control" 
                                value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email:</label>
                            <input type="email" id="email" name="email" class="form-control" 
                                value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="old_password" class="form-label">Old Password:</label>
                            <input type="password" id="old_password" name="old_password" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">New Password:</label>
                            <input type="password" id="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
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
                                <!--<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>-->
                            </div>
                        <?php endif; ?>

                        <div id="message-container"></div>   
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script src="../assets/js/global.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
   
    <script>
        document.getElementById("fileInput").addEventListener("change", function () {
        let formData = new FormData();
        formData.append("profile_picture", this.files[0]);
        
        let xhr = new XMLHttpRequest();
        xhr.open("POST", "../Teacher/scripts/upload_profile_picture.php", true);
        
        xhr.onload = function () {
            if (xhr.status === 200) {
                let response = JSON.parse(xhr.responseText);
                if (response.status === "success") {
                    document.getElementById("profilePic").src = "../assets/uploads/" + response.file_name;
                }
            }
        };
        
        xhr.send(formData);
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

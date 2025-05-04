<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include '../config/db.php';

$user_id = $_SESSION['user_id'] ?? null;

// Initialize $admin with default values
$admin = [
    'full_name' => 'Administrator',
    'email' => 'admin@gmail.com',
    'picture' => '../assets/uploads/default.png',
    'role' => 'Admin'
];

if ($user_id) {
    try {
        // Use PDO to fetch user details
        $stmt = $conn->prepare("SELECT full_name, email, picture, role FROM users WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $admin = [
                'full_name' => $row['full_name'],
                'email' => $row['email'],
                'picture' => !empty($row['picture']) ? "../assets/uploads/" . $row['picture'] : $admin['picture'],
                'role' => $row['role']
            ];
        }
    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
}
?>

<!--=============== HEADER ===============-->
<header class="header" id="header"> 
    <div class="header-container">
        <a href="" class="header-logo" id="header-logo">
            <i class="bi bi-person-lock"></i>
            <span>Student</span>
        </a>
        
        <button class="header-toggle" id="header-toggle">
            <i class="bi bi-list"></i>
        </button>
    </div>
</header>

<!--=============== SIDEBAR ===============-->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-container">
        <!--====== SIDEBAR USER ======-->
        <div class="sidebar-user">
            <div class="sidebar-img">
                <img src="../assets/uploads/<?= htmlspecialchars($student['picture']) ?>" alt="Profile Picture">
            </div>

            <div class="sidebar-info">
                <h3><?= htmlspecialchars($student['student_name']) ?></h3>
                <span><?= htmlspecialchars($student['school_student_id']) ?></span>
            </div>
        </div><!--====== END SIDEBAR USER ======-->
    
        <!--====== SIDEBAR CONTENT ======-->
        <div class="sidebar-content">
            <div class="sidebar-list">
                <a href="dashboard.php" class="sidebar-link">
                    <i class="bi bi-house-door"></i>
                    <span>Dashboard</span>
                </a>
            </div>

            <!--=======  My Attendance ======-->
            <div class="sidebar-list">
                <a href="course-attendance.php" class="sidebar-link">
                <i class="bi bi-clipboard2-data"></i>
                    <span>Course & Attendance</span>
                </a>
            </div>

            <!--=======  Profile ======-->
            <div class="sidebar-list">
                <a href="profile.php" class="sidebar-link">
                    <i class="bi bi-person-circle"></i>
                    <span>Profile</span>
                </a>
            </div>
        </div>

        <div class="sidebar-actions">
            <button>
                <i class="bi bi-cloud-sun sidebar-link sidebar-theme" id="theme-button">
                    <span>Theme</span>
                </i>
            </button>

            <form action="../logout.php">
                <button class="sidebar-link">
                    <i class="bi bi-box-arrow-left"></i>
                    <span>Log Out</span>
                </button>
            </form>
        </div>
    </div>
</nav>

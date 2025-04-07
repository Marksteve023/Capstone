<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include '../config/db.php';

$user_id = $_SESSION['user_id'] ?? null;

$teacher = [
    'full_name' => 'Teacher',
    'email' => 'teacher@gmail.com',
    'picture' => '../assets/uploads/default.png',
    'role' => 'Teacher'
];

if ($user_id) {
    try {
        // Use PDO to fetch user details
        $stmt = $conn->prepare("SELECT full_name, email, picture, role FROM users WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $teacher = [
                'full_name' => $row['full_name'],
                'email' => $row['email'],
                'picture' => !empty($row['picture']) ? "../assets/uploads/" . $row['picture'] : $teacher['picture'],
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
            <span><?= htmlspecialchars(ucwords($teacher['role'])) ?></span>

        </a>
        
        <button class="header-toggle"id="header-toggle">
            <i class="bi bi-list"></i>
        </button>
    </div>

</header>

<!--=============== SIDEBAR ===============-->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-container">
        
        <!--====== sIDEBAR USER ======-->
        <div class="sidebar-user">
            <div class="sidebar-img">
                <img src="<?= htmlspecialchars($teacher['picture']) ?>" alt="Profile Picture">
            </div>

            <div class="sidebar-info">
                <h3><?= htmlspecialchars($teacher['full_name']) ?></h3>
                <span><?= htmlspecialchars($teacher['email']) ?></span>
            </div>
        </div><!--====== END SIDEBAR USER ======-->

        <!--====== SIDEBAR CONTENT ======-->
        <div class="sidebar-content">
            <!--<h3 class="sidebar-title">Manage</h3>-->

            <div class="sidebar-list">
                <a href="dashboard.php" class="sidebar-link">
                    <i class="bi bi-house-door"></i>
                    <span>Dashboard</span>
                </a>
            </div>


            <!--=======  COURSE ======-->
            <div class="sidebar-list">
                <a href="course.php" class="sidebar-link">
                    <i class="bi bi-book"></i>
                    <span>Course & Masterlist</span>
                </a>
            </div>
            
            

            <!--=======  Attendance ======-->
            <div class="sidebar-list">
                <a href="attendance.php" class="sidebar-link">
                <i class="bi bi-calendar2-check"></i>
                    <span>Attendance</span>
                </a>
            </div>
            <!--======= View Attendance Records======-->
            <div class="sidebar-list">
                <a href="attendance-records.php" class="sidebar-link">
                    <i class="bi bi-journal-check"></i>
                    <span>View Attendance</span>
                </a>
            </div>


            <!--=======  Manage User ======-->
            <div class="sidebar-list">
                <a href="reports.php" class="sidebar-link">
                    <i class="bi bi-file-bar-graph"></i>
                    <span>Reports</span>
                </a>
            </div>

            <!--=======  Profile ======-->
            <div class="sidebar-list">
                <a href="profile.php" class="sidebar-link">
                    <i class="bi bi-person-fill-gear"></i>
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
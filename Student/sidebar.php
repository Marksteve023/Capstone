<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include '../config/db.php';

$student_id = $_SESSION['student_id'] ?? null;

// Default student values
$student = [
    'student_name' => 'Student Name',
    'school_student_id' => 'CAxxxxxxx',
    'picture' => 'default.png'
];

if ($student_id) {
    try {
        // Use PDO to fetch student details
        $stmt = $conn->prepare("SELECT student_name, school_student_id, picture FROM students WHERE student_id = :student_id");
        $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $pictureFile = !empty($row['picture']) ? $row['picture'] : 'default.png';
            $picturePath = "../assets/uploads/" . $pictureFile;

            // Check if the file exists, otherwise fallback to default
            if (!file_exists($picturePath)) {
                $pictureFile = 'default.png';
            }

            $student = [
                'student_name' => $row['student_name'],
                'school_student_id' => $row['school_student_id'],
                'picture' => $pictureFile,
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
        <a href="#" class="header-logo" id="header-logo">
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

            <div class="sidebar-list">
                <a href="course-attendance.php" class="sidebar-link">
                    <i class="bi bi-clipboard2-data"></i>
                    <span>Course & Attendance</span>
                </a>
            </div>

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

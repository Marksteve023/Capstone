<?php
session_start();
require_once '../config/db.php';

// Debugging: Check if session exists
if (!isset($_SESSION['email']) || empty($_SESSION['role'])) {
    echo "Session expired or not set!";
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    echo "Unauthorized access!";
    header("Location: ../login.php");
    exit();
}
try {
    // Fetch all counts in a single query (efficient)
    $sql = "SELECT 
                (SELECT COUNT(*) FROM students) AS student_count,
                (SELECT COUNT(*) FROM users WHERE role = 'Teacher') AS teacher_count,
                (SELECT COUNT(*) FROM courses) AS course_count";
    
    $stmt = $conn->query($sql);
    $counts = $stmt->fetch(PDO::FETCH_ASSOC);

    $students_count = $counts['student_count'];
    $teachers_count = $counts['teacher_count'];
    $courses_count = $counts['course_count'];

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Close database connection
$conn = null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'head.php'; ?>
    <title>Dashboard - Smart Attendance Monitoring System</title>
</head>
<body>

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?> 

    <!-- Main Content -->
    <main class="main" id="main">
        <h1>Dashboard</h1>
        <!--<div class="dashboard-cards">

        <div class="dashboard-card">
                <h2><?php echo htmlspecialchars($students_count); ?></h2>
                <p>Students</p>
            </div>
            <div class="dashboard-card">
                <h2><?php echo htmlspecialchars($teachers_count); ?></h2>
                <p>Teachers</p>
            </div>
            <div class="dashboard-card">
                <h2><?php echo htmlspecialchars($courses_count); ?></h2>
                <p>Courses</p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-2">
                <div class="card text-white bg-primary shadow-sm">
                    <div class="card-body text-center">
                    <h2><?php echo htmlspecialchars($students_count); ?></h2>
                        <p class="fw-bold">Students</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-white bg-success shadow-sm">
                    <div class="card-body text-center">
                        <h2><?php echo htmlspecialchars($courses_count); ?></h2>
                        <p class="fw-bold">Teachers</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-white bg-warning shadow-sm">
                    <div class="card-body text-center">
                        <h2><?php echo htmlspecialchars($students_count); ?></h2>
                        <p class="fw-bold">Courses</p>
                    </div>
                </div>
            </div>
        </div>-->

        <div class="container mt-4">

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card text-white bg-primary shadow-sm">
                    <div class="card-body text-center">
                        <h2><?php echo htmlspecialchars($students_count); ?></h2>
                        <p class="fw-bold">Students</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success shadow-sm">
                    <div class="card-body text-center">
                    <h2><?php echo htmlspecialchars($courses_count); ?></h2>
                        <p class="fw-bold">Teachers</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-warning shadow-sm">
                    <div class="card-body text-center">
                    <h2><?php echo htmlspecialchars($courses_count); ?></h2>
                        <p class="fw-bold">Courses</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </main>

    <script src="../assets/js/global.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>

</body>
</html>

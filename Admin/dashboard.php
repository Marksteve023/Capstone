<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['email']) || empty($_SESSION['role'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

try {
    // Use CONCAT to count distinct combinations
    $sql = "SELECT 
                (SELECT COUNT(*) FROM students) AS student_count,
                (SELECT COUNT(*) FROM users WHERE role = 'Teacher') AS teacher_count,
                (SELECT COUNT(*) FROM courses) AS course_count,
                (SELECT COUNT(DISTINCT CONCAT(course_id, attendance_date)) FROM attendance) AS attendance_count";

    $stmt = $conn->query($sql);
    $counts = $stmt->fetch(PDO::FETCH_ASSOC);

    $students_count = $counts['student_count'];
    $teachers_count = $counts['teacher_count'];
    $courses_count = $counts['course_count'];
    $attendance_count = $counts['attendance_count'];

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$conn = null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'head.php'; ?>
    <title>Dashboard - Smart Attendance Monitoring System</title>
</head>
<body>

    <?php include 'sidebar.php'; ?> 

    <main class="main" id="main">
        <h1>Dashboard</h1>

        <div class="container mt-4">
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="card text-white bg-primary shadow-sm">
                        <div class="card-body text-center">
                            <h2><?= htmlspecialchars($students_count) ?></h2>
                            <p class="fw-bold">Students</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-success shadow-sm">
                        <div class="card-body text-center">
                            <h2><?= htmlspecialchars($teachers_count) ?></h2>
                            <p class="fw-bold">Teachers</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-warning shadow-sm">
                        <div class="card-body text-center">
                            <h2><?= htmlspecialchars($courses_count) ?></h2>
                            <p class="fw-bold">Courses</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-info shadow-sm">
                        <div class="card-body text-center">
                            <h2><?= htmlspecialchars($attendance_count) ?></h2>
                            <p class="fw-bold">Total Attendance</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- JS scripts -->
    <script src="../assets/js/global.js"></script>
    <script src="../assets/js/popper.min.js"></script>
    <script src="../assets/js/bootstrap.min.js"></script>
</body>
</html>

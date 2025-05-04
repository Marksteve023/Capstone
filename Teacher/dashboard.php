<?php
// ============================
// Teacher Dashboard Page
// Displays summary stats: total students, courses, and attendance records
// Only accessible by authenticated teachers
// ============================

session_start();
require_once '../config/db.php';

// --- Access Control: Redirect users who are not logged in or not teachers
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']); // Get the logged-in teacher's user ID

try {
    // --- Query: Total unique students enrolled in the teacher's assigned courses
    $sql_students = "
        SELECT COUNT(DISTINCT sc.student_id) 
        FROM student_courses sc
        JOIN assigned_courses ac ON sc.course_id = ac.course_id
        WHERE ac.user_id = :user_id
    ";

    // --- Query: Total courses assigned to the teacher
    $sql_courses = "
        SELECT COUNT(*) 
        FROM assigned_courses 
        WHERE user_id = :user_id
    ";

    // --- Query: Total distinct attendance records (course + date + time)
    $sql_attendance = "
        SELECT COUNT(DISTINCT CONCAT(a.course_id, '-', a.attendance_date, '-', a.attendance_time)) AS total_attendance
        FROM attendance a
        INNER JOIN assigned_courses ac ON a.course_id = ac.course_id
        WHERE ac.user_id = :user_id
    ";

    // --- Execute: Student count query
    $stmt_students = $conn->prepare($sql_students);
    $stmt_students->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_students->execute();
    $students_count = $stmt_students->fetchColumn();

    // --- Execute: Course count query
    $stmt_courses = $conn->prepare($sql_courses);
    $stmt_courses->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_courses->execute();
    $courses_count = $stmt_courses->fetchColumn();

    // --- Execute: Attendance record count query
    $stmt_attendance = $conn->prepare($sql_attendance);
    $stmt_attendance->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_attendance->execute();
    $attendance_count = $stmt_attendance->fetchColumn();

} catch (PDOException $e) {
    // --- Error: Handle database query issues
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Dashboard - Smart Attendance Monitoring System</title>
    <!-- Include shared head resources (e.g., meta tags, Bootstrap, custom CSS) -->
    <?php include 'head.php'; ?>
</head>
<body>

    <!-- Sidebar navigation -->
    <?php include 'sidebar.php'; ?> 

    <!-- Main content container -->
    <main class="main" id="main">
        <h1>Dashboard</h1>

        <!-- Summary Cards Section -->
        <div class="container mt-4">
            <div class="row g-4">
                <!-- Card: Total Students -->
                <div class="col-md-4">
                    <div class="card text-white shadow-sm">
                        <div class="card-body text-center">
                            <h2><?php echo htmlspecialchars($students_count); ?></h2>
                            <p class="fw-bold">Students</p>
                        </div>
                    </div>
                </div>

                <!-- Card: Total Courses -->
                <div class="col-md-4">
                    <div class="card text-white shadow-sm">
                        <div class="card-body text-center">
                            <h2><?php echo htmlspecialchars($courses_count); ?></h2>
                            <p class="fw-bold">Courses</p>
                        </div>
                    </div>
                </div>

                <!-- Card: Attendance Records -->
                <div class="col-md-4">
                    <div class="card text-white shadow-sm">
                        <div class="card-body text-center">
                            <h2><?php echo htmlspecialchars($attendance_count); ?></h2>
                            <p class="fw-bold">Attendance Records</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Global and Bootstrap Scripts -->
    <script src="../assets/js/global.js"></script>
    <script src="../assets/js/popper.min.js"></script>
    <script src="../assets/js/bootstrap.min.js"></script>

</body>
</html>

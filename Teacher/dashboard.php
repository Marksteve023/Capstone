<?php
session_start();
require_once '../config/db.php';

// Redirect if session is invalid
if (!isset($_SESSION['email']) || empty($_SESSION['role'])) {
    header("Location: ../login.php");
    exit();
}

// Check if the logged-in user is a teacher
if ($_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

// Get the user ID from the session
$user_id = $_SESSION['user_id']; // Assuming the user ID is stored in the session

try {

    // Get the number of students enrolled in the courses assigned to the teacher
    $sql_students = "SELECT COUNT(DISTINCT sc.student_id) 
                     FROM student_courses sc
                     JOIN assigned_courses ac ON sc.course_id = ac.course_id
                     WHERE ac.user_id = :user_id";

    // Get the number of courses assigned to the teacher
    $sql_courses = "SELECT COUNT(*) FROM assigned_courses WHERE user_id = :user_id";
    
    // Get the attendance count for the teacher's courses
    $sql_attendance = "SELECT COUNT(*) FROM attendance a
                       JOIN assigned_courses ac ON a.course_id = ac.course_id
                       WHERE ac.user_id = :user_id";

    // Prepare and execute the queries
    $stmt_students = $conn->prepare($sql_students);
    $stmt_students->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_students->execute();
    $students_count = $stmt_students->fetchColumn();

    $stmt_courses = $conn->prepare($sql_courses);
    $stmt_courses->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_courses->execute();
    $courses_count = $stmt_courses->fetchColumn();

    $stmt_attendance = $conn->prepare($sql_attendance);
    $stmt_attendance->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_attendance->execute();
    $attendance_count = $stmt_attendance->fetchColumn();

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
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

        <div class="container mt-4">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card text-white shadow-sm">
                        <div class="card-body text-center">
                            <h2><?php echo htmlspecialchars($students_count); ?></h2>
                            <p class="fw-bold">Students</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white shadow-sm">
                        <div class="card-body text-center">
                            <h2><?php echo htmlspecialchars($courses_count); ?></h2>
                            <p class="fw-bold">Courses</p>
                        </div>
                    </div>
                </div>
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

    <script src="../assets/js/global.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>

</body>
</html>

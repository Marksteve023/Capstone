<?php
session_start();
require_once '../config/db.php';

// Redirect if session is invalid
if (!isset($_SESSION['student_id']) || empty($_SESSION['student_id'])) {
    header("Location: ../login.php");
    exit();
}

$student_id = intval($_SESSION['student_id']);


$sql = "
    SELECT 
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS present,
        SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) AS late,
        SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) AS absent,
        SUM(CASE WHEN status = 'Excused' THEN 1 ELSE 0 END) AS excused
    FROM attendance
    WHERE student_id = :student_id
";

$stmt = $conn->prepare($sql);
$stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$present = $row['present'] ?? 0;
$late = $row['late'] ?? 0;
$absent = $row['absent'] ?? 0;
$excused = $row['excused'] ?? 0;
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
    <div class="container mt-4">
        <h1>Dashboard</h1>

        <div class="row g-4">
            <div class="col-md-3">
                <div class="card bg-success text-white shadow-sm">
                    <div class="card-body text-center">
                        <h2><?= $present?></h2>
                        <p class="fw-bold">Total Present</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark shadow-sm">
                    <div class="card-body text-center">
                        <h2><?= $late?></h2>
                        <p class="fw-bold">Total Late</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white shadow-sm">
                    <div class="card-body text-center">
                        <h2><?= $absent?></h2>
                        <p class="fw-bold">Total Absent</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white shadow-sm">
                    <div class="card-body text-center">
                        <h2><?= $excused?></h2>
                        <p class="fw-bold">Total Excused</p>
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

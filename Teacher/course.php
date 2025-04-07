<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);
$user_role = $_SESSION['role'];

// Only allow teachers to access this page
if ($user_role !== 'teacher') {
    header("Location: ../index.php");
    exit();
}

// Fetch all courses assigned to the logged-in teacher
$query = "SELECT c.course_id, c.course_name, c.section, c.academic_year 
          FROM assigned_courses ac
          INNER JOIN courses c ON ac.course_id = c.course_id
          WHERE ac.user_id = :user_id";

$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Course & Masterlist  - Smart Attendance Monitoring System</title>
    <?php include 'head.php'; ?>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="main container" id="main">
        <h1>Course & Masterlist</h1>
        <div class="container d-flex justify-content-center align-items-center">
            <div class="row g-3 justify-content-center">
                <?php if (!empty($courses)): ?>
                    <?php foreach ($courses as $course): ?>
                        <div class="col-md-4 d-flex">
                            <div class="card bg-primary text-white shadow-sm w-100">
                                <div class="card-body text-center position-relative">
                                    <h2 class="h5 mb-0"><strong><?php echo htmlspecialchars($course['course_name'] . ' - ' . $course['section']); ?></strong></h2>
                                    <p class="mb-0">Academic Year: <?php echo htmlspecialchars($course['academic_year']); ?></p>
                                    
                                    <!-- Clickable Link for Modal -->
                                    <a href="#" class="stretched-link" role="button"
                                    data-bs-toggle="modal" data-bs-target="#studentModal"
                                    onclick="setCourseData('<?php echo htmlspecialchars($course['course_id']); ?>', 
                                                            '<?php echo htmlspecialchars($course['course_name']); ?>', 
                                                            '<?php echo htmlspecialchars($course['section']); ?>', 
                                                            '<?php echo htmlspecialchars($course['academic_year']); ?>')">
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center">
                        <p class="text-muted">You have no assigned courses.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>




        <div class="modal fade" id="studentModal" tabindex="-1" aria-labelledby="studentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-custom">
                <div class="modal-content"> 
                    <div class="modal-header d-flex justify-content-center">
                        <h5 class="modal-title" id="studentModalLabel">Course | Student Masterlist</h5>
                        <button type="button" class="btn-close position-absolute end-0 me-2" 
                                data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="d-flex gap-3 align-items-center mb-3">
                            <div class="col-3">
                                <select class="form-select" onchange="showTable(this.value)">
                                    <option value="masterlist">Masterlist</option>
                                    <option value="setA">Set-A</option>
                                    <option value="setB">Set-B</option>
                                </select>
                            </div>
                           
                        </div>
                        <div class="search-wrapper">
                            <input type="text" id="search-students" placeholder="Search by Name, School ID" class="search-input">
                        </div>

                        <?php include 'masterlist.php'; ?>
                        <?php include 'setA.php'; ?>
                        <?php include 'setB.php'; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../assets/js/global.js"></script>
    <script src="../assets/js/admin.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>

    <script>
        function setCourseData(courseId, courseName, section, academicYear) {
            document.getElementById('studentModalLabel').innerText = `${courseName} - ${section} | ${academicYear} | Student Masterlist`;
            document.getElementById('studentModalLabel').dataset.courseId = courseId;
            document.getElementById('studentModalLabel').dataset.courseName = courseName;
            document.getElementById('studentModalLabel').dataset.section = section;
            document.getElementById('studentModalLabel').dataset.academicYear = academicYear;
            document.getElementById('studentModalLabel').dataset.setGroup = 'masterlist';

            // Hide all rows first
            document.querySelectorAll("#masterlistBody tr, #setABody tr, #setBBody tr").forEach(row => {
                row.style.display = "none";
            });

            // Show only rows that match the selected course ID in each set
            document.querySelectorAll(`#masterlistBody tr[data-course-id='${courseId}']`).forEach(row => row.style.display = "table-row");
            document.querySelectorAll(`#setABody tr[data-course-id='${courseId}']`).forEach(row => row.style.display = "table-row");
            document.querySelectorAll(`#setBBody tr[data-course-id='${courseId}']`).forEach(row => row.style.display = "table-row");
        }

        function showTable(table) {
            document.querySelectorAll('.table-responsive').forEach(tbl => tbl.classList.add('d-none'));
            document.getElementById(table + 'Table').classList.remove('d-none');

            //modal title
            const studentModalLabel = document.getElementById('studentModalLabel');
            const courseName = studentModalLabel.dataset.courseName;
            const section = studentModalLabel.dataset.section;
            const academicYear = studentModalLabel.dataset.academicYear;
            const setGroup = table.charAt(0).toUpperCase() + table.slice(1);

            studentModalLabel.innerText = `${courseName} - ${section} | ${academicYear} | ${setGroup}`;
            studentModalLabel.dataset.setGroup = table;

            // Hide all rows first
            document.querySelectorAll(`#${table}Body tr`).forEach(row => {
                row.style.display = "none";
            });

            // Show only rows that match the selected course ID in the selected set
            const currentCourseId = studentModalLabel.dataset.courseId;
            document.querySelectorAll(`#${table}Body tr[data-course-id='${currentCourseId}']`).forEach(row => {
                row.style.display = "table-row";
            });
        }

      
        // Search Function
        document.getElementById('search-students').addEventListener('input', function () {
        const query = this.value.trim().toLowerCase();
        const studentModalLabel = document.getElementById('studentModalLabel');
        const currentCourseId = studentModalLabel.dataset.courseId;

        // Identify the currently visible table
        let activeTable = document.querySelector('.table-responsive:not(.d-none)');
        if (!activeTable) return;

        let tableBody = activeTable.querySelector('tbody');

        // Filter rows in the active table
        tableBody.querySelectorAll('tr').forEach(row => {
            const text = Array.from(row.getElementsByTagName('td')).map(td => td.textContent.trim().toLowerCase()).join(' ');
            const courseIdMatch = row.getAttribute('data-course-id') === currentCourseId;
            row.style.display = text.includes(query) && courseIdMatch ? 'table-row' : 'none';
        });
    });




    </script>
</body>
</html>
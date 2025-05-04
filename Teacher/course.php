<?php
// ============================
// Course & Masterlist Page (Teacher)
// Displays all assigned courses for the logged-in teacher with modal-based access to full student masterlists (Set A & Set B)
// ============================

session_start();
include '../config/db.php';

// --- Access Control: Redirect users who are not logged in or not teachers
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../index.php");
    exit();
}

$user_id   = intval($_SESSION['user_id']);
$user_role = $_SESSION['role'];

if ($user_role !== 'teacher') {
    header("Location: ../index.php");
    exit();
}

// --- Fetch assigned courses for the logged-in teacher
$query = "
    SELECT c.course_id, c.course_name, c.section, c.academic_year 
    FROM assigned_courses ac
    INNER JOIN courses c ON ac.course_id = c.course_id
    WHERE ac.user_id = :user_id
";

$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Include shared head resources (e.g., meta tags, Bootstrap, custom CSS) -->
    <?php include 'head.php'; ?>
    <title>Course & Masterlist - Smart Attendance Monitoring System</title>
</head>
<body>

    <!-- Sidebar navigation -->
    <?php include 'sidebar.php'; ?>

    <!-- Main content -->
    <main class="main" id="main">
        <h1>Course & Masterlist</h1>

        <div class="container mt-4">
            <!-- Course Cards Section -->
            <div class="cards-container d-flex justify-content-center">
                <div class="row g-3">
                    <?php if (!empty($courses)): ?>
                        <?php foreach ($courses as $course): ?>
                            <div class="col-md-4 col-sm-6 mb-4">
                                <!-- Individual Course Card -->
                                <div class="card bg-primary text-white shadow-sm h-100" id="courseMastelistCard">
                                    <div class="card-body text-center position-relative">
                                        <!-- Course Information -->
                                        <h2 class="h5 mb-2">
                                            <strong><?php echo htmlspecialchars($course['course_name'] . ' - ' . $course['section']); ?></strong>
                                        </h2>
                                        <p class="mb-0">Academic Year: <?php echo htmlspecialchars($course['academic_year']); ?></p>
                                        
                                        <!-- Clickable Area for Modal -->
                                        <a href="#" class="stretched-link" role="button" data-bs-toggle="modal" data-bs-target="#studentModal"
                                        onclick="setCourseData('<?php echo htmlspecialchars($course['course_id']); ?>', 
                                                                '<?php echo htmlspecialchars($course['course_name']); ?>', 
                                                                '<?php echo htmlspecialchars($course['section']); ?>', 
                                                                '<?php echo htmlspecialchars($course['academic_year']); ?>')">
                                            <span class="visually-hidden">Open course details</span>
                                        </a>
                                        
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>    
                    <?php else: ?>
                        <!-- Message if no assigned courses -->
                        <div class="col-12 text-center">
                            <p class="text-muted">You have no assigned courses.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Modal: Student Masterlist -->
        <div class="modal fade" id="studentModal" tabindex="-1" aria-labelledby="studentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-custom">
                <div class="modal-content">
                    <!-- Modal Header -->
                    <div class="modal-header d-flex justify-content-center">
                        <h5 class="modal-title" id="studentModalLabel">Course | Student Masterlist</h5>
                        <button type="button" class="btn-close position-absolute end-0 me-2" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <!-- Modal Body -->
                    <div class="modal-body">
                        
                        <!-- Dropdown to switch between Masterlist/Set A/Set B -->
                        <div class="d-flex justify-content-center align-items-center mb-3">
                            <div class="col-4"> 
                                <select class="form-select text-center" onchange="showTable(this.value)">
                                    <option value="masterlist">Masterlist</option>
                                    <option value="setA">Set-A</option>
                                    <option value="setB">Set-B</option>
                                </select>
                            </div>
                        </div>

                        <!-- Search bar -->
                        <div class="search-wrapper-container my-3 text-center">
                            <input type="text" id="search-students" class="search-input text-center" placeholder="Search by Name, School ID">
                        </div>

                        <!-- Include masterlist tables -->
                        <?php include 'masterlist.php'; ?>
                        <?php include 'setA.php'; ?>
                        <?php include 'setB.php'; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!--=============== MAIN JS ===============-->
    <script src="../assets/js/global.js"></script>
    <script src="../assets/js/admin.js"></script>

    <!-- Bootstrap JS and Popper.js -->
    <script src="../assets/js/popper.min.js"></script>
    <!--<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>-->

    <script src="../assets/js/bootstrap.min.js"></script>
    <!--<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>-->

    <script>

        // ===========================================
        // JS: Handle Student Masterlist Modal Display
        // ===========================================

        // -------------------------------------------
        // Set data and show modal content for course
        // -------------------------------------------
        function setCourseData(courseId, courseName, section, academicYear) {
            // Update modal title and data attributes
            const label = document.getElementById('studentModalLabel');
            label.innerText = `${courseName} - ${section} | ${academicYear} | Student Masterlist`;
            label.dataset.courseId = courseId;
            label.dataset.courseName = courseName;
            label.dataset.section = section;
            label.dataset.academicYear = academicYear;
            label.dataset.setGroup = 'masterlist';

            // Hide all rows from all tables initially
            document.querySelectorAll("#masterlistBody tr, #setABody tr, #setBBody tr").forEach(row => {
                row.style.display = "none";
            });

            // Show only rows matching the selected course ID
            document.querySelectorAll(`#masterlistBody tr[data-course-id='${courseId}']`).forEach(row => row.style.display = "table-row");
            document.querySelectorAll(`#setABody tr[data-course-id='${courseId}']`).forEach(row => row.style.display = "table-row");
            document.querySelectorAll(`#setBBody tr[data-course-id='${courseId}']`).forEach(row => row.style.display = "table-row");
        }

        // -------------------------------------------
        // Switch between Masterlist, Set A, Set B tables
        // -------------------------------------------
        function showTable(table) {
            // Hide all tables
            document.querySelectorAll('.table-responsive').forEach(tbl => tbl.classList.add('d-none'));

            // Show selected table
            document.getElementById(table + 'Table').classList.remove('d-none');

            // Update modal title with selected set
            const studentModalLabel = document.getElementById('studentModalLabel');
            const courseName = studentModalLabel.dataset.courseName;
            const section = studentModalLabel.dataset.section;
            const academicYear = studentModalLabel.dataset.academicYear;
            const setGroup = table.charAt(0).toUpperCase() + table.slice(1);

            studentModalLabel.innerText = `${courseName} - ${section} | ${academicYear} | ${setGroup}`;
            studentModalLabel.dataset.setGroup = table;

            // Hide all rows in the selected table
            document.querySelectorAll(`#${table}Body tr`).forEach(row => {
                row.style.display = "none";
            });

            // Show only rows that match the selected course ID
            const currentCourseId = studentModalLabel.dataset.courseId;
            document.querySelectorAll(`#${table}Body tr[data-course-id='${currentCourseId}']`).forEach(row => {
                row.style.display = "table-row";
            });
        }

        // ---------------------------------------------
        // Search student rows across currently active table
        // ---------------------------------------------
        document.getElementById('search-students').addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();
            const studentModalLabel = document.getElementById('studentModalLabel');
            const currentCourseId = studentModalLabel.dataset.courseId;

            // Identify currently visible table
            let activeTable = document.querySelector('.table-responsive:not(.d-none)');
            if (!activeTable) return;

            let tableBody = activeTable.querySelector('tbody');

            // Loop through each row and match query + course ID
            tableBody.querySelectorAll('tr').forEach(row => {
                const text = Array.from(row.getElementsByTagName('td'))
                                .map(td => td.textContent.trim().toLowerCase())
                                .join(' ');

                const courseIdMatch = row.getAttribute('data-course-id') === currentCourseId;
                row.style.display = text.includes(query) && courseIdMatch ? 'table-row' : 'none';
            });
        });

</script>


</body>
</html>
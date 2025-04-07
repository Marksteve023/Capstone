
<?php 
session_start();   
require_once __DIR__ . '/../config/db.php'; 

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

// Fetch assigned courses and join with teachers & courses tables
$sql = "SELECT ac.assigned_course_id, t.full_name, t.user_id, t.school_id, c.course_name, c.section, ac.assigned_at, ac.course_id
        FROM assigned_courses AS ac
        JOIN users AS t ON ac.user_id = t.user_id
        JOIN courses AS c ON ac.course_id = c.course_id
        ORDER BY ac.assigned_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute();
$assigned_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch teachers for dropdown (Fixed table name)
$sqlTeachers = "SELECT user_id, full_name FROM users WHERE role = 'Teacher'";
$stmtTeachers = $conn->prepare($sqlTeachers);
$stmtTeachers->execute();
$teacherOptions = $stmtTeachers->fetchAll(PDO::FETCH_ASSOC);

// Fetch courses with concatenated course name & section
$sqlCourses = "SELECT course_id, CONCAT(course_name, ' - ', section) AS course_display, section FROM courses";
$stmtCourses = $conn->prepare($sqlCourses);
$stmtCourses->execute();
$courseOptions = $stmtCourses->fetchAll(PDO::FETCH_ASSOC);

// Handle reassignment
$selectedCourseId = $selectedSection = $reassignId = '';
if (isset($_GET['reassign_id'])) {
    $reassignId = filter_input(INPUT_GET, 'reassign_id', FILTER_SANITIZE_NUMBER_INT);
    
    // FIX: Join with `courses` table to fetch `section` properly
    $stmt = $conn->prepare("
        SELECT ac.course_id, c.section 
        FROM assigned_courses ac
        JOIN courses c ON ac.course_id = c.course_id
        WHERE ac.assigned_course_id = ?
    ");
    
    $stmt->execute([$reassignId]);
    $reassignData = $stmt->fetch(PDO::FETCH_ASSOC); 
    
    if ($reassignData) {
        $selectedCourseId = $reassignData['course_id'];
        $selectedSection = $reassignData['section'];
    }
}    
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'head.php'; ?>
    <title>Manage Teacher - Smart Attendance Monitoring System</title>
</head>
<body>
    <!--=============== SIDEBAR ===============-->
    <?php include 'sidebar.php'; ?>
    
    <!--=============== MAIN ===============-->
    <main class="main" id="main">
            <h1>Manage Teacher</h1>
            
            <div class="container mt-4">
                <div class="card shadow-lg">
                    <div class="card-header">
                        <h2 class="mb-0 text-center">
                            <?php echo $reassignId ? 'Reassign Course' : 'Assign Course'; ?>
                        </h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="../Admin/scripts/assigned-course.php">
                            <input type="hidden" name="reassign_id" value="<?php echo htmlspecialchars($reassignId); ?>">

                        <div class="row">
                            
                                <!-- Teacher Dropdown -->
                                <div class="mb-3">
                                    <label for="teacher" class="form-label">Teacher</label>
                                    <select class="form-select select2" id="teacher" name="user_id" required>
                                        <option value="" disabled selected>----- Select Teacher -----</option>
                                        <?php foreach ($teacherOptions as $teacher): ?>
                                            <option value="<?php echo htmlspecialchars($teacher['user_id']); ?>">
                                                <?php echo htmlspecialchars($teacher['full_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Course Dropdown -->
                                <div class="mb-3">
                                    <label for="course" class="form-label">Course</label>
                                    <select class="form-select select2" id="course" name="course_id" required onchange="updateSection()">
                                        <option value="" disabled <?php echo empty($selectedCourseId) ? 'selected' : ''; ?>>
                                            ----- Select Course -----
                                        </option>
                                        <?php foreach ($courseOptions as $option): ?>
                                            <option value="<?php echo htmlspecialchars($option['course_id']); ?>" 
                                                    data-section="<?php echo htmlspecialchars($option['section']); ?>"
                                                    <?php echo ($selectedCourseId == $option['course_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($option['course_display']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Section Input -->
                                <div class="mb-3">
                                    <label for="section" class="form-label">Section</label>
                                    <input type="text" class="form-control" id="section" name="section" readonly 
                                        value="<?php echo htmlspecialchars($selectedSection); ?>">
                                </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="mb-3 d-flex justify-content-center">
                            <button type="submit" class="btn btn-primary w-50">
                                <?php echo $reassignId ? 'Reassign Course' : 'Assign Course'; ?>
                            </button>
                        </div>

                        <!-- Error & Success Messages -->
                        <?php if (!empty($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($_SESSION['message'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <div id="message-container"></div>   
                    </form>
                </div>
            </div>
        </div>  

        <div class="assigned-course-container">
        
            <h2 class="mb-0 text-center">List of Assigned Courses</h2>

            <!-- Search Bar -->     
            <div class="search-wrapper">
                <input type="text" id="search-assigned" placeholder="Search..." class="search-input">
            </div>

            <div class="table-wrapper">
                <table class="table table-striped table-bordered tContainer">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Teacher Name</th>
                            <th>School ID</th>
                            <th>Course Name</th>
                            <th>Section</th>
                            <th>Assigned At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="assigned-course-list">
                        <?php if (empty($assigned_courses)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; font-weight: bold;">No assigned courses found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($assigned_courses as $assigned_course): ?>
                                <tr data-assigned-course-id="<?php echo $assigned_course['assigned_course_id']; ?>">
                                    <td><?php echo htmlspecialchars($assigned_course['assigned_course_id']); ?></td>
                                    <td><?php echo htmlspecialchars($assigned_course['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($assigned_course['school_id']); ?></td>
                                    <td><?php echo htmlspecialchars($assigned_course['course_name']); ?></td>
                                    <td><?php echo htmlspecialchars($assigned_course['section']); ?></td>
                                    <td><?php echo htmlspecialchars($assigned_course['assigned_at']); ?></td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="?reassign_id=<?php echo htmlspecialchars($assigned_course['assigned_course_id']); ?>" class="btn btn-warning btn-sm">Reassign</a>
                                            
                                            <form id="delete-form-<?php echo $assigned_course['assigned_course_id']; ?>" 
                                                action="" method="POST" style="display:inline;">
                                                <input type="hidden" name="assigned_course_id" value="<?php echo $assigned_course['assigned_course_id']; ?>">
                                                <button type="button" class="btn btn-danger btn-sm" onclick="deleteAssigned(<?php echo $assigned_course['assigned_course_id']; ?>)">Delete</button>
                                            </form>

                                        </div>
                                        
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>         
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!--=============== MAIN JS ===============-->
    <script src="../assets/js/global.js"></script>
    <script src="../assets/js/admin.js"></script>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>

    <!-- Include jQuery (required for Select2) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Include Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

    <script>
    $(document).ready(function() {
        // Apply Select2 to the teacher and course dropdowns
        $('#teacher').select2({
            placeholder: "--- Choose Teacher ---",
            allowClear: true,
            width: '100%'
        });
        $('#course').select2({
            placeholder: "--- Select Courses ---",
            allowClear: true,
            width: '100%'
        });

    });

    // Search Function
    document.getElementById('search-assigned').addEventListener('input', function () {
        const query = this.value.trim().toLowerCase();
        document.querySelectorAll('.table tbody tr').forEach(row => {
            const text = Array.from(row.getElementsByTagName('td')).map(td => td.textContent.trim().toLowerCase()).join(' ');
            row.style.display = text.includes(query) ? '' : 'none';
        });
    });
    
    

    function updateSection() {
        // Get the selected course ID
        var courseSelect = document.getElementById('course');
        var selectedOption = courseSelect.options[courseSelect.selectedIndex];

        // Get the section associated with the selected course
        var section = selectedOption.getAttribute('data-section');

        // Update the section input field
        document.getElementById('section').value = section;
        }

        

        // Ensure Messages Fade Out After Page Load
        document.addEventListener("DOMContentLoaded", () => { 
            setTimeout(() => {
                document.querySelectorAll(".alert").forEach(alert => {
                    alert.classList.add("fade"); // Add fade class
                    alert.style.transition = "opacity 0.5s ease-out"; 
                    alert.style.opacity = "0"; 

                    setTimeout(() => alert.remove(), 500); // Remove after fading
                });
            }, 3000);
        });
    </script>

</body>
</html>

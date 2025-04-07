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

 // Ensure database connection is established
 if (!$conn) {
     die("Database connection failed.");
 }

 // Fetch enrollments
 try {
     $sql = "SELECT sc.student_course_id, s.school_student_id, s.student_name, c.course_name, c.academic_year, c.section, sc.enrolled_at
             FROM student_courses sc
             INNER JOIN students s ON sc.student_id = s.student_id
             INNER JOIN courses c ON sc.course_id = c.course_id
             ORDER BY sc.enrolled_at DESC";

     $stmt = $conn->prepare($sql);
     $stmt->execute();
     $enroll = $stmt->fetchAll(PDO::FETCH_ASSOC);
 } catch (PDOException $e) {
     die("Error fetching enrollments: " . $e->getMessage());
 }

 // Fetch Sections
 try {
     $sqlSections = "SELECT DISTINCT section FROM courses";
     $stmtSections = $conn->prepare($sqlSections);
     $stmtSections->execute();
     $sectionOptions = $stmtSections->fetchAll(PDO::FETCH_ASSOC);
 } catch (PDOException $e) {
     die("Error fetching sections: " . $e->getMessage());
 }

 // Fetch courses mapped by section
 try {
     $sqlCourses = "SELECT course_id, course_name, section, academic_year FROM courses";
     $stmtCourses = $conn->prepare($sqlCourses);
     $stmtCourses->execute();
     $courseOptions = $stmtCourses->fetchAll(PDO::FETCH_ASSOC);
 } catch (PDOException $e) {
     die("Error fetching courses: " . $e->getMessage());
 }

 // Handle Re-enrollment
 $selectedEnrollmentID = $selectedSection = $selectedCourse = '';
 $re_enrollId = '';
 

 if (isset($_GET['re_enroll_id'])) {
     $re_enrollId = filter_input(INPUT_GET, 're_enroll_id', FILTER_SANITIZE_NUMBER_INT);

     $stmt = $conn->prepare("SELECT sc.student_course_id, s.school_student_id, s.student_name, c.course_id, c.course_name, c.section 
                             FROM student_courses sc
                             INNER JOIN students s ON sc.student_id = s.student_id
                             INNER JOIN courses c ON sc.course_id = c.course_id
                             WHERE sc.student_course_id = ?");
     $stmt->execute([$re_enrollId]);
     $re_enrollData = $stmt->fetch(PDO::FETCH_ASSOC); // Use fetch instead of fetchAll as only one row is expected

     if ($re_enrollData) {
         $selectedEnrollmentID = $re_enrollData['student_course_id'];
         $selectedSection = $re_enrollData['section'];
         $selectedCourse = $re_enrollData['course_id'];
     }
 }
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'head.php'; ?>
    <title>Student Course Enrollment - Smart Attendance Monitoring System</title>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main" id="main">
        <h1>Student Course Enrollment</h1>

        <div class="container mt-4">
            <div class="card shadow-lg">
                <div class="card-header">
                    <h2 class="mb-0 text-center">Student Enrollment</h2>
                </div>

                <div class="card-body">
                    <form action="../admin/scripts/enroll.php" method="POST">
                    <input type="hidden" name="re_enroll_id" value="<?php echo htmlspecialchars($re_enrollId); ?>">
                        <div class="row">
                            <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="school_student_id" class="form-label">Student ID</label>
                                        <input type="text" name="school_student_id" id="school_student_id" value="<?= htmlspecialchars($re_enrollData['school_student_id'] ?? '') ?>" required oninput="fetchStudentDetails()">
                                    </div>

                                <div class="mb-3">
                                    <label for="student_name" class="form-label">Student Name</label>
                                    <input type="text" name="student_name" id="student_name" value="<?php echo htmlspecialchars($re_enrollData['student_name'] ?? ''); ?>" readonly>
                                </div>
                            </div>

                            <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="section" class="form-label">Section</label>
                                        <select class="form-select select2" name="section" id="section" onchange="updateSection()">
                                            <option value="" disabled <?= empty($selectedSection) ? 'selected' : '' ?>>Select Section</option>
                                            <?php foreach ($sectionOptions as $section) : ?>
                                                <option value="<?php echo htmlspecialchars($section['section']); ?>"
                                                    <?= ($selectedSection == $section['section']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($section['section']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                <div class="mb-3">
                                    <label for="course_id" class="form-label">Courses</label>
                                    <select class="form-select select2-multiple select2" id="course_id" name="course_id[]" multiple>
                                    <?php foreach ($courseOptions as $course) : ?>
                                            <option value="<?php echo htmlspecialchars($course['course_id']) ?>" data-section="<?= htmlspecialchars($course['section']) ?>">
                                                <?php echo htmlspecialchars($course['course_name'] . ' - ' . $course['section']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="mb-3 d-flex justify-content-center">
                            <button type="submit" class="btn btn-primary w-50">
                                <?php echo $re_enrollId ? 'Reassign Course' : 'Enroll Course'; ?>
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
                                <!--<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>-->
                            </div>
                        <?php endif; ?>

                        <div id="message-container"></div>   
                    </form>
                </div>
            </div>
        </div>

        <div class="student-enrollment-container">
            <h2 class="mb-0 text-center">
                Student Enrollment List
            </h2>

            <div class="search-wrapper">
                <input type="text" id="search-enrolled" placeholder="Search by Name, School ID," class="search-input">
            </div>

            <div class="table-wrapper">

                <table class="table table-striped table-bordered tContainer">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student Name</th>
                            <th>School ID</th>
                            <th>Section</th>
                            <th>Course</th>
                            <th>academic Year</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="enrolled-list">
                        <?php if (empty($enroll)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; font-weight: bold">No Enrolled Records</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($enroll as $index => $enrolled): ?>
                                <tr data-enrolled-id="<?php echo htmlspecialchars($enrolled['student_course_id']); ?>">
                                <td><?php echo htmlspecialchars($index + 1); ?></td>
                                    <td><?php echo htmlspecialchars($enrolled['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($enrolled['school_student_id']); ?></td>
                                    <td><?php echo htmlspecialchars($enrolled['section']); ?></td>
                                    <td><?php echo htmlspecialchars($enrolled['course_name']); ?></td>
                                    <td><?php echo htmlspecialchars($enrolled['academic_year']); ?></td>
                                    <td><?php echo htmlspecialchars($enrolled['enrolled_at']); ?></td>
                                    <td>
                                    <div class="d-flex gap-2">
                                        <a href="?re_enroll_id=<?php echo htmlspecialchars($enrolled['student_course_id']); ?>" 
                                        class="btn btn-warning btn-sm">Edit</a>

                                        <form id="delete-form-<?php echo $enrolled['student_course_id']; ?>" action="" method="POST">
                                            <input type="hidden" name="user_id" value="<?php echo $enrolled['student_course_id']; ?>">
                                            <button type="button" class="btn btn-danger btn-sm" 
                                            onclick="deleteEnrolled(<?php echo $enrolled['student_course_id']; ?>)">Delete</button>
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

    

    </div>

    <!--=============== MAIN JS ===============-->
    <script src="../assets/js/global.js"></script>
    <script src="../assets/js/admin.js"></script>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>

    <!-- Include jQuery and Select2 -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

    <script>

        async function fetchStudentDetails() {
            const studentId = document.getElementById("school_student_id").value.trim();
            if (!studentId) return;
            
            try {
                const response = await fetch(`scripts/fetch_student.php?school_student_id=${encodeURIComponent(studentId)}`);
                if (!response.ok) throw new Error("Failed to fetch student data");
                
                const student = await response.json();
                document.getElementById("student_name").value = student?.student_name || '';
            } catch (error) {
                console.error("Error fetching student details:", error);
            }
        }

        $(document).ready(function () {
            $('.select2').select2({
                placeholder: "----- Select Section -----",
                allowClear: true,
                width: '100%'
            });

            $('.select2-multiple').select2({
                placeholder: "----- Select Courses -----",
                allowClear: true,
                width: '100%'
            });

            // Auto-fill courses based on selected section
            $('#section').change(function () {
                let section = $(this).val();
                let courseSelect = $('#course_id');

                // Clear previous selections
                courseSelect.val(null).trigger('change');

                // Filter and pre-select courses that belong to the chosen section
                let matchingCourses = [];
                $('#course_id option').each(function () {
                    if ($(this).data('section') === section) {
                        matchingCourses.push($(this).val());
                    }
                });

                courseSelect.val(matchingCourses).trigger('change');
            });
        });
        
        // Search Function
        document.getElementById('search-enrolled').addEventListener('input', function () {
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

<?php
session_start();
include '../config/db.php';

// Redirect if session is invalid
if (!isset($_SESSION['email']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$edit_course = null;

// If editing, fetch course details
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $course_id = $_GET['id'];
    try {
        $stmt = $conn->prepare("SELECT * FROM courses WHERE course_id = :course_id");
        $stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->execute();
        $edit_course = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'head.php'; ?>
</head>
<body>
<?php include 'sidebar.php'; ?>
<main class="main" id="main">
    <h1>Course & Section</h1>

    <div class="container mt-4">
        <div class="card shadow-lg">
            <div class="card-header text-center">
                <h2><?php echo $edit_course ? 'Edit Course' : 'Create Course'; ?></h2>
            </div>

            <div class="card-body">
                <form action="../Admin/scripts/create-course.php" method="POST">
                    <?php if ($edit_course): ?>
                        <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($edit_course['course_id']); ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="course_name" class="form-label">Course Name</label>
                        <input type="text" class="form-control" id="course_name" name="course_name" value="<?php echo htmlspecialchars($edit_course['course_name'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="section" class="form-label">Section</label>
                        <input type="text" class="form-control" id="section" name="section" value="<?php echo htmlspecialchars($edit_course['section'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="semester" class="form-label">Semester</label>
                        <select class="form-select" id="semester" name="semester" required>
                            <option value="" disabled <?php echo !$edit_course ? 'selected' : ''; ?>>--- Select Semester ---</option>
                            <?php
                            $semesters = ["1st Trimester", "2nd Trimester", "3rd Trimester"];
                            foreach ($semesters as $sem) {
                                $selected = ($edit_course && $edit_course['semester'] === $sem) ? 'selected' : '';
                                echo "<option value=\"$sem\" $selected>$sem</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="academic_year" class="form-label">Academic Year</label>
                        <select class="form-select" id="academic_year" name="academic_year" required>
                            <option value="">--- Select Academic Year ---</option>
                            <?php
                            $currentMonth = date('n');
                            $currentYear = date('Y');
                            $startYear = ($currentMonth < 6) ? $currentYear - 1 : $currentYear;

                            for ($i = 4; $i >= 0; $i--) {
                                $start = $startYear + $i;
                                $end = $start + 1;
                                $val = "$start-$end";
                                $selected = ($edit_course && $edit_course['academic_year'] === $val) ? 'selected' : '';
                                echo "<option value=\"$val\" $selected>$val</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3 text-center">
                        <button type="submit" class="btn btn-primary"><?php echo $edit_course ? 'Update' : 'Save'; ?></button>
                        <?php if ($edit_course): ?>
                            <a href="course-section.php" class="btn btn-secondary ms-2">Cancel Edit</a>
                        <?php endif; ?>
                    </div>

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
                </form>
            </div>
        </div>
    </div>

    <div class="course-list-container mt-5">
        <div class="text-center mb-3">
            <h2>List of Courses</h2>
        </div>

        <!-- Search Bar --> 
        <div class="search-wrapper my-3">
            <input type="text" id="search-course" placeholder="Search by Course Name, Section, Semester, Academic Year" class="search-input">
        </div>


        <div class="table-wrapper">
            <table class="table table-striped table-bordered">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Course Name</th>
                    <th>Section</th>
                    <th>Semester</th>
                    <th>Academic Year</th>
                    <th>Instructor</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody id="course-body"></tbody>
            </table>
        </div>
    </div>
</main>

<script src="../assets/js/global.js"></script>
<script src="../assets/js/admin.js"></script>
<script src="../assets/js/popper.min.js"></script>
<script src="../assets/js/bootstrap.min.js"></script>
<!-- Include jQuery first -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
document.getElementById('search-course').addEventListener('input', function () {
    const query = this.value.trim().toLowerCase();
    document.querySelectorAll('#course-body tr').forEach(row => {
        const rowText = row.innerText.toLowerCase();
        row.style.display = rowText.includes(query) ? '' : 'none';
    });
});

// Fade out alerts after 3s
document.addEventListener("DOMContentLoaded", () => {
    setTimeout(() => {
        document.querySelectorAll(".alert").forEach(alert => {
            alert.classList.add("fade");
            alert.style.opacity = "0";
            setTimeout(() => alert.remove(), 500);
        });
    }, 3000);
});

// jQuery document ready
$(function () {
    function fetchCourses() {
        $.ajax({
            url: '../Admin/scripts/courses-list.php', 
            type: 'GET',
            dataType: 'json',
            success: function (data) {
                const tbody = $('#course-body');
                tbody.empty();

                if (data.success && Array.isArray(data.courses)) {
                    data.courses.forEach((course, index) => {
                        const createdAt = new Date(course.created_at).toLocaleString();
                        const instructor = course.full_name || 'Unassigned';
                        const row = `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${course.course_name}</td>
                                <td>${course.section}</td>
                                <td>${course.semester}</td>
                                <td>${course.academic_year}</td>
                                <td>${instructor}</td>
                                <td>${createdAt}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="?id=${course.course_id}" class="btn btn-outline-warning btn-sm">Edit</a>
                                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteCourse(${course.course_id})">Delete</button>
                                    </div>
                                </td>
                            </tr>`;
                        tbody.append(row);
                    });
                } else {
                    tbody.append('<tr><td colspan="8" class="text-center">No courses found.</td></tr>');
                }
            },
            error: function () {
                alert('Error fetching course data.');
            }
        });
    }

    window.deleteCourse = function (courseId) {
        if (confirm('Are you sure you want to delete this course?')) {
            $.ajax({
                url: '../Admin/scripts/delete-course.php',
                type: 'POST',
                data: { course_id: courseId },
                dataType: 'json',
                success: function (data) {
                    alert(data.message);
                    if (data.success) fetchCourses();  // Refresh courses after deletion
                },
                error: function () {
                    alert('Error deleting course.');
                }
            });
        }
    };

    // Initial fetch to load courses
    fetchCourses();
});
</script>

</body>
</html>

<?php
    session_start();
    include '../config/db.php';

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

// Initialize course editing data
$edit_course = null;

if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $course_id = $_GET['id'];
    try {
        $sql = "SELECT * FROM courses WHERE course_id = :course_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->execute();
        $edit_course = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
}

// Fetch all courses with instructor name
try {
    $sql = "SELECT c.course_id, c.course_name, c.section, c.semester, c.academic_year, c.created_at, u.full_name 
            FROM courses c 
            LEFT JOIN assigned_courses ac ON c.course_id = ac.course_id 
            LEFT JOIN users u ON ac.user_id = u.user_id
            ORDER BY c.course_name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
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
                <div class="card-header">   
                    <h2 class="mb-0 text-center">
                        <?php echo $edit_course ? 'Edit Course' : 'Create Course'; ?>
                    </h2>
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
                            <label for="section" class="form-label">Course Section</label>
                            <input type="text" class="form-control" id="section" name="section" value="<?php echo htmlspecialchars($edit_course['section'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="semester" class="form-label">Semester</label>
                            <select class="form-select" id="semester" name="semester" required>
                                <option value="" disabled <?php echo !$edit_course ? 'selected' : ''; ?>>--- Select Semester ---</option>
                                <option value="1st Trimester" <?php echo $edit_course && $edit_course['semester'] === '1st Trimester' ? 'selected' : ''; ?>>1st Trimester</option>
                                <option value="2nd Trimester" <?php echo $edit_course && $edit_course['semester'] === '2nd Trimester' ? 'selected' : ''; ?>>2nd Trimester</option>
                                <option value="3rd Trimester" <?php echo $edit_course && $edit_course['semester'] === '3rd Trimester' ? 'selected' : ''; ?>>3rd Trimester</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="academic_year" class="form-label">Academic Year</label>
                            <input type="text" class="form-control" id="academic_year" name="academic_year" value="<?php echo htmlspecialchars($edit_course['academic_year'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3 d-flex justify-content-center">
                            <button type="submit" class="btn btn-primary w-50"><?php echo $edit_course ? 'Update' : 'Save'; ?></button>
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
                    
        <div class="course-list-container">

            <h2 class="mb-0 text-center">List of Courses</h2>

            <!-- Search Bar --> 
            <div class="search-wrapper">
                <input type="text" id="search-course" placeholder="Search by Course Name, Section, Semester, Academic Year" class="search-input">
            </div>

            <div class="table-wrapper">
                <table class="table table-striped table-bordered tContainer">
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
                    <tbody id="course-table-body">
                        <?php if (empty($courses)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; font-weight: bold;">No courses available</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($courses as  $index => $course): ?>
                                <tr data-course-id="<?php echo htmlspecialchars($course['course_id']); ?>">
                                    <td><?php echo htmlspecialchars($index + 1); ?></td>
                                    <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                    <td><?php echo htmlspecialchars($course['section']); ?></td>
                                    <td><?php echo htmlspecialchars($course['semester']); ?></td>
                                    <td><?php echo htmlspecialchars($course['academic_year']); ?></td>
                                    <td><?php echo htmlspecialchars($course['full_name'] ?? 'Unassigned'); ?></td>
                                    <td><?php echo date("Y-m-d H:i:s", strtotime($course['created_at'])); ?></td>
                                    <td>

                                        <div class="d-flex gap-2">
                                            <a href="?id=<?php echo htmlspecialchars($course['course_id']); ?>" class="btn btn-warning btn-sm">Edit</a>
                                        
                                            <form id="delete-form-<?php echo $course['course_id']; ?>" 
                                                action="" method="POST" style="display:inline;">
                                                <input type="hidden" name="course_id" value="<?php echo $course['course_id']; ?>">
                                                <button type="button" class="btn btn-danger btn-sm" onclick="deleteCourse(<?php echo $course['course_id']; ?>)">Delete</button>
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


    <script>

        // Search Function
        document.getElementById('search-course').addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();
            document.querySelectorAll('.table tbody tr').forEach(row => {
                const text = Array.from(row.getElementsByTagName('td')).map(td => td.textContent.trim().toLowerCase()).join(' ');
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });

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

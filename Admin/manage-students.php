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

$edit_student = null;
$students = [];

// Fetch all students
try {
    $sql = "SELECT * FROM students ORDER BY student_name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
}

// Edit student logic
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $student_id = $_GET['id'];

    try {
        $sql = "SELECT * FROM students WHERE student_id = :student_id ORDER BY student_name ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":student_id", $student_id, PDO::PARAM_INT);
        $stmt->execute();
        $edit_student = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
}
?>


<!-- Manage-student.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'head.php'; ?>
    <title>Manage Student - Smart Attendance Monitoring System</title>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="main" id="main">
        <h1>Manage Student</h1>

        <div class="container mt-4">
            <div class="card shadow-lg">
                <div class="card-header text-center">
                    <h2 class="mb-0"><?php echo $edit_student ? 'Edit' : 'Add';?> Student</h2>
                </div>
                <div class="card-body">
                    <form action="../admin/scripts/create-student.php" method="POST" enctype="multipart/form-data">
                        <?php if ($edit_student): ?>
                            <input type="hidden" name="student_id" value="<?= htmlspecialchars($edit_student['student_id']) ?>">
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="school_student_id">School ID</label>
                                    <input type="text" class="form-control" id="school_student_id" name="school_student_id" required value="<?= htmlspecialchars($edit_student['school_student_id'] ?? '') ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="student_name" class="form-label">Student Name</label>
                                    <input type="text" class="form-control" id="student_name" name="student_name" required value="<?= htmlspecialchars($edit_student['student_name'] ?? '') ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="rfid_tag">RFID</label>
                                    <input type="text" class="form-control" id="rfid_tag" name="rfid_tag" required value="<?= htmlspecialchars($edit_student['rfid_tag'] ?? '') ?>">
                                </div>


                                <div class="mb-3">
                                    <label for="program">Academic Program</label>
                                    <input type="text" class="form-control" id="program" name="program" required value="<?= htmlspecialchars($edit_student['program'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="year_level" class="form-label">Year Level</label>
                                    <select class="form-select select2" name="year_level" id="year_level" required>
                                        <option value="" disabled <?php echo !$edit_student ? 'selected' : ''; ?>>--- Select Year Level---</option>
                                        <option value="1st Year" <?php echo ($edit_student && $edit_student['year_level'] == '1st Year') ? 'selected' : ''; ?>>1st Year</option>
                                        <option value="2nd Year" <?php echo ($edit_student && $edit_student['year_level'] == '2nd Year') ? 'selected' : ''; ?>>2nd Year</option>
                                        <option value="3rd Year" <?php echo ($edit_student && $edit_student['year_level'] == '3rd Year') ? 'selected' : ''; ?>>3rd Year</option>
                                        <option value="4th Year" <?php echo ($edit_student && $edit_student['year_level'] == '4th Year') ? 'selected' : ''; ?>>4th Year</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="password">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Must be at least 8 characters." minlength="8" <?= !$edit_student ? 'required' : ''; ?>>
                                </div>

                                <div class="mb-3">
                                    <label for="picture" class="form-label">Profile Picture</label>
                                    <input type="file" class="form-control" id="picture" name="picture" accept="image/*">
                                    <input type="hidden" name="current_picture" value="<?php echo htmlspecialchars($edit_student['picture'] ?? ''); ?>">
                                    <?php if (!empty($edit_student['picture'])): ?>
                                        <img src="../../assets/uploads/<?php echo htmlspecialchars($edit_student['picture']); ?>" alt="Profile Picture" style="max-width: 150px; margin-top: 10px;">
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3 d-flex justify-content-center">
                            <button type="submit" class="btn btn-primary w-50"><?= $edit_student ? 'Edit' : 'Add'; ?> Student</button>
                        </div>

                        <?php if (!empty($_SESSION['error'])): ?>
                                <div class="alert alert-danger">
                                    <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($_SESSION['message'])): ?>
                                <div class="alert alert-success">
                                    <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
                                </div>
                            <?php endif; ?>

                        <div id="message-container"></div>   
                    </form>
                </div>
            </div>
        </div>


        <div class="student-container">

            <h2 class="mb-0 text-center">Student Record</h2>
         
            <!-- Search Bar -->     
            <div class="search-wrapper">
                <input type="text" id="search-student" placeholder="Search by Name, School ID, Program" class="search-input">
            </div>
            <div class="table-wrapper">
                <!-- Bootstrap Table Example -->
                <table class="table table-striped table-bordered tContainer">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Picture</th>
                            <th>School ID</th>
                            <th>Student Name</th>
                            <th>RFID</th>
                            <th>Academic Program</th>
                            <th>Year Level</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>    
                    <tbody  class="student-list">
                       <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="9" style="text-align: center; font-weight: bold;">No Student Records found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($students as $index => $student): ?>
                                <tr data-student-id="<?php echo htmlspecialchars(string: $student['student_id']);?>">
                                    <td><?php echo htmlspecialchars($index + 1); ?></td>
                                    <td>
                                        <img src="<?php echo !empty($student['picture']) ? '../assets/uploads/' . htmlspecialchars($student['picture']) : '../uploads/default.png'; ?>"
                                            alt="User Picture" class="user-picture">
                                    </td>
                                    <td><?php echo htmlspecialchars($student['school_student_id']);?></td>
                                    <td><?php echo htmlspecialchars($student['student_name']);?></td>
                                    <td><?php echo htmlspecialchars($student['rfid_tag']);?></td>
                                    <td><?php echo htmlspecialchars($student['program']);?></td>
                                    <td><?php echo htmlspecialchars($student['year_level']);?></td>
                                    <td><?php echo htmlspecialchars($student['created_at']) ?></td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="?id=<?php echo htmlspecialchars(string: $student['student_id']);?>"   
                                            class="btn btn-warning btn-sm">Edit</a>

                                            <form id="delete-form-<?php echo $student['student_id']; ?>" 
                                                action="" method="POST">
                                                <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                                                <button type="button" class="btn btn-danger btn-sm" 
                                                onclick="deleteStudent(<?php echo $student['student_id']; ?>)">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach?>
                        <?php endif?>    
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
            document.getElementById('search-student').addEventListener('input', function () {
                const query = this.value.trim().toLowerCase();
                document.querySelectorAll('.table tbody tr').forEach(row => {
                    const text = Array.from(row.getElementsByTagName('td')).map(td => td.textContent.trim().toLowerCase()).join(' ');
                    row.style.display = text.includes(query) ? '' : 'none';
                });
            });
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

<script>
    document.getElementById('rfid_tag').addEventListener('input', function(event) {
        // Automatically capture the scanned RFID
        let rfidInput = event.target.value;
        
        // Here you can add any logic to handle the RFID (e.g., ensure it's the correct format)
        console.log("Scanned RFID:", rfidInput);
    });

    // You can also trigger the event after the tag is scanned if you want to add more logic
    function onRFIDScan(rfid) {
        document.getElementById('rfid_tag').value = rfid;
    }
</script>

</body>
</html>
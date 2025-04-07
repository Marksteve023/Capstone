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

$query = "SELECT c.course_id, c.course_name, c.section 
        FROM assigned_courses ac
        JOIN courses c ON ac.course_id = c.course_id
        WHERE ac.user_id = ?";

$stmt = $conn->prepare($query);
$stmt->bindParam(1, $user_id, PDO::PARAM_INT);
$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'head.php'; ?>
    <title>Attendance - Smart Attendance Monitoring System</title>
</head>
<body>

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?> 

    <!-- Main Content -->
    <main class="main" id="main">
        <h1>Attendance</h1>
        <div class="container d-flex justify-content-center align-items-center">
            <div class="card shadow-lg p-4">
                <div class="mb-3">
                    <h2 class="text-center">Create Attendance</h2>
                </div>
                <form id="CreateAttendanceForm">
                    <div class="row">
                        <!-- Course Selection -->
                        <div class="col-md-3">
                            <label for="course_name" class="form-label">Course</label>
                            <select name="course_name" id="course_name" class="form-select" required>
                                <option value="" disabled selected>----- Select Course -----</option>
                                <?php foreach ($courses as $course) { ?>
                                    <option value="<?= $course['course_id']; ?>">
                                        <?= htmlspecialchars($course['course_name'] . " - " . $course['section']); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <!-- Set Group Selection -->
                        <div class="col-md-3">
                            <label for="set_group" class="form-label">Set Group</label>
                            <select name="set_group" id="set_group" class="form-select" required>
                                <option value="" disabled selected>----- Select Set Group -----</option>
                                <option value="Set A">Set A</option>
                                <option value="Set B">Set B</option>
                            </select>
                        </div>

                        <!-- Date Selection -->
                        <div class="col-md-3">
                            <label for="attendance_date" class="form-label">Date</label>
                            <div class="input-group">
                                <span class="input-group-text" id="datePickerIcon" style="cursor: pointer;">
                                    <i class="bi bi-calendar"></i>
                                </span>
                                <input type="date" name="attendance_date" id="attendance_date" class="form-control" required>
                            </div>
                        </div>

                        <!-- Time Selection -->
                        <div class="col-md-3">
                            <label for="attendance_time" class="form-label">Time</label>
                            <div class="input-group">
                                <span class="input-group-text" id="timePickerIcon" style="cursor: pointer;">
                                    <i class="bi bi-clock"></i>
                                </span>
                                <input type="time" name="attendance_time" id="attendance_time" class="form-control" required>
                            </div>
                        </div>

                        
                    </div>
                    <div class="text-center mt-4">
                        <button class="btn btn-primary" id="createBtn" type="submit">
                            Create Attendance
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Attendance Modal -->
        <div class="modal fade" id="attendanceModal" tabindex="-1" aria-labelledby="attendanceModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-custom">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title w-100 text-center" id="attendanceModalLabel">Course | Attendance</h5>
                        <button type="button" class="btn-close position-absolute end-0 me-3" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Student Name</th>
                                    <th>Course</th>
                                    <th>Section</th>
                                    <th>Status</th>
                                    <th>Timestamp</th>
                                </tr>
                            </thead>
                            <tbody id="attendanceTable">
                                <!-- Attendance data will be appended here -->
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-success" id="saveBtn">Save Attendance</button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../assets/js/global.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
   
    <script>
       // Date Picker Icon Clicked
        document.getElementById('datePickerIcon').addEventListener('click', function() {
            document.getElementById('attendance_date').showPicker(); // Opens the date picker
        });

        // Time Picker Icon Clicked
        document.getElementById('timePickerIcon').addEventListener('click', function() {
            document.getElementById('attendance_time').showPicker(); // Opens the time picker (fixed typo here)
        });

        // Create Attendance Button Clicked
        document.getElementById("CreateAttendanceForm").addEventListener("submit", function (event) {
            event.preventDefault();
            
            var courseSelect = document.getElementById("course_name");
            var setGroup = document.getElementById("set_group").value;
            var date = document.getElementById("attendance_date").value;
            var time = document.getElementById("attendance_time").value;

            if (!courseSelect.value || !setGroup || !date || !time) {
                alert("Please fill in all required fields!");
                return;
            }

            // Get selected course name and section
            var selectedOption = courseSelect.options[courseSelect.selectedIndex].text;

            // Update modal title
            document.getElementById("attendanceModalLabel").innerText = `${selectedOption} | ${setGroup} | ${date} | Attendance`;

            // Show the modal
            var attendanceModal = new bootstrap.Modal(document.getElementById("attendanceModal"));
            attendanceModal.show();
        });

        // WebSocket Connection
        var socket = new WebSocket("ws://localhost:8080/attendance");
        var attendanceData = [];

        // Receive RFID scan data
        socket.onmessage = function(event) {
            var data = JSON.parse(event.data);
            
            // Check if student already exists in the table
            if (!attendanceData.some(att => att.student_id === data.student_id)) {
                attendanceData.push(data);
                $("#attendanceTable").append(
                    `<tr>
                        <td>${data.student_id}</td>
                        <td>${data.student_name}</td>
                        <td>${data.course_id}</td>
                        <td>${data.section}</td>
                        <td>${data.status}</td>
                        <td>${data.timestamp}</td>
                    </tr>`
                );
            }
        };

       // Save button clicked -> Send data to PHP
        $("#saveBtn").click(function() {
            // Define your class start time (you can fetch this dynamically if needed)
            var classStartTime = new Date($("#attendance_time").val()); // Use form time input
            var lateThreshold = 10 * 60 * 1000; // 10 minutes late threshold in milliseconds

            // If no students have been scanned, fetch all students and mark unscanned as Absent
            if (attendanceData.length === 0) {
                var selectedCourseId = $("#course_name").val();
                var setGroup = $("#set_group").val();

                $.post("../Teacher/scripts/get_students_for_course.php", { course_id: selectedCourseId, set_group: setGroup }, function(response) {
                    var studentsInCourse = JSON.parse(response);

                    studentsInCourse.forEach(function(student) {
                        if (!attendanceData.some(att => att.student_id === student.student_id)) {
                            attendanceData.push({
                                student_id: student.student_id,
                                student_name: student.student_name,
                                course_name: student.course_name,
                                section: student.section,
                                status: 'Absent',
                                timestamp: new Date().toISOString()
                            });
                        }
                    });
                    saveAttendance();
                });
            } else {

                // If students have scanned, check for Present, Late, or Absent status based on timestamp
                attendanceData.forEach(function(attendance) {
                    var scanTime = new Date(attendance.timestamp); // Timestamp when student scanned

                    if (scanTime <= classStartTime) {
                        attendance.status = 'Present'; // Mark as Present if scanned before or at class start time
                    } else if (scanTime > classStartTime && scanTime <= classStartTime + lateThreshold) {
                        attendance.status = 'Late'; // Mark as Late if scanned within the late threshold
                    } // else {
                        //attendance.status = 'Absent'; // Mark as Absent if scanned after the late threshold
                    //}
                });

                // Proceed to save attendance data
                saveAttendance();
            }
        });

        function saveAttendance() {
            $("#saveBtn").html('<span class="spinner-border spinner-border-sm"></span> Saving...').prop("disabled", true);

            $.post("../Teacher/scripts/save_attendance.php", { attendance: JSON.stringify(attendanceData) }, function(response) {
                alert(response);
                attendanceData = []; // Clear the attendance data after saving
                $("#attendanceTable").html(""); // Clear the table in the modal
                $("#attendanceModal").modal("hide"); // Close the modal
                $("#saveBtn").html("Save Attendance").prop("disabled", false);
            });
        }


    </script>   
</body>
</html>

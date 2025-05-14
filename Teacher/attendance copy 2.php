<?php

// ============================
// Create Attendance Page (Teacher)
// Allows teachers to initiate and prepare attendance sessions
// ============================

session_start();
require_once '../config/db.php';

// --- Access control: Redirect if user is not logged in or not a teacher
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);
$user_role = $_SESSION['role'];

if ($user_role !== 'teacher') {
    header("Location: ../index.php");
    exit();
}

// --- Fetch available courses and sections assigned to the logged-in teacher
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
    <!-- Include shared head resources (e.g., meta tags, Bootstrap, custom CSS) -->
    <?php include 'head.php'; ?>
    <title>Attendance - Smart Attendance Monitoring System</title>
</head>
<body>

    <!-- Sidebar navigation -->
    <?php include 'sidebar.php'; ?> 

    <!-- Main content container -->
    <main class="main" id="main">
        <h1>Attendance</h1>

        <!-- Create Attendance Form Card -->
        <div class="container mt-4 d-flex justify-content-center align-items-center">
            <div class="card shadow-lg p-4">
                
                <!-- Title -->
                <div class="mb-3">
                    <h2 class="text-center">Create Attendance</h2>
                </div>

                <!-- Attendance Form -->
                <form id="CreateAttendanceForm">
                    <div class="row">
                        <!-- Field: Course & Section -->
                        <div class="col-md-3">
                            <label for="course_name" class="form-label">Course & Section</label>
                            <select name="course_name" id="course_name" class="form-select" required>
                                <option value="" disabled selected>----- Select Course & Section -----</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?= $course['course_id']; ?>" data-section="<?= $course['section']; ?>">
                                        <?= htmlspecialchars($course['course_name'] . " - " . $course['section']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Field: Set Group -->
                        <div class="col-md-3">
                            <label for="set_group" class="form-label">Set Group</label>
                            <select name="set_group" id="set_group" class="form-select" required>
                                <option value="" disabled selected>----- Select Set Group -----</option>
                                <option value="Set A">Set A</option>
                                <option value="Set B">Set B</option>
                            </select>
                        </div>

                        <!-- Field: Attendance Date -->
                        <div class="col-md-3">
                            <label for="attendance_date" class="form-label">Date</label>
                            <div class="input-group">
                                <span class="input-group-text" id="datePickerIcon" style="cursor: pointer;">
                                    <i class="bi bi-calendar"></i>
                                </span>
                                <input type="date" name="attendance_date" id="attendance_date" class="form-control" required>
                            </div>
                        </div>

                        <!-- Field: Attendance Time -->
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

                    <!-- Create Button -->
                    <div class="text-center mt-4">
                        <button class="btn btn-primary" id="createBtn" type="submit">
                            Create Attendance
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal: Student Attendance Marking -->
        <div class="modal fade" id="attendanceModal" tabindex="-1" aria-labelledby="attendanceModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-custom">
                <div class="modal-content">
                    
                    <!-- Modal Header -->
                    <div class="modal-header">
                        <h5 class="modal-title w-100 text-center" id="attendanceModalLabel">Course | Attendance</h5>
                        <button type="button" class="btn-close position-absolute end-0 me-3" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <!-- Modal Body: Student Attendance Table -->
                    <div class="modal-body">

                        <!-- Display student picture -->
                        <div id="studentPictureContainer" class="text-center mb-3">
                            <!-- Student picture will be dynamically inserted here -->
                        </div>
    
                        <table class="table table-striped table-bordered tContainer">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>School Student ID</th>
                                    <th>Student Name</th>
                                    <th>Course</th>
                                    <th>Section</th>
                                    <th>Set Group</th>
                                    <th>Status</th>
                                    <th>Timestamp</th>
                                </tr>
                            </thead>
                            <tbody id="attendanceTable">
                                <!-- Attendance data will be appended here dynamically -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Modal Footer with Save Button -->
                    <div class="modal-footer">
                        <button class="btn btn-success" id="saveBtn">Save Attendance</button>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <!--=============== MAIN JS ===============-->
    <script src="../assets/js/global.js"></script>

    <!-- Bootstrap JS and Popper.js -->
    <script src="../assets/js/popper.min.js"></script>
    <!--<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>-->

    <script src="../assets/js/bootstrap.min.js"></script>
    <!--<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>-->
    
    <!-- Include jQuery and Select2 JS -->
    <script src="../assets/js/jquery.min.js"></script>
    <!--<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>-->
    
    <script>

        // =================================
        // JS: Attendance Creation via WebSocket
        // =================================

        $(document).ready(function () {
            // Focus and show native date picker when calendar icon is clicked
            $('#datePickerIcon').click(function () {
                $('#attendance_date').focus();
                document.getElementById("attendance_date").showPicker();
            });

            // Focus and show native time picker when clock icon is clicked
            $('#timePickerIcon').click(function () {
                $('#attendance_time').focus();
                document.getElementById("attendance_time").showPicker();
            });
        });

        // Initialize WebSocket connection (update URL as needed)
        const socket = new WebSocket("ws://localhost:9000");

        socket.onerror = function (event) {
            console.error("WebSocket connection error:", event);
        };

        // Triggered to notify server of a new RFID scan for attendance
        function createAttendance(rfid) {
            const message = {
                type: 'attendance',
                rfid: rfid
            };
            socket.send(JSON.stringify(message));
        }

        // =================================
        // JS: Create Attendance Form Submit
        // =================================

        document.getElementById("CreateAttendanceForm").addEventListener("submit", function (event) {
            event.preventDefault();

            const courseSelect = document.getElementById("course_name");
            const setGroup = document.getElementById("set_group").value;
            const date = document.getElementById("attendance_date").value;
            const time = document.getElementById("attendance_time").value;

            if (!courseSelect.value || !setGroup || !date || !time) {
                alert("Please fill in all required fields!");
                return;
            }

            const selectedOption = courseSelect.options[courseSelect.selectedIndex].text;
            const courseName = selectedOption.split(" - ")[0];
            const section = courseSelect.options[courseSelect.selectedIndex].getAttribute('data-section');

            document.getElementById("attendanceModalLabel").innerText = `${selectedOption} | ${setGroup} | Attendance`;

            // Fetch students not yet scanned
            $.post("../Teacher/scripts/get_students.php", {
                course_id: courseSelect.value,
                set_group: setGroup,
                attendance_date: date,
                attendance_time: time,
                courseName: courseName,
                section: section
            }, function (response) {
                try {
                    const students = JSON.parse(response);
                    if (Array.isArray(students)) {
                        $("#attendanceTable").html(""); // Clear table
                        const allStudents = [];

                        students.forEach(student => {
                            if (student.student_id) {
                                allStudents.push({
                                    student_id: student.student_id,
                                    school_student_id: student.school_student_id,
                                    student_name: student.student_name,
                                    rfid_tag: student.rfid_tag,
                                    status: "Absent",
                                    courseName,
                                    section,
                                    setGroup,
                                    timestamp: "",
                                    attendance_time: time
                                });
                            }
                        });

                        window.allStudents = allStudents; // Store globally
                    } else {
                        console.error("Invalid data format from server:", students);
                    }
                } catch (e) {
                    console.error("Error parsing server response:", e);
                }
            });

            // Show modal
            const attendanceModal = new bootstrap.Modal(document.getElementById("attendanceModal"));
            attendanceModal.show();
        });

        // =========================================
        // JS: WebSocket Message Handler (RFID Scan)
        // =========================================
        socket.onmessage = function (event) {
            const data = JSON.parse(event.data);

            if (data.type === "attendance") {
                const scannedStudent = window.allStudents.find(student => student.rfid_tag === data.rfid);

                if (scannedStudent) {
                    const currentTime = new Date();
                    const attendanceTimeStr = document.getElementById("attendance_time").value;
                    const attendanceTime = new Date(currentTime.toDateString() + " " + attendanceTimeStr);
                    const timeDifference = (currentTime - attendanceTime) / 60000;
                    const status = timeDifference > 10 ? "Late" : "Present";
                    const timestamp = currentTime.toLocaleString();

                    let studentRow = document.querySelector(`tr[data-student-id="${scannedStudent.student_id}"]`);

                    if (!studentRow) {
                        studentRow = document.createElement("tr");
                        studentRow.setAttribute("data-student-id", scannedStudent.student_id);

                        // Assuming the picture field stores the image filename (relative path)
                        const pictureSrc = scannedStudent.picture ? `../assets/uploads/${scannedStudent.picture}` : '../assets/images/default.jpg'; // fallback to default image

                        studentRow.innerHTML = `
                            <td>${window.allStudents.indexOf(scannedStudent) + 1}</td>
                            <td>${scannedStudent.school_student_id}</td>
                            <td>${scannedStudent.student_name}</td>
                            <td>${scannedStudent.courseName}</td>
                            <td>${scannedStudent.section}</td>
                            <td>${scannedStudent.setGroup}</td>
                            <td class="attendance-status">${status}</td>
                            <td class="attendance-time">${timestamp}</td>
                        `;
                        document.getElementById("attendanceTable").appendChild(studentRow);
                    } else {
                        studentRow.querySelector(".attendance-status").textContent = status;
                        studentRow.querySelector(".attendance-time").textContent = timestamp;
                    }

                    scannedStudent.status = status;
                    scannedStudent.timestamp = timestamp;

                    // Display the student's picture in a separate section of the modal
                    const pictureContainer = document.getElementById("studentPictureContainer"); // Add this div in the modal HTML
                    const pictureSrc = scannedStudent.picture ? `../assets/uploads/${scannedStudent.picture}` : '../assets/uploads/default.jpg';
                    pictureContainer.innerHTML = `
                        <h5>${scannedStudent.student_name}</h5>
                        <img src="${pictureSrc}" alt="Student Picture" class="img-fluid rounded" style="width: 150px; height: 150px; object-fit: cover;">
                    `;
                }
            }
        };


        // =================================
        // JS: Save Attendance Button Click
        // =================================

        document.getElementById("saveBtn").addEventListener("click", function () {
            const attendanceData = window.allStudents.map(student => ({
                student_id: student.student_id,
                status: student.status,
                timestamp: student.timestamp,
                course_id: document.getElementById("course_name").value,
                setGroup: student.setGroup,
                attendance_date: document.getElementById("attendance_date").value,
                attendance_time: document.getElementById("attendance_time").value
            }));

            $.post("../Teacher/scripts/save_attendance.php", { attendance_data: JSON.stringify(attendanceData) }, function (response) {
                console.log(response);
                if (response === "success") {
                    alert("Attendance saved successfully!");
                    const attendanceModal = bootstrap.Modal.getInstance(document.getElementById("attendanceModal"));
                    attendanceModal.hide();
                    $("#attendanceTable").html(""); // Reset table
                    document.getElementById("CreateAttendanceForm").reset(); // Reset form
                } else {
                    alert("There was an error saving attendance. Please try again.");
                }
            });
        });
</script>
</body>
</html>

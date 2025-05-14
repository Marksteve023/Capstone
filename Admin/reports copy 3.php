<?php 
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$sql = "SELECT DISTINCT 
    u.user_id, u.full_name,
    c.course_id, c.course_name, c.section,
    a.set_group
FROM assigned_courses ac
INNER JOIN users u ON ac.user_id = u.user_id
INNER JOIN courses c ON ac.course_id = c.course_id
LEFT JOIN attendance a ON a.course_id = c.course_id
WHERE u.role = 'teacher'
ORDER BY u.full_name, c.course_name ASC";

$stmt = $conn->prepare($sql);
$stmt->execute();
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare unique filter values
$courses = [];
$sections = [];
$set_groups = [];
$teachers = [];

foreach ($records as $session) {
    $courses[$session['course_name']] = $session['course_name'];
    $sections[$session['section']] = $session['section'];
    if (!empty($session['set_group'])) {
        $set_groups[$session['set_group']] = $session['set_group'];
    }
    $teachers[$session['full_name']] = $session['full_name'];
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Include shared head elements (e.g., Bootstrap, meta tags, CSS) -->
    <?php include 'head.php'; ?>
    <title>Reports - Smart Attendance Monitoring System</title>
</head>
<body>

<!-- Include sidebar navigation -->
<?php include 'sidebar.php'; ?>

<main class="main" id="main">
    <h1>Reports</h1>

    <!-- Filter Form Section -->
    <div class="container mt-4">

        <div class="card shadow-lg">
            <div class="card-header">   
                <h2 class="mb-0 text-center">
                    Attendance Report
                </h2>
            </div>

            <div class="card-body">
                <form id="filterForm">
                    <div class="row g-3">

                        <!-- Month Picker -->
                        <div class="col-md-2 mb-3">
                            <label for="month" class="form-label">Select Month:</label>
                            <div class="input-group">
                                <span class="input-group-text" id="monthPickerIcon" style="cursor: pointer;">
                                    <i class="bi bi-calendar-month"></i>
                                </span>
                                <input type="month" name="month" id="month" class="form-control">
                            </div>
                        </div>

                        <!-- Week Picker -->
                        <div class="col-md-2 mb-3">
                            <label for="week" class="form-label">Select Week:</label>
                            <div class="input-group">
                                <span class="input-group-text" id="weekPickerIcon" style="cursor: pointer;">
                                    <i class="bi bi-calendar2-week"></i>
                                </span>
                                <input type="week" id="week" name="week" class="form-control">
                            </div>
                        </div>

                        <!-- Course Dropdown -->
                        <div class="col-md-2 mb-3">
                            <label for="course_name" class="form-label">Course Name</label>
                            <select name="course_name" id="course_name" class="form-select select2">
                                <option value="" selected>All Courses</option>
                                <?php foreach ($courses as $course_name): ?>
                                    <option value="<?= htmlspecialchars($course_name) ?>"><?= htmlspecialchars($course_name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Section Dropdown -->
                        <div class="col-md-2 mb-3">
                            <label for="section" class="form-label">Section</label>
                            <select id="section" name="section" class="form-select select2">
                                <option value="" selected>All Sections</option>
                                <?php foreach ($sections as $section): ?>
                                    <option value="<?= htmlspecialchars($section) ?>"><?= htmlspecialchars($section) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                            <!-- Set Group Dropdown -->
                            <div class="col-md-2 mb-3">
                                <label for="setGroup" class="form-label">Set Group</label>
                                <select name="set_group" id="setGroup" class="form-select">
                                    <option value="" selected>All Groups</option>
                                    <option value="Set A">Set A</option>
                                    <option value="Set B">Set B</option>
                                </select>
                            </div>

                        <!-- Teacher Dropdown -->
                        <div class="col-md-2 mb-3">
                            <label for="teacher" class="form-label">Teacher</label>
                            <select name="teacher" id="teacher" class="form-select select2">
                                <option value="" selected>All Teachers</option>
                                <?php foreach ($teachers as $name): ?>
                                    <option value="<?= htmlspecialchars($name) ?>"><?= htmlspecialchars($name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- View Report Button -->
                    <div class="row mt-4">
                        <div class="col text-center">
                            <button type="button" id="clearFilters" class="btn btn-outline-primary ms-2">Clear Filters</button>
                        </div>
                    </div>
                </form>
                <div class="" id="message-container"></div> 
            </div>
        </div>
    </div>

    <!-- Student Attendance Table -->
    <div class="attendance-report mt-4">
        <div class="mb-0">
            <h2 class="text-center">Attendance report</h2>
        </div>
        
        <!-- Table Container -->
        <div class="table-wrapper">
            <table class="table table-striped table-bordered tContainer">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Course Name</th>
                        <th>Section</th>
                        <th>Set Group</th>
                        <th>Attendance Date</th>
                        <th>Attendance Time</th>
                        <th>Teacher</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="attendanceReport">
                    <!-- Table body will be populated by AJAX -->
                </tbody>
            </table>
        </div>
    </div>


    <!-- Modal: Detailed View of Student Attendance for a Session -->
    <div class="modal fade" id="attendanceModal" tabindex="-1" aria-labelledby="attendanceModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-custom">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header d-flex justify-content-center">
                    <h5 class="modal-title" id="attendanceModalLabel">Course | Attendance</h5>
                    <button type="button" class="btn-close position-absolute end-0 me-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body">
                    <div class="title-chart">
                        <h2 class="text-center">Class Attendance</h2>
                        <h6 id="reportTitle" class="text-center"></h6>
                    </div>

                    <!-- Charts -->
                    <div class="chart-row d-flex flex-wrap gap-4 justify-content-center mt-4">
                        <!-- Pie Chart for Attendance -->
                        <div class="chart-card p-3 shadow-sm border rounded">
                            <h5 class="text-center">Pie Chart</h5>
                            <canvas id="attendancePieChart"></canvas>
                        </div>

                        <!-- Bar Chart for Attendance -->
                        <div class="chart-card p-3 shadow-sm border rounded">
                            <h5 class="text-center">Bar Chart</h5>
                            <canvas id="attendanceBarChart"></canvas>
                        </div>
                    </div>

                    <!-- Search Field -->
                    <div class="search-wrapper-container my-3 text-center">
                        <input type="text" id="search-students" class="search-input text-center" placeholder="Search by Name, School ID">
                    </div>

                    <!-- Student Attendance Table -->
                    <div class="table-responsive" id="attendanceTable">
                        <table class="table table-bordered table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>School Student ID</th>
                                    <th>Student Name</th>
                                    <th>Course</th>
                                    <th>Section</th>
                                    <th>Set Group</th>
                                    <th>Timestamp</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="attendanceDetailsBody">
                                <!-- Rows populated dynamically via JavaScript -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Modal Footer with Action Buttons -->
                    <div class="modal-footer">
                        <button class="btn btn-outline-success mt-3" id="attendanceDownload">Download</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Student Attendance Report History -->
    <div class="modal fade" id="studentReportModal" tabindex="-1" aria-labelledby="studentReportModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-custom">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header d-flex justify-content-center">
                    <h5 class="modal-title text-dark" id="studentReportModalLabel">Attendance Reports History</h5>
                    <button type="button" class="btn-close position-absolute end-0 me-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body">

                    <!-- Student Chart Titles -->
                    <div class="title-chart">
                        <h2 class="text-center text-dark">Student Attendance History</h2>
                        <h6 id="studentReportTitle" class="text-center"></h6>
                    </div>

                    <!-- Student Attendance Charts -->
                    <div class="chart-row d-flex flex-wrap gap-4 justify-content-center mt-4">
                        <div class="chart-card p-3 shadow-sm border rounded">
                            <h5 class="text-center">Pie Chart</h5>
                            <canvas id="studentPieChart"></canvas>
                        </div>
                        <div class="chart-card p-3 shadow-sm border rounded">
                            <h5 class="text-center">Bar Chart</h5>
                            <canvas id="studentBarChart"></canvas>
                        </div>
                    </div>

                    <div class="card p-4 rounded shadow-sm mt-4 mb-3">

                        <h3 class="mb-3 text-center">Filter Attendance Records</h3>

                        <div class="row mb-3">
                            <div class="col-md-4 offset-md-2">
                                <label for="attendanceMonthFilter" class="form-label">Select Month</label>
                                <div class="input-group">
                                    <span class="input-group-text" id="MonthsPickerIcon"><i class="bi bi-calendar3"></i></span>
                                    <input type="month" id="attendanceMonthFilter" class="form-control" />
                                </div>
                            </div>
                            <!-- Status Filter -->
                            <div class="col-md-4">
                                <label for="statusFilter" class="form-label">Select Status</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-filter-circle"></i></span>
                                    <select id="statusFilter" class="form-select">
                                        <option value="">All</option>
                                        <option value="Present">Present</option>
                                        <option value="Late">Late</option>
                                        <option value="Absent">Absent</option>
                                        <option value="Excused">Excused</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 offset-md-4">
                                <button class="btn btn-outline-secondary w-100" id="clearFilters">
                                    <i class="bi bi-x-circle me-2"></i>Clear Filters
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Table with Student Attendance History -->
                    <div class="table-responsive" id="attendanceTable">
                        <table class="table table-bordered table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Course</th>
                                    <th>Section</th>
                                    <th>Set Group</th>
                                    <th>Attendance Date</th>
                                    <th>Attendance Time</th>
                                    <th>Timestamp</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="reportDetailsBody">
                                <!-- Modal Student report records inserted here -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Modal Footer with Download Button -->
                    <div class="modal-footer">
                        <button class="btn btn-outline-success mt-3" id="studentReportDownload">Download</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</main>
<!--=============== MAIN JS ===============-->
<script src="../assets/js/global.js"></script>
<script src="../assets/js/popper.min.js"></script>
<script src="../assets/js/bootstrap.min.js"></script>
<script src="../assets/js/chart.min.js"></script>
<script src="../assets/js/jquery.min.js"></script>
<script src="../assets/js/select2.min.js"></script>

<script>
    // Function to format time into AM/PM format
    const formatToAmPm = (timeStr) => {
        if (!timeStr) return ''; // Return empty string if timeStr is not provided
        const [hourStr, minuteStr] = timeStr.split(':');
        let hour = parseInt(hourStr, 10);
        const ampm = hour >= 12 ? 'PM' : 'AM'; // Determine if it's AM or PM
        hour = hour % 12 || 12; // Convert hour to 12-hour format
        return `${hour.toString().padStart(2, '0')}:${minuteStr} ${ampm}`; // Return formatted time
    };

    // Function to get the color based on attendance status
    const getStatusColor = (status) => {
        switch (status) {
            case 'Present': return 'primary'; // Blue for present
            case 'Absent': return 'success'; // Green for absent
            case 'Late': return 'danger'; // Red for late
            case 'Excused': return 'purple'; // Purple for excused
            default: return 'secondary'; // Default for undefined statuses
        }
    };

    

    $(document).ready(() => {
        let allAttendanceRecords = []; // Store all attendance records fetched from the server

        // Function to fetch attendance data from the server via AJAX
        const fetchAttendanceData = () => {
            $.ajax({
                url: '../Admin/scripts/fetch_attendance_records.php', // Endpoint for fetching records
                type: 'POST',
                dataType: 'json', // Expect JSON data
                success: (response) => {
                    allAttendanceRecords = response; // Save response data
                    renderTable(allAttendanceRecords); // Call function to render the table with the fetched data
                },
                error: () => {
                    // Handle error if data fetching fails
                    $('#attendanceReport').html('<tr><td colspan="8" class="text-center text-danger">Failed to load data.</td></tr>');
                }
            });
        };

        // Function to render attendance table dynamically
        const renderTable = (data) => {
            const $tbody = $('#attendanceReport').empty(); // Clear existing table content
            if (data.length === 0) {
                // Show message if no attendance records are found
                $tbody.append('<tr><td colspan="8" class="text-center">No attendance records found.</td></tr>');
                return;
            }

            // Loop through each attendance record and create table rows
            $.each(data, (index, record) => {
                const formattedTime = formatToAmPm(record.attendance_time); // Format time
                const row = `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${record.course_name}</td>
                        <td>${record.section}</td>
                        <td>${record.set_group}</td>
                        <td>${record.attendance_date}</td>
                        <td>${formattedTime}</td>
                        <td>${record.teacher_name}</td>
                        <td>
                            <div class="d-flex gap-3 justify-content-center">
                                <button class="btn btn-sm btn-outline-info view-attendance-btn"
                                    data-course-name="${record.course_name}"
                                    data-section="${record.section}"
                                    data-set-group="${record.set_group}"
                                    data-date="${record.attendance_date}"
                                    data-time="${record.attendance_time}">
                                    <i class="bi bi-eye"></i> View
                                </button>
                                <button class="btn btn-outline-danger btn-sm delete-button"
                                    data-course-id="${record.course_id}"
                                    data-section="${record.section}"
                                    data-set-group="${record.set_group}"
                                    data-attendance-date="${record.attendance_date}"
                                    data-attendance-time="${record.attendance_time}">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </div>
                        </td>
                    </tr>`;
                $tbody.append(row); // Append row to the table body
            });

            addEventListeners(); // Attach event listeners for view and delete buttons
        };

        // Function to add event listeners to view and delete buttons
        const addEventListeners = () => {
            $('.view-attendance-btn').off('click').on('click', function () {
                // Capture button data and call viewReport function
                const courseName = $(this).data('course-name');
                const section = $(this).data('section');
                const setGroup = $(this).data('set-group');
                const attendanceDate = $(this).data('date');
                const attendanceTime = $(this).data('time');
                viewReport(courseName, section, setGroup, attendanceDate, attendanceTime); // View the report for the selected attendance record
            });

            // Delete button functionality
            $('.delete-button').off('click').on('click', function () {
                const courseId = $(this).data('course-id');
                const section = $(this).data('section');
                const setGroup = $(this).data('set-group');
                const attendanceDate = $(this).data('attendance-date');
                const attendanceTime = $(this).data('attendance-time');
                deleteCourse(courseId, section, setGroup, attendanceDate, attendanceTime); // Delete the attendance record
            });
        };

        // Attach event to the student report buttons inside the attendance modal
        $('#attendanceDetailsBody').off('click', '.view-student-report').on('click', '.view-student-report', function () {
            const studentID = $(this).data('student-id');
            const schoolID = $(this).data('school-id');
            const studentName = $(this).data('student-name');
            const courseName = $(this).data('course');
            const section = $(this).data('section');
            viewStudentReport(studentID, schoolID, studentName, courseName, section);
        });


        // Search function for students
        $('#search-students').on('input', function () {
            const query = $(this).val().trim().toLowerCase(); // Get search query and make it lowercase
            $('#attendanceDetailsBody tr').each(function () {
                const schoolId = $(this).find('td:nth-child(2)').text().toLowerCase(); // School ID column
                const studentName = $(this).find('td:nth-child(3)').text().toLowerCase(); // Student name column
                // Toggle row visibility based on search query matching either school ID or student name
                $(this).toggle(schoolId.includes(query) || studentName.includes(query));
            });
        });

        function applyStudentReportFilters() {
        const selectedMonth = $('#attendanceMonthFilter').val(); // Format: YYYY-MM
        const selectedStatus = $('#statusFilter').val().toLowerCase(); // present, late, etc.

        const statusCounts = { 'present': 0, 'late': 0, 'absent': 0, 'excused': 0 };

        $('#reportDetailsBody tr').each(function () {
            const date = $(this).find('td:nth-child(5)').text().trim(); // YYYY-MM-DD
            const status = $(this).find('td:nth-child(8)').text().trim().toLowerCase();

            let show = true;

            if (selectedMonth && !date.startsWith(selectedMonth)) {
                show = false;
            }

            if (selectedStatus && status !== selectedStatus) {
                show = false;
            }

            $(this).toggle(show);

            if (show && statusCounts.hasOwnProperty(status)) {
                statusCounts[status]++;
            }
        });

        updateStudentChart({
            Present: statusCounts['present'],
            Late: statusCounts['late'],
            Absent: statusCounts['absent'],
            Excused: statusCounts['excused']
        });
    }
        $('#MonthsPickerIcon').click(() => $('#attendanceMonthFilter')[0]?.showPicker?.());

        $('#attendanceMonthFilter, #statusFilter').on('change', applyStudentReportFilters);

            $('#clearFilter').on('click', function () {
            $('#attendanceMonthFilter').val('');
            $('#statusFilter').val('');
            applyStudentReportFilters();
        });

        let AttendanceRecords = {}; // Object to store current attendance record for download
        // View detailed attendance report for a specific session
        const viewReport = (courseName, section, setGroup, attendanceDate, attendanceTime) => {
            const timeDisplay = formatToAmPm(attendanceTime); // Format the attendance time

            AttendanceRecords = { course_name: courseName, section: section, set_group: setGroup, attendance_date: attendanceDate, attendance_time: attendanceTime };

            // Set the modal title to include course, section, and time information
            $('#attendanceModalLabel').text(`${courseName} - ${section} - ${setGroup} - ${attendanceDate} - ${timeDisplay}`);
            $('#attendanceModal').modal('show'); // Show the modal with attendance details

            // Fetch attendance data for the selected session
            $.ajax({
                url: '../Admin/scripts/fetch-attendance-report.php',
                type: 'GET',
                data: AttendanceRecords, // Send the selected session's attendance data
                dataType: 'json',
                success: function (data) {
                    // Check if data is empty
                    if (!data || data.length === 0) {
                        $('#attendanceDetailsBody').html('<tr><td colspan="7" class="text-center">No attendance records found for this session.</td></tr>');
                        return;
                    }

                    const statusCount = { Present: 0, Absent: 0, Late: 0, Excused: 0 }; // Initialize status count
                    const tableRows = []; // Array to hold table rows

                    // Loop through the fetched data and build table rows
                    $.each(data, function (index, entry) {
                        const displayTimestamp = entry.timestamp ? formatToAmPm(entry.timestamp) : 'N/A'; // Format timestamp if available
                        const statusColor = getStatusColor(entry.status); // Get the status color for the badge
                        statusCount[entry.status] = statusCount[entry.status] + 1 || 1; // Increment the status count

                        tableRows.push(`
                            <tr>
                                <td>${index + 1}</td>
                                <td>${entry.school_student_id}</td>
                                <td>${entry.student_name}</td>
                                <td>${entry.course_name}</td>
                                <td>${entry.section}</td>
                                <td>${entry.set_group}</td>
                                <td>${displayTimestamp}</td>
                                <td><span class="badge bg-${statusColor}">${entry.status}</span></td>
                               <td>
                                    <button class="btn btn-sm btn-outline-info view-student-report"
                                        data-student-id="${entry.student_id}"
                                        data-school-id="${entry.school_student_id}"
                                        data-student-name="${entry.student_name}"
                                        data-course="${entry.course_name}"
                                        data-section="${entry.section}"
                                        <i class="bi bi-eye"></i> view Report
                                    </button>
                                </td>
                            </tr>
                        `);
                    });

                    $('#attendanceDetailsBody').html(tableRows.join('')); // Append the table rows to the body
                    drawAttendanceCharts(statusCount); // Draw the attendance charts based on status count
                },
                error: function () {
                    $('#attendanceDetailsBody').html('<tr><td colspan="7" class="text-center text-danger">Failed to load attendance data. Please try again.</td></tr>');
                }
            });
        };

        let StudentReport = {}; // Make sure it's a global or scoped object as needed

        const viewStudentReport = (studentID, schoolID, studentName, courseName, section) => {
            // Save current student context
            StudentReport = { 
                student_id: studentID, 
                student_name: studentName, 
                school_student_id: schoolID, 
                course_name: courseName, 
                section 
            };

            // Set modal title
            $('#studentReportModalLabel').text(`${studentName} (${schoolID}) - ${courseName} - ${section}`);
            $('#studentReportModal').modal('show'); // Show the student report modal

            // Clear current table content
            $('#reportDetailsBody').html('<tr><td colspan="8" class="text-center">Loading...</td></tr>');

            // Fetch student's attendance records
            $.ajax({
                url: '../Admin/scripts/fetch_student_report.php',
                type: 'GET',
                data: StudentReport,
                dataType: 'json',
                success: function (data) {
                    if (!data || data.length === 0) {
                        $('#reportDetailsBody').html('<tr><td colspan="8" class="text-center">No attendance records found for this student.</td></tr>');
                        return;
                    }

                    // Initialize status counters and table rows
                    const statusCount = { Present: 0, Absent: 0, Late: 0, Excused: 0 };
                    const ReportRows = [];

                    // Process each attendance record
                    $.each(data, function (index, record) {
                        const displayTimestamp = record.timestamp ? formatToAmPm(record.timestamp) : 'N/A';
                        const statusColor = getStatusColor(record.status);

                        // Increment status count safely
                        if (statusCount.hasOwnProperty(record.status)) {
                            statusCount[record.status]++;
                        }

                        // Append formatted row
                        ReportRows.push(`
                            <tr>
                                <td>${index + 1}</td>
                                <td>${record.course_name}</td>
                                <td>${record.section}</td>
                                <td>${record.set_group || 'N/A'}</td>
                                <td>${record.attendance_date}</td>
                                <td>${formatToAmPm(record.attendance_time)}</td>
                                <td>${displayTimestamp}</td>
                                <td><span class="badge bg-${statusColor}">${record.status}</span></td>
                            </tr>
                        `);
                    });

                    // Update the table body
                    $('#reportDetailsBody').html(ReportRows.join(''));

                    // Draw the charts with updated data
                    updateStudentChart(statusCount);
                },
                error: function () {
                    $('#reportDetailsBody').html('<tr><td colspan="8" class="text-center text-danger">Failed to load student report data. Please try again.</td></tr>');
                }
            });
        };

        // --- Chart variables
        let attendanceChart = null;
        let studentChart = null;

        // --- Update Class Attendance Charts
        function drawAttendanceCharts(statusCount) {
            const pieCtx = document.getElementById('attendancePieChart').getContext('2d');
            const barCtx = document.getElementById('attendanceBarChart').getContext('2d');

            const labels = ['Present', 'Late', 'Absent', 'Excused'];
            const colors = ['#0d6efd', '#dc3545', '#198754', '#6f42c1'];

            if (attendanceChart?.pie) attendanceChart.pie.destroy();
            if (attendanceChart?.bar) attendanceChart.bar.destroy();

            attendanceChart = {
                pie: new Chart(pieCtx, {
                    type: 'pie',
                    data: {
                        labels,
                        datasets: [{
                            data: labels.map(label => statusCount[label] || 0),
                            backgroundColor: colors
                        }]
                    },
                    options: { responsive: true }
                }),
                bar: new Chart(barCtx, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: labels.map((label, i) => ({
                            label,
                            data: labels.map((_, j) => j === i ? (statusCount[label] || 0) : 0),
                            backgroundColor: colors[i]
                        }))
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { display: true },
                            title: { display: true, text: 'Class Attendance - Bar' }
                        },
                        scales: {
                            x: { stacked: false },
                            y: { beginAtZero: true }
                        }
                    }
                })
            };
        }

        // --- Update Student Attendance Charts
        function updateStudentChart(Statuscounts) {
            const pieCtx = document.getElementById('studentPieChart').getContext('2d');
            const barCtx = document.getElementById('studentBarChart').getContext('2d');

            const labels = ['Present', 'Late', 'Absent', 'Excused'];
            const colors = ['#0d6efd', '#dc3545', '#198754', '#6f42c1'];

            if (studentChart?.pie) studentChart.pie.destroy();
            if (studentChart?.bar) studentChart.bar.destroy();

            studentChart = {
                pie: new Chart(pieCtx, {
                    type: 'pie',
                    data: {
                        labels,
                        datasets: [{
                            data: labels.map(label => Statuscounts[label] || 0),
                            backgroundColor: colors
                        }]
                    },
                    options: { responsive: true }
                }),
                bar: new Chart(barCtx, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: labels.map((label, i) => ({
                            label,
                            data: labels.map((_, j) => j === i ? (Statuscounts[label] || 0) : 0),
                            backgroundColor: colors[i]
                        }))
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { display: true },
                            title: { display: true, text: 'Student Attendance - Bar' }
                        },
                        scales: {
                            x: { stacked: false },
                            y: { beginAtZero: true }
                        }
                    }
                })
            };
        }


        // Download attendance report
        $('#attendanceDownload').on('click', function () {
            if (!AttendanceRecords) {
                alert('Please view a report first.');
                return;
            }
            // Generate URL query string for downloading the report
            const query = `?course_name=${encodeURIComponent(AttendanceRecords.course_name)}&section=${encodeURIComponent(AttendanceRecords.section)}&set_group=${encodeURIComponent(AttendanceRecords.set_group)}&attendance_date=${encodeURIComponent(AttendanceRecords.attendance_date)}&attendance_time=${encodeURIComponent(AttendanceRecords.attendance_time)}&timestamp=${encodeURIComponent(AttendanceRecords.timestamp)}`;
            window.location.href = `../Admin/scripts/download_report.php${query}`; // Trigger download
        });

        // Delete attendance record
        const deleteCourse = (courseId, section, setGroup, attendanceDate, attendanceTime) => {
            if (!confirm("Are you sure you want to delete this attendance record?")) return; // Confirm deletion
            $.ajax({
                url: '../Admin/scripts/delete_attendance.php', // Endpoint for deletion
                type: 'POST',
                data: { course_id: courseId, section, set_group: setGroup, attendance_date: attendanceDate, attendance_time: attendanceTime },
                success: (response) => {
                    if (response === 'success') {
                        showMessage('Attendance record deleted successfully.', 'success'); // Show success message
                        fetchAttendanceData(); // Refresh the attendance data
                    } else {
                        showMessage('Failed to delete attendance. Error: ' + response, 'danger'); // Show error message
                    }
                },
                error: () => showMessage('AJAX error occurred.', 'danger') // Handle AJAX error
            });
        };

        // Function to show success or error messages
        const showMessage = (message, type = 'success') => {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            $('#message-container').html(`
                <div class="alert ${alertClass} alert-dismissible fade show mt-3" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `);
            setTimeout(() => $('#message-container .alert').fadeOut('slow', function () { $(this).remove(); }), 3000);
        };


        // Check if date is in the same week
        function isSameWeek(dateObj, weekStr) {
            const [inputYear, inputWeek] = weekStr.split('-W').map(Number);
            const firstJan = new Date(inputYear, 0, 1);
            const firstWeekDay = firstJan.getDay() || 7;
            const inputWeekStart = new Date(firstJan.setDate(firstJan.getDate() + (inputWeek - 1) * 7 - (firstWeekDay - 1)));
            const inputWeekEnd = new Date(inputWeekStart);
            inputWeekEnd.setDate(inputWeekStart.getDate() + 6);
            return dateObj >= inputWeekStart && dateObj <= inputWeekEnd;
        }
        
          // Check if date is in the same month
        function isSameMonth(dateObj, monthStr) {
            const [year, month] = monthStr.split('-').map(Number);
            return dateObj.getFullYear() === year && dateObj.getMonth() + 1 === month;
        }


         // Apply filters to data
        function applyFilters() {
            const course = $('#course_name').val()?.toLowerCase();
            const section = $('#section').val()?.toLowerCase();
            const setGroup = $('#setGroup').val()?.toLowerCase();
            const month = $('#month').val();
            const week = $('#week').val();
            const teacher = $('#teacher').val()?.toLowerCase();
            const date = $('#attendance_date').val();
            const time = $('#attendance_time').val();

            const filtered = allAttendanceRecords.filter(record => {
                const recDate = new Date(record.attendance_date);
                return (!course || record.course_name.toLowerCase().includes(course)) &&
                    (!section || record.section.toLowerCase().includes(section)) &&
                    (!setGroup || record.set_group?.toLowerCase().includes(setGroup)) &&
                    (!month || isSameMonth(recDate, month)) &&
                    (!week || isSameWeek(recDate, week)) &&
                    (!teacher || record.teacher_name.toLowerCase().includes(teacher)) &&
                    (!date || record.attendance_date.includes(date)) &&
                    (!time || record.attendance_time.includes(time));
            });

            renderTable(filtered);
        }

        // Attach filter listeners
        $('#course_name, #section, #setGroup, #teacher, #attendance_date, #attendance_time, #month, #week').on('change', applyFilters);

        // Clear all filters
        $('#clearFilters').on('click', function () {
            $('#filterForm')[0].reset();
            $('.select2').val('').trigger('change');
            renderTable(allAttendanceRecords);
        });

        // Initialize date picker icons
        $('#monthPickerIcon').click(() => $('#month')[0]?.showPicker ? $('#month')[0].showPicker() : $('#month')[0].focus());
        $('#weekPickerIcon').click(() => $('#week')[0]?.showPicker ? $('#week')[0].showPicker() : $('#week')[0].focus());


        // Initial data load
        fetchAttendanceData();
    });
</script>



</body>
</html>

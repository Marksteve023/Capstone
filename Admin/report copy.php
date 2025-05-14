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

                    <div class="card p-4 rounded shadow-sm mt-4">

                        <h3 class="mb-3 text-center">Filter Attendance Records</h3>

                        <div class="row mb-3">
                            <div class="col-md-4 offset-md-2">
                                <label for="attendanceMonthFilter" class="form-label">Select Month</label>
                                <div class="input-group">
                                    <span class="input-group-text" id="MonthPickerIcon"><i class="bi bi-calendar3"></i></span>
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
 $(document).ready(() => {
    let allAttendanceRecords = [];
    let studentChart = { pie: null, bar: null };

    const formatToAmPm = (timeStr) => {
        if (!timeStr) return 'N/A';
        const [hourStr, minuteStr] = timeStr.split(':');
        let hour = parseInt(hourStr, 10);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        hour = hour % 12 || 12;
        return `${hour.toString().padStart(2, '0')}:${minuteStr} ${ampm}`;
    };

    const getStatusColor = (status) => {
        switch (status) {
            case 'Present': return 'primary';
            case 'Absent': return 'danger';
            case 'Late': return 'warning';
            case 'Excused': return 'info';
            default: return 'secondary';
        }
    };

    const fetchAttendanceData = () => {
        $.ajax({
            url: '../Admin/scripts/fetch_attendance_records.php',
            type: 'POST',
            dataType: 'json',
            success: (response) => {
                allAttendanceRecords = response;
                renderTable(allAttendanceRecords);
            },
            error: () => {
                $('#attendanceReport').html('<tr><td colspan="8" class="text-center text-danger">Failed to load data.</td></tr>');
            }
        });
    };

    const renderTable = (data) => {
        const $tbody = $('#attendanceReport').empty();
        if (!data.length) {
            $tbody.append('<tr><td colspan="8" class="text-center">No records found.</td></tr>');
            return;
        }

        $.each(data, (i, record) => {
            $tbody.append(`
                <tr>
                    <td>${i + 1}</td>
                    <td>${record.course_name}</td>
                    <td>${record.section}</td>
                    <td>${record.set_group}</td>
                    <td>${record.attendance_date}</td>
                    <td>${formatToAmPm(record.attendance_time)}</td>
                    <td>${record.teacher_name}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-info view-attendance-btn"
                            data-course-name="${record.course_name}"
                            data-section="${record.section}"
                            data-set-group="${record.set_group}"
                            data-date="${record.attendance_date}"
                            data-time="${record.attendance_time}">
                            <i class="bi bi-eye"></i> View
                        </button>
                    </td>
                </tr>
            `);
        });
    };

    $(document).on('click', '.view-attendance-btn', function () {
        const btn = $(this);
        viewReport(
            btn.data('course-name'),
            btn.data('section'),
            btn.data('set-group'),
            btn.data('date'),
            btn.data('time')
        );
    });

    const viewReport = (courseName, section, setGroup, date, time) => {
        $('#attendanceModalLabel').text(`${courseName} - ${section} - ${setGroup} - ${date} - ${formatToAmPm(time)}`);
        $('#attendanceModal').modal('show');

        $.ajax({
            url: '../Admin/scripts/fetch-attendance-report.php',
            type: 'GET',
            data: { course_name: courseName, section, set_group: setGroup, attendance_date: date, attendance_time: time },
            dataType: 'json',
            success: (data) => {
                const statusCount = { Present: 0, Absent: 0, Late: 0, Excused: 0 };
                if (!data.length) {
                    $('#attendanceDetailsBody').html('<tr><td colspan="9" class="text-center">No data found.</td></tr>');
                    drawAttendanceCharts(statusCount);
                    return;
                }

                const rows = data.map((entry, i) => {
                    statusCount[entry.status] += 1;
                    return `
                        <tr>
                            <td>${i + 1}</td>
                            <td>${entry.school_student_id}</td>
                            <td>${entry.student_name}</td>
                            <td>${entry.course_name}</td>
                            <td>${entry.section}</td>
                            <td>${entry.set_group}</td>
                            <td>${entry.timestamp ? formatToAmPm(entry.timestamp) : 'N/A'}</td>
                            <td><span class="badge bg-${getStatusColor(entry.status)}">${entry.status}</span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-info view-student-report"
                                    data-student-id="${entry.student_id}"
                                    data-school-id="${entry.school_student_id}"
                                    data-student-name="${entry.student_name}"
                                    data-course="${entry.course_name}"
                                    data-section="${entry.section}">
                                    <i class="bi bi-eye"></i> View Report
                                </button>
                            </td>
                        </tr>
                    `;
                }).join('');
                $('#attendanceDetailsBody').html(rows);

                drawAttendanceCharts(statusCount);
            }
        });
    };

    const drawAttendanceCharts = (statusCount) => {
        const labels = ['Present', 'Absent', 'Late', 'Excused'];
        const colors = ['#0d6efd', '#dc3545', '#ffc107', '#0dcaf0'];
        const dataArray = labels.map(label => statusCount[label]);

        if (window.pieChartInstance) window.pieChartInstance.destroy();
        if (window.barChartInstance) window.barChartInstance.destroy();

        window.pieChartInstance = new Chart($('#attendancePieChart'), {
            type: 'pie',
            data: { labels, datasets: [{ data: dataArray, backgroundColor: colors }] },
            options: { responsive: true }
        });

        window.barChartInstance = new Chart($('#attendanceBarChart'), {
            type: 'bar',
            data: { labels, datasets: [{ label: 'Count', data: dataArray, backgroundColor: colors }] },
            options: { responsive: true, scales: { y: { beginAtZero: true } } }
        });
    };

    $(document).on('click', '.view-student-report', function () {
        const btn = $(this);
        const studentId = btn.data('student-id');
        const studentName = btn.data('student-name');
        const course = btn.data('course');
        const section = btn.data('section');
        const schoolId = btn.data('school-id');

        $('#studentReportModalLabel').text(`${studentName} - ${schoolId} - ${course} - ${section}`);
        $('#studentReportModal').modal('show');

        $.ajax({
            url: '../Admin/scripts/fetch_student_report.php',
            method: 'GET',
            data: { student_id: studentId, course_name: course, section },
            dataType: 'json',
            success: function (data) {
                const tbody = $('#reportDetailsBody').empty();
                const statusCounts = { Present: 0, Late: 0, Absent: 0, Excused: 0 };

                if (!data.length) {
                    tbody.html('<tr><td colspan="8">No records found.</td></tr>');
                    updateStudentChart(statusCounts);
                    return;
                }

                data.forEach((row, i) => {
                    statusCounts[row.status] += 1;
                    tbody.append(`
                        <tr>
                            <td>${i + 1}</td>
                            <td>${row.course_name}</td>
                            <td>${row.section}</td>
                            <td>${row.set_group || 'N/A'}</td>
                            <td>${row.attendance_date}</td>
                            <td>${formatToAmPm(row.attendance_time)}</td>
                            <td>${row.timestamp ? formatToAmPm(row.timestamp) : 'N/A'}</td>
                            <td><span class="badge bg-${getStatusColor(row.status)}">${row.status}</span></td>
                        </tr>
                    `);
                });

                updateStudentChart(statusCounts);
            }
        });
    });

    function updateStudentChart(counts) {
        const labels = ['Present', 'Late', 'Absent', 'Excused'];
        const colors = ['#0d6efd', '#ffc107', '#dc3545', '#0dcaf0'];
        const dataArray = labels.map(label => counts[label]);

        if (studentChart.pie) studentChart.pie.destroy();
        if (studentChart.bar) studentChart.bar.destroy();

        studentChart.pie = new Chart($('#studentPieChart'), {
            type: 'pie',
            data: { labels, datasets: [{ data: dataArray, backgroundColor: colors }] },
            options: { responsive: true }
        });

        studentChart.bar = new Chart($('#studentBarChart'), {
            type: 'bar',
            data: { labels, datasets: [{ label: 'Count', data: dataArray, backgroundColor: colors }] },
            options: { responsive: true, scales: { y: { beginAtZero: true } } }
        });
    }

    // Initial fetch
    fetchAttendanceData();
});

</script>

</body>
</html>

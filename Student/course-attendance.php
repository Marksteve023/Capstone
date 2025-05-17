<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['student_id']) || empty($_SESSION['student_id'])) {
    header("Location: ../login.php");
    exit();
}

$student_id = intval($_SESSION['student_id']);
$studentFullName = $_SESSION['student_name'] ?? 'Student';

$courses = [];
$sql = "SELECT c.course_id, c.course_name, c.section, c.academic_year
        FROM student_courses sc
        INNER JOIN courses c ON sc.course_id = c.course_id
        WHERE sc.student_id = :student_id";

try {
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $stmt->execute();
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'head.php'; ?>
    <title>Course & Attendance - Smart Attendance Monitoring System</title>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <main class="main" id="main">
        <h1>Course & Attendance</h1>

        <div class="container mt-4">
            <div class="cards-container d-flex justify-content-center">
                <div class="row g-3">
                    <?php if (!empty($courses)): ?>
                        <?php foreach ($courses as $course): ?>
                            <div class="col-md-4 col-sm-6 mb-4">
                                <div class="card bg-primary text-white shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <h2 class="h5 mb-2">
                                            <strong><?= htmlspecialchars($course['course_name'] . ' - ' . $course['section']); ?></strong>
                                        </h2>
                                        <p class="mb-0">Academic Year: <?= htmlspecialchars($course['academic_year']); ?></p>
                                        <button 
                                            type="button" 
                                            class="stretched-link btn btn-transparent p-0 position-absolute top-0 start-0 w-100 h-100"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#AttendanceReportModal"
                                            data-course-id="<?= htmlspecialchars($course['course_id']) ?>"
                                            data-course-name="<?= htmlspecialchars($course['course_name']) ?>"
                                            data-section="<?= htmlspecialchars($course['section']) ?>"
                                            data-academic-year="<?= htmlspecialchars($course['academic_year']) ?>">
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12 text-center md-4">
                            <p class="text-muted">You have no enrolled courses.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="AttendanceReportModal" tabindex="-1" aria-labelledby="AttendanceModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header d-flex justify-content-center">
                        <h5 class="modal-title text-dark" id="studentReportModalLabel">Course Attendance History</h5>
                        <button type="button" class="btn-close position-absolute end-0 me-2" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="title-chart">
                            <h2 class="text-center text-dark" id="studentReportMainTitle">Student Attendance History</h2>
                            <h6 id="studentReportSubTitle" class="text-center"></h6>
                        </div>

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

                        <div class="card p-4 rounded shadow-sm mb-4">
                            <h2 class="mb-3 text-center fs-3">Filter Attendance Records</h2>

                            <div class="row mb-3">

                                <!-- Month Filter -->
                                <div class="col-md-4 offset-md-2">
                                    <label for="attendanceMonthFilter" class="form-label">Select Month</label>
                                    <div class="input-group">
                                        <span class="input-group-text" id="monthPickerIcon" style="cursor: pointer;">
                                            <i class="bi bi-calendar3"></i>
                                        </span>
                                        <input type="month" id="attendanceMonthFilter" class="form-control" />
                                    </div>
                                </div>

                                <!-- Status Filter -->
                                <div class="col-md-4">
                                    <label for="attendanceStatusFilter" class="form-label">Select Status</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-filter-circle"></i></span>
                                        <select id="attendanceStatusFilter" class="form-select">
                                            <option value="">All</option>
                                            <option value="Present">Present</option>
                                            <option value="Late">Late</option>
                                            <option value="Absent">Absent</option>
                                            <option value="Excused">Excused</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Clear Button -->
                            <div class="row">
                                <div class="col-md-4 offset-md-4">
                                    <button class="btn btn-outline-secondary w-100" id="clearFilters">
                                        <i class="bi bi-x-circle me-2"></i>Clear Filters
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive mt-4" id="attendanceTable">
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
                                <tbody id="reportDetailsBody"></tbody>
                            </table>
                        </div>

                        <div class="modal-footer">
                            <button class="btn btn-success mt-3" id="studentReportDownload">Download</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Scripts -->
    <script src="../assets/js/global.js"></script>
    <script src="../assets/js/popper.min.js"></script>
    <script src="../assets/js/bootstrap.min.js"></script>
    <script src="../assets/js/chart.min.js"></script>
    <script src="../assets/js/jquery.min.js"></script>

    <script>
    const studentFullName = <?= json_encode($studentFullName); ?>;
    let selectedCourseId = '', selectedCourseName = '', selectedSection = '';
    let studentChart = null;
    let fullAttendanceData = [];

    function formatTimeStringToAMPM(time) {
        const [hourStr, minuteStr] = time.split(':');
        let hour = parseInt(hourStr);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        hour = hour % 12 || 12;
        return `${hour}:${minuteStr.padStart(2, '0')} ${ampm}`;
    }

    function formatTimestampToAMPM(timestamp) {
        const date = new Date(timestamp);
        const hours = date.getHours();
        const minutes = date.getMinutes();
        const ampm = hours >= 12 ? 'PM' : 'AM';
        const hour12 = hours % 12 || 12;
        return `${hour12}:${minutes.toString().padStart(2, '0')} ${ampm}`;
    }

    function getStatusColor(status) {
        switch (status) {
            case 'Present': return 'primary';
            case 'Absent': return 'success';
            case 'Late': return 'danger';
            case 'Excused': return 'purple';
            default: return 'secondary';
        }
    }

    function updateStudentChart(counts) {
        const pieCanvas = document.getElementById('studentPieChart');
        const barCanvas = document.getElementById('studentBarChart');
        if (!pieCanvas || !barCanvas) return;

        const pieCtx = pieCanvas.getContext('2d');
        const barCtx = barCanvas.getContext('2d');

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
                        data: labels.map(label => counts[label] || 0),
                        backgroundColor: colors
                    }]
                },
                options: { responsive: true }
            }),
            bar: new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: 'Attendance Count',
                        data: labels.map(label => counts[label] || 0),
                        backgroundColor: colors
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            })
        };
    }

    function applyStudentReportFilters() {
        const selectedMonth = $('#attendanceMonthFilter').val();
        const selectedStatus = $('#attendanceStatusFilter').val().toLowerCase();
        const statusCounts = { 'present': 0, 'late': 0, 'absent': 0, 'excused': 0 };

        $('#reportDetailsBody').empty();

        let filtered = fullAttendanceData.filter(item => {
            const matchMonth = !selectedMonth || item.attendance_date.startsWith(selectedMonth);
            const matchStatus = !selectedStatus || item.status.toLowerCase() === selectedStatus;
            return matchMonth && matchStatus;
        });

        if (filtered.length === 0) {
            $('#reportDetailsBody').html('<tr><td colspan="8" class="text-center">No attendance records found.</td></tr>');
        } else {
            filtered.forEach((item, i) => {
                const status = item.status;
                statusCounts[status.toLowerCase()] = (statusCounts[status.toLowerCase()] || 0) + 1;

                $('#reportDetailsBody').append(`
                    <tr>
                        <td>${i + 1}</td>
                        <td>${item.course_name}</td>
                        <td>${item.section}</td>
                        <td>${item.set_group}</td>
                        <td>${item.attendance_date}</td>
                        <td>${item.attendance_time ? formatTimeStringToAMPM(item.attendance_time) : 'N/A'}</td>
                        <td>${item.timestamp ? formatTimestampToAMPM(item.timestamp) : 'N/A'}</td>
                        <td><span class="badge bg-${getStatusColor(status)}">${status}</span></td>
                    </tr>
                `);
            });
        }

        updateStudentChart({
            Present: statusCounts['present'],
            Late: statusCounts['late'],
            Absent: statusCounts['absent'],
            Excused: statusCounts['excused']
        });
    }

    function fetchAttendanceData() {
        $('#reportDetailsBody').empty();

        $.ajax({
            url: '../Student/scripts/fetch_attendance.php',
            method: 'POST',
            dataType: 'json',
            data: { course_id: selectedCourseId },
            success: function(response) {
                if (response.success) {
                    fullAttendanceData = response.data;
                    applyStudentReportFilters();
                } else {
                    $('#reportDetailsBody').html('<tr><td colspan="8" class="text-center">No attendance records found.</td></tr>');
                    updateStudentChart({ Present: 0, Late: 0, Absent: 0, Excused: 0 });
                }
            },
            error: function() {
                $('#reportDetailsBody').html('<tr><td colspan="8" class="text-center text-danger">Error fetching data.</td></tr>');
            }
        });
    }

    function setCourseData(courseId, courseName, section, academicYear) {
        selectedCourseId = courseId;
        selectedCourseName = courseName;
        selectedSection = section;

        $('#studentReportMainTitle').text(`${studentFullName} - Course Attendance History`);
        $('#studentReportSubTitle').text(`${courseName} - ${section} | AY: ${academicYear}`);

        $('#attendanceMonthFilter, #attendanceStatusFilter').val('');
        fetchAttendanceData();
    }

    $(document).ready(function () {
        $('[data-bs-target="#AttendanceReportModal"]').on('click', function () {
            const courseId = $(this).data('course-id');
            const courseName = $(this).data('course-name');
            const section = $(this).data('section');
            const academicYear = $(this).data('academic-year');
            setCourseData(courseId, courseName, section, academicYear);
        });

        $('#attendanceMonthFilter, #attendanceStatusFilter').on('change', applyStudentReportFilters);

        $('#clearFilters').on('click', function () {
            $('#attendanceMonthFilter, #attendanceStatusFilter').val('');
            applyStudentReportFilters();
        });

        $('#monthPickerIcon').click(() => $('#attendanceMonthFilter')[0]?.showPicker?.());

        $('#AttendanceReportModal').on('hidden.bs.modal', function () {
            $('#attendanceMonthFilter, #attendanceStatusFilter').val('');
            $('#reportDetailsBody').empty();
            if (studentChart?.pie) studentChart.pie.destroy();
            if (studentChart?.bar) studentChart.bar.destroy();
        });

        $('#studentReportDownload').on('click', function () {
            const month = $('#attendanceMonthFilter').val();
            const status = $('#attendanceStatusFilter').val(); // Get selected status
            const url = `../Student/scripts/download_student_report.php?course_id=${encodeURIComponent(selectedCourseId)}&course_name=${encodeURIComponent(selectedCourseName)}&section=${encodeURIComponent(selectedSection)}&attendance_month=${encodeURIComponent(month)}&status=${encodeURIComponent(status)}`;
            window.location.href = url;
        });

    });
    </script>
</body>
</html>

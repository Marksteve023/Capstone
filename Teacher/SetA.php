
<?php
  $students_per_course = [];

  foreach ($courses as $course) {
      $course_id = $course['course_id'];
  
      $sql = "SELECT sc.student_course_id, sc.student_id, s.school_student_id, s.student_name, 
                     c.course_name, c.section, c.academic_year, sc.set_group, sc.enrolled_at
              FROM student_courses sc
              INNER JOIN students s ON sc.student_id = s.student_id
              INNER JOIN courses c ON sc.course_id = c.course_id
              WHERE sc.course_id = :course_id AND sc.set_group = 'Set A'
              ORDER BY s.student_name ASC";
  
      $stmt = $conn->prepare($sql);
      $stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
      $stmt->execute();
      $students_per_course[$course_id] = $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
    

?>

<div class="table-responsive d-none" id="setATable">
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Student ID</th>
                <th>Student Name</th>   
                <th>Course</th>
                <th>Section</th>
                <th>Academic Year</th>
                <th>Set Group</th>
            </tr>
        </thead>

        <tbody id="setABody">
            <?php foreach ($students_per_course as $course_id => $students): ?>
                <?php foreach ($students as $index => $studentA): ?>
                    <tr data-course-id="<?php echo $course_id; ?>">
                        <td><?php echo htmlspecialchars($index + 1); ?></td>
                        <td><?php echo htmlspecialchars($studentA['school_student_id']); ?></td>
                        <td><?php echo htmlspecialchars($studentA['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($studentA['course_name']); ?></td>
                        <td><?php echo htmlspecialchars($studentA['section']); ?></td>
                        <td><?php echo htmlspecialchars($studentA['academic_year']); ?></td>
                        <td><?php echo htmlspecialchars($studentA['set_group']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
    <button class="btn btn-success mt-3" id="SetADownload">Download SET A</button>
</div>

<script>
        $(document).ready(function () {
        $('#SetADownload').click(function () {
            let courseId = document.getElementById('studentModalLabel').dataset.courseId;
            if (!courseId) {
                alert('No course selected.');
                return;
            }

        $.ajax({
            url: '../Teacher/scripts/download_setA.php',
            type: 'GET',
            data: { course_id: courseId },
            xhrFields: { responseType: 'blob' },
            success: function (data, status, xhr) {
                let filename = xhr.getResponseHeader('Content-Disposition').split('filename=')[1].replace(/"/g, '');
                let blob = new Blob([data], { type: xhr.getResponseHeader('Content-Type') });
                let link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            },
            error: function () {
                alert('Error downloading the masterlist.');
            }
            });
        });
    });
</script>
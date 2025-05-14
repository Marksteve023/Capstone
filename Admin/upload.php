<!DOCTYPE html>
<html>
<head>
    <title>Upload Excel File</title>
</head>
<body>
    <h2>Upload Enrolled Students Excel File</h2>
    <form action="../Admin/scripts/upload_enrollments.php" method="POST" enctype="multipart/form-data">
        <input type="file" name="file" accept=".xlsx, .xls" required>
        <button type="submit" name="submit">Upload</button>
    </form>
</body>
</html>

<div class="import-student-data" id="import-student-data">
                        <h2></h2>
                        <form action="upload_students_excel.php" method="post" enctype="multipart/form-data">

                           <div class="row g-3">
                                <div class="mb-3 col col-md-4">
                                    <label for="file">file</label>
                                    <input type="file" id="file" name="file" accept=".xlsx">
                                </div>
                    
                                <div class="mb-3 d-flex justify-content-center">
                                    <button type="submit" name="submit" class="btn btn-primary">Upload Excel</button>
                                </div>
                           </div>
                        </form>
                    </div>
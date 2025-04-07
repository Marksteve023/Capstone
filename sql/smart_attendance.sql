-- Users Table (Admin & Teachers)
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    school_id VARCHAR(50) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher') NOT NULL,
    picture VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Courses Table
CREATE TABLE courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    course_name VARCHAR(255) NOT NULL,
    section VARCHAR(50) NOT NULL,
    semester ENUM('1st Trimester', '2nd Trimester', '3rd Trimester') NOT NULL,
    academic_year VARCHAR(50) NOT NULL,
    full_course_name VARCHAR(255) GENERATED ALWAYS AS (CONCAT(course_name, ' ', section)) STORED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Assigned Courses Table (Links Teachers to Courses)
CREATE TABLE assigned_courses (
    assigned_course_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    section VARCHAR(50) NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE
);

-- Students Table
CREATE TABLE students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    school_student_id VARCHAR(50) UNIQUE NOT NULL,
    student_name VARCHAR(255) NOT NULL,
    rfid_tag VARCHAR(50) UNIQUE NOT NULL,
    program VARCHAR(100) NOT NULL,
    year_level INT NOT NULL CHECK (year_level BETWEEN 1 AND 4),
    password VARCHAR(255) NOT NULL, 
    picture VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Student Enrollment Table (Links Students to Courses) 
CREATE TABLE student_courses (
    student_course_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    section VARCHAR(50) NOT NULL,
    set_group ENUM('Set A', 'Set B') NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE
);

-- Attendance Tracking Table
CREATE TABLE attendance (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    rfid_tag VARCHAR(50) NOT NULL,
    course_id INT NOT NULL,
    status ENUM('Present', 'Late', 'Absent', 'Excused') NOT NULL,
    attendance_date DATE NOT NULL,
    attendance_time TIME NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    FOREIGN KEY (rfid_tag) REFERENCES students(rfid_tag) ON DELETE CASCADE
);

-- RFID Logs Table
CREATE TABLE rfid_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    rfid_tag VARCHAR(50) NOT NULL,
    scan_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rfid_tag) REFERENCES students(rfid_tag) ON DELETE CASCADE
);

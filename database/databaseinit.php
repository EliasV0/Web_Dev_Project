<?php
$servername = "localhost";
$username   = "root";
$password   = "";

// Connect to MySQL
$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create DB
$sql = "CREATE DATABASE IF NOT EXISTS mydb";
if ($conn->query($sql) === TRUE) {
    echo "Database 'mydb' created or already exists.<br>";
} else {
    die("Error creating DB: " . $conn->error);
}

// Select DB
$conn->select_db("mydb");

// Users table
$tableUsers = "CREATE TABLE IF NOT EXISTS users (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('S', 'P') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($tableUsers) === TRUE) {
    echo "Table 'users' created or already exists.<br>";
} else {
    echo "Error creating table 'users': " . $conn->error . "<br>";
}

// Courses table
$tableCourses = "CREATE TABLE IF NOT EXISTS courses (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    professor_id INT(6) UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (professor_id) REFERENCES users(id) ON DELETE CASCADE
)";
if ($conn->query($tableCourses) === TRUE) {
    echo "Table 'courses' created or already exists.<br>";
} else {
    echo "Error creating table 'courses': " . $conn->error . "<br>";
}

// Assignments table
$tableAssignments = "CREATE TABLE IF NOT EXISTS assignments (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT(6) UNSIGNED,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    due_date DATE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
)";
if ($conn->query($tableAssignments) === TRUE) {
    echo "Table 'assignments' created or already exists.<br>";
} else {
    echo "Error creating table 'assignments': " . $conn->error . "<br>";
}

// Submissions table
$tableSubmissions = "CREATE TABLE IF NOT EXISTS submissions (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT(6) UNSIGNED,
    student_id INT(6) UNSIGNED,
    submission_text TEXT, 
    file_path VARCHAR(255),
    grade DECIMAL(4,1) DEFAULT NULL,
    feedback TEXT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
)";
if ($conn->query($tableSubmissions) === TRUE) {
    echo "Table 'submissions' created or already exists.<br>";
} else {
    echo "Error creating table 'submissions': " . $conn->error . "<br>";
}

// Fix column types
$alter_grade_sql = "ALTER TABLE submissions MODIFY COLUMN grade DECIMAL(4,1) DEFAULT NULL";
if ($conn->query($alter_grade_sql) === TRUE) {
    echo "Column 'grade' in 'submissions' table updated to DECIMAL(4,1).<br>";
} else {
    echo "Warning: Could not alter 'grade' column. It might already be the correct type or another error occurred: " . $conn->error . "<br>";
}

// BLOB support check
$check_column_sql = "SHOW COLUMNS FROM submissions LIKE 'file_content'";
$result_check = $conn->query($check_column_sql);
if ($result_check && $result_check->num_rows == 0) {
    $alter_sql = "ALTER TABLE submissions 
                  DROP COLUMN file_path,
                  ADD COLUMN file_name VARCHAR(255) AFTER submission_text,
                  ADD COLUMN file_type VARCHAR(100) AFTER file_name,
                  ADD COLUMN file_content LONGBLOB AFTER file_type";
    
    if ($conn->query($alter_sql) === TRUE) {
        echo "Table 'submissions' altered successfully: Added BLOB support (file_content).<br>";
    } else {
        echo "Error altering table: " . $conn->error . "<br>";
    }
} else {
    echo "Table 'submissions' already has BLOB support.<br>";
}

echo "<h3>Database initialized successfully! You can now go to the Dashboard.</h3>";
$conn->close();
?>
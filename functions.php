<?php
require_once '../database/db_connect.php';

// Get all courses
function getAllCourses($conn) {
    $sql = "SELECT c.*, u.username as professor_name 
            FROM courses c 
            JOIN users u ON c.professor_id = u.id 
            ORDER BY c.created_at DESC";
    return $conn->query($sql);
}

// Get professor's courses
function getProfCourses($conn, $prof_id) {
    $stmt = $conn->prepare("SELECT * FROM courses WHERE professor_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $prof_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Get assignments for course
function getCourseAssignments($conn, $course_id) {
    $stmt = $conn->prepare("SELECT * FROM assignments WHERE course_id = ? ORDER BY due_date ASC");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Get student grades
function getStudentGrades($conn, $student_id) {
    $sql = "SELECT c.title as course_title, a.title as assign_title, s.grade, s.feedback, s.submitted_at
            FROM submissions s
            JOIN assignments a ON s.assignment_id = a.id
            JOIN courses c ON a.course_id = c.id
            WHERE s.student_id = ?
            ORDER BY s.submitted_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    return $stmt->get_result();
}

?>
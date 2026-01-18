<?php
session_start();
require_once '../database/db_connect.php';

// Check login
if (!isset($_SESSION['logged_in'])) {
    die("Access Denied");
}

if (isset($_GET['file_id'])) {
    $id = intval($_GET['file_id']);
    
    // Get file from DB
    $stmt = $conn->prepare("SELECT file_name, file_type, file_content FROM submissions WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        
        $file_name = $row['file_name'];
        $file_type = $row['file_type'];
        $file_content = $row['file_content'];
        
        // Set headers for download
        header("Content-Description: File Transfer");
        header("Content-Type: " . $file_type);
        header("Content-Disposition: attachment; filename=\"" . $file_name . "\"");
        header("Content-Transfer-Encoding: binary");
        header("Expires: 0");
        header("Cache-Control: must-revalidate");
        header("Pragma: public");
        header("Content-Length: " . strlen($file_content));
        
        // Clean buffer and send file
        ob_clean();
        flush();
        
        echo $file_content;
        exit;
    } else {
        die("File not found.");
    }
}
?>
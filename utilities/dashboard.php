<?php
session_start();
require_once '../functions/check_generic.php';
require_once '../functions/functions.php';
require_once '../database/db_connect.php';

// Ensure user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../sign-in-out-up/login.php");
    exit;
}

$user_id  = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role     = $_SESSION['role'];
$msg      = "";

// Course management (Professor only)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($role !== 'P') {
        die("Forbidden Action");
    }

    // Create course
    if (isset($_POST['action']) && $_POST['action'] === 'create_course') {
        $title = trim($_POST['title']);
        $desc  = trim($_POST['description']);
        if (!empty($title)) {
            $stmt = $conn->prepare("INSERT INTO courses (title, description, professor_id) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $title, $desc, $user_id);
            if ($stmt->execute()) $msg = "Course created successfully!";
            $stmt->close();
        }
    }
    // Edit course
    if (isset($_POST['action']) && $_POST['action'] === 'edit_course') {
        $c_id = intval($_POST['course_id']);
        $title = trim($_POST['title']);
        $desc = trim($_POST['description']);
        
        // Check owner
        $check = $conn->prepare("SELECT id FROM courses WHERE id=? AND professor_id=?");
        $check->bind_param("ii", $c_id, $user_id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE courses SET title=?, description=? WHERE id=?");
            $stmt->bind_param("ssi", $title, $desc, $c_id);
            if ($stmt->execute()) $msg = "Course updated successfully!";
            $stmt->close();
        }
        $check->close();
    }
    // Delete course
    if (isset($_POST['action']) && $_POST['action'] === 'delete_course') {
        $c_id = intval($_POST['course_id']);
        // Check owner
        $check = $conn->prepare("SELECT id FROM courses WHERE id=? AND professor_id=?");
        $check->bind_param("ii", $c_id, $user_id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $stmt = $conn->prepare("DELETE FROM courses WHERE id=?");
            $stmt->bind_param("i", $c_id);
            if ($stmt->execute()) $msg = "Course deleted successfully.";
            $stmt->close();
        }
        $check->close();
    }
}

$myCourses = [];
$allCourses = [];
$studentGrades = [];

// Fetch data for dashboard
if ($role === 'P') {
    // Prof courses
    $myCoursesResult = getProfCourses($conn, $user_id);
    if ($myCoursesResult) {
        $myCourses = $myCoursesResult->fetch_all(MYSQLI_ASSOC);
        $myCoursesResult->free();
    }
} 
elseif ($role === 'S') {
    // Student data
    $allCoursesResult = getAllCourses($conn);
    if ($allCoursesResult) {
        $allCourses = $allCoursesResult->fetch_all(MYSQLI_ASSOC);
        $allCoursesResult->free();
    }

    $gradesResult = getStudentGrades($conn, $user_id);
    if ($gradesResult) {
        $studentGrades = $gradesResult->fetch_all(MYSQLI_ASSOC);
        $gradesResult->free();
    }
}
?><!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | University Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css?v=46">
</head>
<body>

<header>
    <h1><a href="../index.php" class="home-link" style="color: white; text-decoration: none;">University Portal</a></h1>
    <div class="nav-links">
        <span style="color: white; font-weight: 500; margin-right: 15px;">
            <?php echo e($username); ?> <small style="opacity: 0.8;">(<?php echo ($role === 'P' ? 'Professor' : 'Student'); ?>)</small>
        </span>
        <a href="dashboard.php">Dashboard</a>
        <a href="../sign-in-out-up/logout.php">Logout</a>
    </div>
</header>

<div class="container py-5">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 style="color: #4a148c; font-weight: bold;">Dashboard</h2>
                <span class="text-muted"><?php echo date("d/m/Y"); ?></span>
            </div>

            <?php if($msg): ?>
                <div class="alert alert-success shadow-sm"><?php echo e($msg); ?></div>
            <?php endif; ?>

            <?php if ($role === 'P'): ?>
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4>My Courses</h4>
                        <button class="btn btn-custom" data-bs-toggle="modal" data-bs-target="#createCourseModal">
                            Create Course
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-custom">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($myCourses)): ?>
                                    <?php foreach($myCourses as $c): ?>
                                <tr>
                                    <td class="fw-bold"><?php echo e($c['title']); ?></td>
                                    <td><?php echo e($c['description']); ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#editCourseModal<?php echo $c['id']; ?>">
                                            Edit
                                        </button>
                                        
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete course?');">
                                            <input type="hidden" name="action" value="delete_course">
                                            <input type="hidden" name="course_id" value="<?php echo $c['id']; ?>">
                                            <button class="btn btn-sm btn-light border text-danger">
                                                Delete
                                            </button>
                                        </form>
                                        
                                        <a href="assignment.php?course_id=<?php echo $c['id']; ?>" class="btn btn-sm btn-custom ms-2">
                                            Assignments
                                        </a>
                                    </td>
                                </tr>

                                <div class="modal fade" id="editCourseModal<?php echo $c['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header"><h5>Edit Course</h5></div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="action" value="edit_course">
                                                    <input type="hidden" name="course_id" value="<?php echo $c['id']; ?>">
                                                    <div class="mb-2"><label>Title</label><input type="text" name="title" class="form-control" value="<?php echo e($c['title']); ?>" required></div>
                                                    <div class="mb-2"><label>Description</label><textarea name="description" class="form-control"><?php echo e($c['description']); ?></textarea></div>
                                                </div>
                                                <div class="modal-footer"><button type="submit" class="btn btn-primary">Save Changes</button></div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" class="text-center text-muted p-4">No courses yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal fade" id="createCourseModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST">
                                <div class="modal-header bg-light"><h5>Create New Course</h5></div>
                                <div class="modal-body">
                                    <input type="hidden" name="action" value="create_course">
                                    <div class="mb-3"><label>Title</label><input type="text" name="title" class="form-control" required></div>
                                    <div class="mb-3"><label>Description</label><textarea name="description" class="form-control"></textarea></div>
                                </div>
                                <div class="modal-footer"><button type="submit" class="btn btn-custom">Create</button></div>
                            </form>
                        </div>
                    </div>
                </div>

            <?php elseif ($role === 'S'): ?>
                <div class="row">
                    <div class="col-lg-7">
                        <div class="dashboard-card">
                            <h4>Available Courses</h4>
                            <div class="row">
                                <?php if(!empty($allCourses)): ?>
                                    <?php foreach($allCourses as $c): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100 border-0 shadow-sm bg-light">
                                        <div class="card-body">
                                            <h5 class="fw-bold text-primary"><?php echo e($c['title']); ?></h5>
                                            <small class="text-muted d-block mb-3">Professor: <?php echo e($c['professor_name']); ?></small>
                                            <a href="assignment.php?course_id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-primary w-100">View</a>
                                        </div>
                                    </div>
                                </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted">No courses available.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="dashboard-card">
                            <h4 style="color: #2e7d32;">Grades</h4>
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Course</th>
                                        <th class="text-end">Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(!empty($studentGrades)): ?>
                                        <?php foreach($studentGrades as $g): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?php echo e($g['course_title']); ?></div>
                                            <small class="text-muted"><?php echo e($g['assign_title']); ?></small>
                                        </td>
                                                                                    <td class="text-end">
                                                <?php if($g['grade'] === null): ?>
                                                    <span class="badge bg-warning text-dark rounded-pill fs-6">Pending</span>
                                                <?php else: ?>
                                                    <?php $grade_class = ($g['grade'] < 4.0) ? 'bg-danger' : 'bg-success'; ?>
                                                    <span class="badge <?php echo $grade_class; ?> rounded-pill fs-6">
                                                        <?php echo number_format($g['grade'], 1); ?> / 10
                                                    </span>
                                                <?php endif; ?>
                                            </td>                                    </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="2" class="text-center text-muted">No grades recorded.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
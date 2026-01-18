<?php
session_start();
require_once '../functions/check_generic.php';
require_once '../functions/functions.php';
require_once '../database/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in'])) {
    header("Location: ../sign-in-out-up/login.php");
    exit;
}

// Check if course ID is provided
if (!isset($_GET['course_id'])) {
    header("Location: dashboard.php");
    exit;
}

$course_id = intval($_GET['course_id']);
$user_id   = $_SESSION['user_id'];
$role      = $_SESSION['role'];
$msg       = "";

// Retrieve course details
$stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$course) {
    header("Location: dashboard.php");
    exit;
}

// Handle Professor Actions (Assignments CRUD)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Strict RBAC check: Only Professors can modify assignments
    if ($role !== 'P') {
        die("Forbidden Action");
    }
    // Check ownership: Professor must own the course
    if ($course['professor_id'] != $user_id) {
        die("Forbidden Action: You do not own this course.");
    }

    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $due = $_POST['due_date'] ?? '';

    // Create Assignment
    if (isset($_POST['action']) && $_POST['action'] === 'create_assign') {
        if(!empty($title) && !empty($due)){
            $stmt = $conn->prepare("INSERT INTO assignments (course_id, title, description, due_date) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $course_id, $title, $desc, $due);
            if($stmt->execute()) $msg = "Assignment posted successfully!";
            $stmt->close();
        }
    }
    // Edit Assignment
    if (isset($_POST['action']) && $_POST['action'] === 'edit_assign') {
        $aid = intval($_POST['assign_id']);
        if(!empty($title) && !empty($due)){
            $stmt = $conn->prepare("UPDATE assignments SET title=?, description=?, due_date=? WHERE id=?");
            $stmt->bind_param("sssi", $title, $desc, $due, $aid);
            if($stmt->execute()) $msg = "Assignment updated successfully!";
            $stmt->close();
        }
    }
    // Delete Assignment
    if (isset($_POST['action']) && $_POST['action'] === 'delete_assign') {
        $aid = intval($_POST['assign_id']);
        $stmt = $conn->prepare("DELETE FROM assignments WHERE id=?");
        $stmt->bind_param("i", $aid);
        if($stmt->execute()) $msg = "Assignment deleted successfully!";
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($course['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css?v=50">
</head>
<body>

<header>
    <h1><a href="../index.php" class="home-link" style="color: white; text-decoration: none;">University Portal</a></h1>
    <div class="nav-links">
        <span style="color: white; font-weight: 500; margin-right: 15px;">
            <?php echo e($_SESSION['username']); ?> <small style="opacity: 0.8;">(<?php echo ($_SESSION['role'] === 'P' ? 'Prof.' : 'Student'); ?>)</small>
        </span>
        <a href="dashboard.php">Dashboard</a>
        <a href="../sign-in-out-up/logout.php">Logout</a>
    </div>
</header>

<div class="container pb-5 my-4">
    <div class="mb-4">
        <a href="dashboard.php" class="btn-back">Back to Dashboard</a>
        <h1 class="fw-bold text-primary mt-2"><?php echo e($course['title']); ?></h1>
        <p class="text-muted lead"><?php echo e($course['description']); ?></p>
        <hr>
    </div>

    <?php if($msg) echo "<div class='alert alert-success shadow-sm'>" . e($msg) . "</div>"; ?>

    <div class="row g-3 g-lg-4">
        <div class="<?php echo ($role === 'P') ? 'col-lg-8' : 'col-lg-12'; ?>">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="text-primary fw-bold mb-0">Active Assignments</h4>
                <?php if($role === 'P'): ?>
                    <a href="#createAssignForm" class="btn btn-outline-primary btn-sm d-lg-none">
                        + New Assignment
                    </a>
                <?php endif; ?>
            </div>
            
            <div class="row">
            <?php 
            $assigns_result = getCourseAssignments($conn, $course_id);
            if ($assigns_result->num_rows > 0):
                while($row = $assigns_result->fetch_assoc()):
            ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="card-title fw-bold"><?php echo e($row['title']); ?></h5>
                                <h6 class="card-subtitle text-danger">
                                    Due: <?php echo date("M j", strtotime($row['due_date'])); ?>
                                </h6>
                            </div>
                            <?php if($role === 'P'): ?>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light border" type="button" data-bs-toggle="dropdown">
                                    ⋮
                                </button>
                                <ul class="dropdown-menu">
                                    <li><button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editAssignModal<?php echo $row['id']; ?>">Edit</button></li>
                                    <li>
                                        <form method="POST" onsubmit="return confirm('Delete this assignment?');">
                                            <input type="hidden" name="action" value="delete_assign">
                                            <input type="hidden" name="assign_id" value="<?php echo $row['id']; ?>">
                                            <button class="dropdown-item text-danger">Delete</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>
                        <p class="card-text text-muted flex-grow-1">
                            <?php 
                                $desc = e($row['description']);
                                // Cut text if it's too long
                                if (strlen($desc) > 100) {
                                    echo substr($desc, 0, 100) . "...";
                                } else {
                                    echo $desc;
                                }
                            ?>
                        </p>
                        <a href="submission.php?assign_id=<?php echo $row['id']; ?>" class="btn btn-primary mt-3 w-100">
                            <?php echo ($role === 'S') ? 'View / Submit' : 'View Submissions'; ?>
                        </a>
                    </div>
                </div>
            </div>

            <?php if($role === 'P'): ?>
            <div class="modal fade" id="editAssignModal<?php echo $row['id']; ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Assignment</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="action" value="edit_assign">
                                <input type="hidden" name="assign_id" value="<?php echo $row['id']; ?>">
                                <div class="mb-3"><label class="form-label">Title</label><input type="text" name="title" class="form-control" value="<?php echo e($row['title']); ?>" required></div>
                                <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="4"><?php echo e($row['description']); ?></textarea></div>
                                <div class="mb-3"><label class="form-label">Due Date</label><input type="date" name="due_date" class="form-control" value="<?php echo $row['due_date']; ?>" required></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php endwhile; else: ?>
                <div class="col-12"><div class="alert alert-secondary text-center">No active assignments at the moment.</div></div>
            <?php endif; ?>
            </div>
        </div>

        <?php if($role === 'P'): ?>
        <div class="col-lg-4" id="createAssignForm">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-body">
                    <h5 class="card-title text-primary mb-3">Create New Assignment</h5>
                    <form method="POST">
                        <input type="hidden" name="action" value="create_assign">
                        <div class="mb-3"><label class="form-label">Title</label><input type="text" name="title" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="4"></textarea></div>
                        <div class="mb-3"><label class="form-label">Due Date</label><input type="date" name="due_date" class="form-control" required></div>
                        <button type="submit" class="btn btn-primary w-100">Post Assignment</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
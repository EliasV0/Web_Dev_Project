<?php
session_start();
require_once '../functions/check_generic.php';
require_once '../functions/functions.php';
require_once '../database/db_connect.php';

// Check if user is logged in and assignment ID is present
if (!isset($_SESSION['logged_in'])) header("Location: ../sign-in-out-up/login.php");
if (!isset($_GET['assign_id'])) header("Location: dashboard.php");

$assign_id = intval($_GET['assign_id']);
$user_id   = $_SESSION['user_id'];
$role      = $_SESSION['role'];
$msg       = "";

// Check if assignment exists
$stmt = $conn->prepare("SELECT * FROM assignments WHERE id = ?");
$stmt->bind_param("i", $assign_id);
$stmt->execute();
    $assign = $stmt->get_result()->fetch_assoc();
    $stmt->close();

if (!$assign) {
    header("Location: dashboard.php");
    exit;
}

// Student Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_upload'])) {
    if ($role !== 'S') die("Forbidden Action");
    
    // Check file type
    $allowed_extensions = ['pdf', 'doc', 'docx', 'zip'];
    $max_size = 5 * 1024 * 1024; // 5MB
    $file_ext = strtolower(pathinfo($_FILES["file_upload"]["name"], PATHINFO_EXTENSION));

    if (!in_array($file_ext, $allowed_extensions)) {
        $msg = "Error: Disallowed file type. Only PDF, DOC, DOCX, ZIP files are allowed.";
    } 
    elseif ($_FILES['file_upload']['size'] > $max_size) {
        $msg = "Error: File is too large. Maximum size is 5MB.";
    } else {
        // Read file for DB blob
        $file_name = basename($_FILES["file_upload"]["name"]);
        $file_type = $_FILES['file_upload']['type']; 
        $file_content = file_get_contents($_FILES['file_upload']['tmp_name']);
        
        // Check for existing sub
        $check_stmt = $conn->prepare("SELECT id, grade FROM submissions WHERE assignment_id = ? AND student_id = ?");
        $check_stmt->bind_param("ii", $assign_id, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $existing_sub = $check_result->fetch_assoc();
        $check_stmt->close();

        if ($existing_sub) {
            // Confirmation check
            if ($existing_sub['grade'] !== null && !isset($_POST['confirm_overwrite'])) {
                $msg = "Error: Assignment is already graded. You must confirm overwrite to proceed.";
            } else {
                // Update and reset grade
                $stmt_update = $conn->prepare("UPDATE submissions SET file_name=?, file_type=?, file_content=?, submitted_at=NOW(), grade=NULL, feedback=NULL WHERE assignment_id=? AND student_id=?");
                $stmt_update->bind_param("sssii", $file_name, $file_type, $file_content, $assign_id, $user_id);
                
                if($stmt_update->execute()) $msg = "File updated successfully. Previous grade (if any) has been reset.";
                else $msg = "Error updating file: " . $conn->error;
                
                $stmt_update->close();
            }
        } else {
            // New sub
            $stmt_insert = $conn->prepare("INSERT INTO submissions (assignment_id, student_id, file_name, file_type, file_content) VALUES (?, ?, ?, ?, ?)");
            $stmt_insert->bind_param("iisss", $assign_id, $user_id, $file_name, $file_type, $file_content);
            
            if($stmt_insert->execute()) $msg = "File uploaded successfully.";
            else $msg = "Error uploading file: " . $conn->error;
            
            $stmt_insert->close();
        }
    }
}

// Professor Grading
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade_student'])) {
    if ($role !== 'P') die("Forbidden Action");

    $sub_id   = intval($_POST['sub_id']);
    $grade    = floatval($_POST['grade']);
    $feedback = trim($_POST['feedback']);

    // Update grade
    $stmt = $conn->prepare("UPDATE submissions SET grade=?, feedback=? WHERE id=?");
    $stmt->bind_param("dsi", $grade, $feedback, $sub_id);
    if($stmt->execute()) {
        $msg = "Grade saved successfully.";
    }
    $stmt->close();
}

// Get student's sub if it exists
$mySub = null;
if ($role === 'S') {
    $stmt = $conn->prepare("SELECT * FROM submissions WHERE assignment_id = ? AND student_id = ?");
    $stmt->bind_param("ii", $assign_id, $user_id);
    $stmt->execute();
    $mySub = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submission - University Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css?v=46">
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

<div class="container py-4">

    <div class="mb-3 d-flex justify-content-between align-items-center">
        <a href="assignment.php?course_id=<?php echo $assign['course_id']; ?>" class="btn-back">Back to Assignments</a>
        <?php if($msg): ?>
            <?php $alert_class = (strpos($msg, 'Error') !== false) ? 'alert-danger' : 'alert-success'; ?>
            <div class="alert <?php echo $alert_class; ?> py-2 px-3 mb-0 shadow-sm fw-bold small"><?php echo e($msg); ?></div>
        <?php endif; ?>
    </div>

    <div class="row g-4">
        <!-- Left Column: Assignment Details -->
        <div class="col-lg-5">
            <div class="card mb-3 shadow-sm h-100">
                <div class="card-header bg-light py-2">
                    <h5 class="mb-0 text-primary fw-bold fs-6"><?php echo e($assign['title']); ?></h5>
                </div>
                <div class="card-body p-3">
                    <h6 class="card-subtitle mb-2 text-muted small">Due Date: <?php echo date("F j, Y", strtotime($assign['due_date'])); ?></h6>
                    <div class="card-text text-muted" style="max-height: 250px; overflow-y: auto; font-size: 0.95rem; line-height: 1.5;">
                        <?php echo nl2br(e($assign['description'])); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Actions (Submission or Grading) -->
        <div class="col-lg-7">
            <?php if($role === 'S'): ?>
                <!-- Student's Submission View -->
                <div class="card shadow-sm h-100">
                    <div class="card-header py-2">
                        <h5 class="mb-0 fs-6">My Submission</h5>
                    </div>
                    <div class="card-body p-3">
                        <?php if($mySub): ?>
                            <ul class="list-group list-group-flush mb-3">
                                <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                    <strong>Status:</strong>
                                    <span class="badge bg-success">Submitted</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                    <strong>Date:</strong>
                                    <span class="small"><?php echo date("M j, g:i a", strtotime($mySub['submitted_at'])); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                    <strong>File:</strong>
                                    <a href="download.php?file_id=<?php echo $mySub['id']; ?>" class="btn btn-sm btn-outline-primary"><?php echo e($mySub['file_name']); ?></a>
                                </li>
                            </ul>

                            <div class="p-3 bg-light rounded mb-3 border">
                               <div class="d-flex align-items-start">
                                   <!-- Grade Section -->
                                   <div class="me-4 border-end pe-4" style="min-width: 100px;">
                                       <h6 class="fw-bold mb-1 text-dark">Grade</h6>
                                       <?php if($mySub['grade'] !== null): ?>
                                           <?php $grade_color = ($mySub['grade'] < 4.0) ? 'text-danger' : 'text-primary'; ?>
                                           <div class="d-flex align-items-baseline">
                                               <span class="fs-4 fw-bold <?php echo $grade_color; ?> me-1"><?php echo number_format($mySub['grade'], 1); ?></span>
                                               <span class="text-muted small">/ 10</span>
                                           </div>
                                       <?php else: ?>
                                           <p class="text-muted mb-0 small">Pending</p>
                                       <?php endif; ?>
                                   </div>
                                   
                                   <!-- Feedback Section -->
                                   <div class="flex-grow-1">
                                       <h6 class="fw-bold mb-1 text-dark">Feedback</h6>
                                       <?php if($mySub['grade'] !== null && !empty($mySub['feedback'])): ?>
                                           <p class="text-muted fst-italic mb-0 small" style="word-break: break-word;">"<?php echo e($mySub['feedback']); ?>"</p>
                                       <?php elseif($mySub['grade'] !== null): ?>
                                           <p class="text-muted mb-0 small fst-italic">No feedback provided.</p>
                                       <?php else: ?>
                                           <p class="text-muted mb-0 small">Your assignment has not been graded yet.</p>
                                       <?php endif; ?>
                                   </div>
                               </div>
                           </div>
                        <?php else: ?>
                            <div class="alert alert-warning text-center py-2 mb-3 small">No submission yet.</div>
                        <?php endif; ?>
                        
                        <div class="mt-2">
                            <h6 class="mb-2"><?php echo $mySub ? 'Update File' : 'Upload File'; ?></h6>
                            <form method="POST" enctype="multipart/form-data">
                                <?php if($mySub && $mySub['grade'] !== null): ?>
                                    <div class="alert alert-warning border-warning py-2 px-3 mb-2 small">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="confirm_overwrite" id="confirmOverwrite" required>
                                            <label class="form-check-label text-dark fw-bold" for="confirmOverwrite">
                                                Assignement has been graded. Resubmission will result in grade deletion.
                                            </label>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="input-group">
                                    <input type="file" name="file_upload" class="form-control" required>
                                    <button type="submit" class="btn btn-primary">Submit</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if($role === 'P'): ?>
                <!-- Professor's View -->
                <div class="card shadow-sm h-100">
                    <div class="card-header py-2">
                        <h5 class="mb-0 fs-6">Student Submissions</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3 py-2">Student</th>
                                        <th>File</th>
                                        <th class="text-center">Grade</th>
                                        <th class="text-end pe-3">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $conn->prepare("SELECT s.*, u.username FROM submissions s JOIN users u ON s.student_id=u.id WHERE s.assignment_id=? ORDER BY s.submitted_at DESC");
                                    $stmt->bind_param("i", $assign_id);
                                    $stmt->execute();
                                    $subs = $stmt->get_result();
                                    
                                    if ($subs->num_rows > 0):
                                        while($s = $subs->fetch_assoc()):
                                    ?>
                                    <tr>
                                        <td class="ps-3 fw-bold small"><?php echo e($s['username']); ?></td>
                                        <td>
                                            <a href="download.php?file_id=<?php echo $s['id']; ?>" class="btn btn-sm btn-link text-decoration-none small">Download</a>
                                        </td>
                                        <td class="text-center">
                                            <?php if($s['grade'] !== null): ?>
                                                <?php $grade_color = ($s['grade'] < 4.0) ? 'text-danger' : 'text-primary'; ?>
                                                <span class="fw-bold <?php echo $grade_color; ?> small"><?php echo number_format($s['grade'], 1); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted small">---</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-3">
                                            <button class="btn btn-sm btn-outline-secondary py-0 small" data-bs-toggle="modal" data-bs-target="#gradeModal<?php echo $s['id']; ?>">
                                                Grade
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; else: ?>
                                        <tr><td colspan="4" class="text-center text-muted py-4 small">No submissions found.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
// Modals for Professor View
if ($role === 'P' && isset($subs)):
    $subs->data_seek(0);
    while($s = $subs->fetch_assoc()):
?>
<div class="modal fade" id="gradeModal<?php echo $s['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header py-2">
                    <h6 class="modal-title">Grade: <?php echo e($s['username']); ?></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="grade_student" value="1">
                    <input type="hidden" name="sub_id" value="<?php echo $s['id']; ?>">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Grade (0-10)</label>
                        <input type="number" name="grade" min="0" max="10" step="0.1" class="form-control" value="<?php echo $s['grade']; ?>" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold small">Feedback</label>
                        <textarea name="feedback" class="form-control" rows="3"><?php echo e($s['feedback']); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer py-1">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary px-3">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endwhile; endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
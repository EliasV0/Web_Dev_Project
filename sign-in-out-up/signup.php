<?php
session_start();
require_once '../functions/check_generic.php'; 

$success_message = "";
$error_message = "";

// Initialize variables
$input_username = "";
$input_email = "";
$input_role = "";

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../database/db_connect.php';

    // Get and clean input
    $input_username    = $_POST['username'] ?? '';
    $input_email       = $_POST['email'] ?? '';
    $input_password    = $_POST['password'] ?? '';
    $input_role        = $_POST['role'] ?? '';
    $input_secret_code = trim($_POST['secret_code'] ?? '');

    // Validation checks
    if (!check_field($input_username, "/^[A-Za-z0-9]+$/", 3, 10)) {
        $error_message = "Invalid Username (3-10 chars, alphanumeric).";
    } 
    elseif (!check_field($input_password, "/^.+$/", 5, 20)) {
        $error_message = "Password must be 5-20 characters.";
    }
    elseif (!filter_var($input_email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid Email format.";
    }
    elseif (!in_array($input_role, ['S', 'P'])) {
        $error_message = "Invalid Role selected.";
    } 
    else {
        // Verify codes for student/prof
        $code_is_valid = false;
        if ($input_role === 'S' && $input_secret_code === 'STUD2025') $code_is_valid = true;
        if ($input_role === 'P' && $input_secret_code === 'PROF2025') $code_is_valid = true;

        if (!$code_is_valid) {
            $error_message = "Invalid Registration Code for the selected role.";
        } else {
            // Check duplicates
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->bind_param("ss", $input_username, $input_email);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows > 0) {
                $error_message = "Username or Email already exists.";
            } else {
                // Hash password and save to DB
                $hashed_pass = password_hash($input_password, PASSWORD_DEFAULT);
                $stmtInsert = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                $stmtInsert->bind_param("ssss", $input_username, $input_email, $hashed_pass, $input_role);

                if ($stmtInsert->execute()) {
                    $success_message = "Account created! You can now login.";
                    $input_username = $input_email = $input_role = ""; 
                } else {
                    $error_message = "Database Error: " . $conn->error;
                }
                $stmtInsert->close();
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - University Portal</title>
    <link rel="stylesheet" href="../style.css"> 
</head>
<body>

    <div class="auth-card">
        <h2>Create Account</h2>
        
        <?php if($error_message): ?>
            <div class="alert alert-error"><?php echo e($error_message); ?></div>
        <?php endif; ?>

        <?php if($success_message): ?>
            <div class="alert alert-success">
                <?php echo e($success_message); ?> <br>
                <a href="login.php" style="font-weight:bold;">Go to Login</a>
            </div>
        <?php else: ?>

        <form method="post">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="<?php echo e($input_username); ?>" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo e($input_email); ?>" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <div class="form-group">
                <label>Role</label>
                <select name="role" required>
                    <option value="">-- Select Role --</option>
                    <option value="S" <?php if($input_role === 'S') echo 'selected'; ?>>Student (Requires Code)</option>
                    <option value="P" <?php if($input_role === 'P') echo 'selected'; ?>>Professor (Requires Code)</option>
                </select>
            </div>

            <div class="form-group">
                <label style="color: #d9534f;">Registration Code</label>
                <input type="text" name="secret_code" placeholder="e.g. STUD2025" required>
                <span class="small-text">Required for security verification.</span>
            </div>

            <button type="submit">Sign Up</button>
        </form>

        <?php endif; ?>

        <div class="auth-footer">
            <p>Already have an account? <a href="login.php">Login here</a></p>
            <p><a href="../index.php">Back to Home</a></p>
        </div>
    </div>

</body>
</html>
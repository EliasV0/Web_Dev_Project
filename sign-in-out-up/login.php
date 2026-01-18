<?php
session_start();
require_once '../functions/check_generic.php';

// Go to dashboard if logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: ../utilities/dashboard.php");
    exit;
}

$error_message = "";

// Process login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../database/db_connect.php';
    
    // Clean input
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error_message = "Please fill in all fields.";
    } else {
        // Check user in DB
        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verify password
            if (password_verify($password, $user['password'])) {
                // Save to session
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['role']      = $user['role'];

                header("Location: ../utilities/dashboard.php");
                exit;
            }
        }
        
        // Generic error for security
        $error_message = "Invalid Email or Password.";
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - University Portal</title>
    <link rel="stylesheet" href="../style.css"> 
</head>
<body>

    <div class="auth-card">
        <h2>Login</h2>

        <?php if($error_message): ?>
            <div class="alert alert-error"><?php echo e($error_message); ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required placeholder="name@example.com">
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="Enter your password">
            </div>

            <button type="submit">Login</button>
        </form>

        <div class="auth-footer">
            <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
            <p><a href="../index.php">Back to Home</a></p>
        </div>
    </div>

</body>
</html>
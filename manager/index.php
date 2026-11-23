<?php
session_start();
require_once 'config/db.php';
//require_once 'includes/functions.php';


if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    $sql = "SELECT * FROM managers WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $manager = $result->fetch_assoc();
        if (password_verify($password, $manager['password_hash'])) {
            $_SESSION['manager_id'] = $manager['id'];
            $_SESSION['manager_role'] = $manager['role'];
            
            // Update last login
            $update_sql = "UPDATE managers SET last_login = NOW() WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $manager['id']);
            $update_stmt->execute();
            
            header("Location: dashboard.php");
            exit();
        }
    }
    $error = "Invalid username or password";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Login - Vending Machine System</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body class="login-page">
    <div class="login-container">
        <form method="POST" class="login-form">
            <h1>Manager Login</h1>
            
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" name="login">Login</button>
        </form>
    </div>
</body>
</html>
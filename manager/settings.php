<?php
require_once 'includes/header.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($_POST['action']) {
        case 'update_profile':
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $current_password = $_POST['current_password'];
            
            // Verify current password
            $stmt = $conn->prepare("SELECT password_hash FROM managers WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['manager_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $manager = $result->fetch_assoc();
            
            if (password_verify($current_password, $manager['password_hash'])) {
                $stmt = $conn->prepare("UPDATE managers SET username = ?, email = ? WHERE id = ?");
                $stmt->bind_param("ssi", $username, $email, $_SESSION['manager_id']);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Profile updated successfully!";
                } else {
                    $_SESSION['error'] = "Failed to update profile.";
                }
            } else {
                $_SESSION['error'] = "Current password is incorrect.";
            }
            break;
            
        case 'change_password':
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            if ($new_password !== $confirm_password) {
                $_SESSION['error'] = "New passwords do not match.";
                break;
            }
            
            // Verify current password
            $stmt = $conn->prepare("SELECT password_hash FROM managers WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['manager_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $manager = $result->fetch_assoc();
            
            if (password_verify($current_password, $manager['password_hash'])) {
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE managers SET password_hash = ? WHERE id = ?");
                $stmt->bind_param("si", $new_password_hash, $_SESSION['manager_id']);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Password changed successfully!";
                } else {
                    $_SESSION['error'] = "Failed to change password.";
                }
            } else {
                $_SESSION['error'] = "Current password is incorrect.";
            }
            break;
            
        case 'update_notifications':
            $low_stock = isset($_POST['low_stock']) ? 1 : 0;
            $new_orders = isset($_POST['new_orders']) ? 1 : 0;
            $maintenance = isset($_POST['maintenance']) ? 1 : 0;
            
            // Update notification preferences in manager settings
            $stmt = $conn->prepare("
                UPDATE manager_settings 
                SET 
                    notify_low_stock = ?,
                    notify_new_orders = ?,
                    notify_maintenance = ?
                WHERE manager_id = ?
            ");
            $stmt->bind_param("iiii", $low_stock, $new_orders, $maintenance, $_SESSION['manager_id']);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Notification preferences updated!";
            } else {
                $_SESSION['error'] = "Failed to update notification preferences.";
            }
            break;
    }
    
    // Redirect to prevent form resubmission
    header("Location: settings.php");
    exit();
}

// Get current manager data
$stmt = $conn->prepare("
    SELECT m.*, ms.notify_low_stock, ms.notify_new_orders, ms.notify_maintenance
    FROM managers m
    LEFT JOIN manager_settings ms ON m.id = ms.manager_id
    WHERE m.id = ?
");
$stmt->bind_param("i", $_SESSION['manager_id']);
$stmt->execute();
$manager = $stmt->get_result()->fetch_assoc();
?>

<div class="settings-container">
    <h1>Settings</h1>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php 
            echo $_SESSION['success']; 
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?php 
            echo $_SESSION['error']; 
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <!-- Profile Settings -->
    <div class="settings-card">
        <h2>Profile Settings</h2>
        <form action="settings.php" method="POST" class="settings-form">
            <input type="hidden" name="action" value="update_profile">
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($manager['username']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($manager['email']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="current_password_profile">Current Password</label>
                <input type="password" id="current_password_profile" name="current_password" required>
                <small>Required to save changes</small>
            </div>
            
            <button type="submit" class="btn-primary">Update Profile</button>
        </form>
    </div>

    <!-- Password Change -->
    <div class="settings-card">
        <h2>Change Password</h2>
        <form action="settings.php" method="POST" class="settings-form">
            <input type="hidden" name="action" value="change_password">
            
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit" class="btn-primary">Change Password</button>
        </form>
    </div>

    <!-- Notification Settings -->
    <div class="settings-card">
        <h2>Notification Preferences</h2>
        <form action="settings.php" method="POST" class="settings-form">
            <input type="hidden" name="action" value="update_notifications">
            
            <div class="checkbox-group">
                <label>
                    <input type="checkbox" name="low_stock" <?php echo $manager['notify_low_stock'] ? 'checked' : ''; ?>>
                    Low Stock Alerts
                </label>
                <small>Get notified when products are running low</small>
            </div>
            
            <div class="checkbox-group">
                <label>
                    <input type="checkbox" name="new_orders" <?php echo $manager['notify_new_orders'] ? 'checked' : ''; ?>>
                    New Orders
                </label>
                <small>Get notified for new transactions</small>
            </div>
            
            <div class="checkbox-group">
                <label>
                    <input type="checkbox" name="maintenance" <?php echo $manager['notify_maintenance'] ? 'checked' : ''; ?>>
                    Maintenance Alerts
                </label>
                <small>Get notified when machines need maintenance</small>
            </div>
            
            <button type="submit" class="btn-primary">Update Preferences</button>
        </form>
    </div>

    <!-- Session Information -->
    <div class="settings-card">
        <h2>Session Information</h2>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Last Login</span>
                <span class="info-value"><?php echo date('Y-m-d H:i', strtotime($manager['last_login'])); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Account Type</span>
                <span class="info-value"><?php echo ucfirst($manager['role']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Account Created</span>
                <span class="info-value"><?php echo date('Y-m-d', strtotime($manager['created_at'])); ?></span>
            </div>
        </div>
    </div>
</div>

<script>
// Password strength checker
document.getElementById('new_password').addEventListener('input', function(e) {
    const password = e.target.value;
    const strength = checkPasswordStrength(password);
    updatePasswordStrengthIndicator(strength);
});

function checkPasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]/)) strength++;
    if (password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[^a-zA-Z0-9]/)) strength++;
    
    return strength;
}

function updatePasswordStrengthIndicator(strength) {
    const indicator = document.createElement('div');
    indicator.className = 'password-strength';
    
    const strengthText = ['Very Weak', 'Weak', 'Medium', 'Strong', 'Very Strong'];
    const strengthClass = ['very-weak', 'weak', 'medium', 'strong', 'very-strong'];
    
    indicator.innerHTML = `
        <div class="strength-bar ${strengthClass[strength-1]}"></div>
        <span>${strengthText[strength-1]}</span>
    `;
    
    const existingIndicator = document.querySelector('.password-strength');
    if (existingIndicator) {
        existingIndicator.remove();
    }
    
    document.getElementById('new_password').parentNode.appendChild(indicator);
}

// Confirm password matcher
document.getElementById('confirm_password').addEventListener('input', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = e.target.value;
    
    if (newPassword === confirmPassword) {
        e.target.setCustomValidity('');
    } else {
        e.target.setCustomValidity('Passwords do not match');
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
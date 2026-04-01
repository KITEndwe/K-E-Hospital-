<?php
// Database connection
$host = 'localhost';
$dbname = 'ke_hospital';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Check current admin
$stmt = $pdo->prepare("SELECT * FROM admin WHERE email = ?");
$stmt->execute(['admin@kehospital.com']);
$admin = $stmt->fetch();

echo "<h2>Current Admin Record:</h2>";
echo "<pre>";
print_r($admin);
echo "</pre>";

if ($admin) {
    // Test password 'admin123'
    $test_password = 'admin123';
    
    echo "<h3>Password Test:</h3>";
    echo "Testing password: 'admin123'<br>";
    
    // Check if password is already hashed
    if (strpos($admin['password'], '$2') === 0) {
        echo "Password is already hashed.<br>";
        if (password_verify($test_password, $admin['password'])) {
            echo "✓ Password verification SUCCESSFUL!<br>";
        } else {
            echo "✗ Password verification FAILED. Need to update password.<br>";
            
            // Generate new hash
            $new_hash = password_hash($test_password, PASSWORD_BCRYPT);
            echo "New hash: " . $new_hash . "<br>";
            
            // Update password
            $update = $pdo->prepare("UPDATE admin SET password = ? WHERE admin_id = ?");
            if ($update->execute([$new_hash, $admin['admin_id']])) {
                echo "✓ Password updated successfully!<br>";
                echo "You can now login with: admin@kehospital.com / admin123<br>";
            } else {
                echo "✗ Failed to update password.<br>";
            }
        }
    } else {
        echo "Password is plain text: " . $admin['password'] . "<br>";
        
        // Generate new hash
        $new_hash = password_hash($test_password, PASSWORD_BCRYPT);
        echo "New hash: " . $new_hash . "<br>";
        
        // Update password
        $update = $pdo->prepare("UPDATE admin SET password = ? WHERE admin_id = ?");
        if ($update->execute([$new_hash, $admin['admin_id']])) {
            echo "✓ Password updated to hash successfully!<br>";
            echo "You can now login with: admin@kehospital.com / admin123<br>";
        } else {
            echo "✗ Failed to update password.<br>";
        }
    }
} else {
    echo "No admin found with email: admin@kehospital.com<br>";
    echo "Creating new admin...<br>";
    
    // Create new admin
    $new_hash = password_hash('admin123', PASSWORD_BCRYPT);
    $insert = $pdo->prepare("INSERT INTO admin (full_name, email, password, role, profile_image, phone) 
                              VALUES (?, ?, ?, ?, ?, ?)");
    if ($insert->execute(['Elijah Mwange', 'admin@kehospital.com', $new_hash, 'Super Admin', '/admin/assets/doctor_icon.svg', '+260 761016446'])) {
        echo "✓ New admin created successfully!<br>";
        echo "Login with: admin@kehospital.com / admin123<br>";
    } else {
        echo "✗ Failed to create admin.<br>";
    }
}
?>
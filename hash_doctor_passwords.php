<?php
// hash_doctor_passwords.php - Run this script once to hash all doctor passwords
$host = 'localhost';
$dbname = 'ke_hospital';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Doctor credentials mapping
    $doctors = [
        'doc1' => ['email' => 'doc1@kehospital.co.zm', 'password' => 'MwilaDoc@2024'],
        'doc2' => ['email' => 'doc2@kehospital.co.zm', 'password' => 'MutintaDoc@2024'],
        'doc3' => ['email' => 'doc3@kehospital.co.zm', 'password' => 'LuyandoDoc@2024'],
        'doc4' => ['email' => 'doc4@kehospital.co.zm', 'password' => 'ChrisDoc@2024'],
        'doc5' => ['email' => 'doc5@kehospital.co.zm', 'password' => 'ChipoDoc@2024'],
        'doc6' => ['email' => 'doc6@kehospital.co.zm', 'password' => 'KelvinDoc@2024'],
        'doc7' => ['email' => 'doc7@kehospital.co.zm', 'password' => 'PatrickDoc@2024'],
        'doc8' => ['email' => 'doc8@kehospital.co.zm', 'password' => 'LillianDoc@2024'],
        'doc9' => ['email' => 'doc9@kehospital.co.zm', 'password' => 'ThandDoc@2024'],
        'doc10' => ['email' => 'doc10@kehospital.co.zm', 'password' => 'JosephDoc@2024']
    ];
    
    echo "<h2>Updating Doctor Passwords</h2>";
    echo "<pre>";
    
    foreach ($doctors as $doctor_id => $credentials) {
        // Hash the password
        $hashed_password = password_hash($credentials['password'], PASSWORD_DEFAULT);
        
        // Update the database
        $stmt = $pdo->prepare("
            UPDATE doctors 
            SET email = ?, password = ? 
            WHERE doctor_id = ?
        ");
        
        $stmt->execute([
            $credentials['email'],
            $hashed_password,
            $doctor_id
        ]);
        
        // Verify the hash works
        if (password_verify($credentials['password'], $hashed_password)) {
            echo "✓ Doctor $doctor_id ({$credentials['email']}) - Password updated successfully<br>";
        } else {
            echo "✗ Doctor $doctor_id - Password verification failed!<br>";
        }
    }
    
    echo "<br><strong>All doctor passwords have been hashed successfully!</strong><br>";
    echo "<br><strong>Test Credentials:</strong><br>";
    foreach ($doctors as $doctor_id => $credentials) {
        echo "$doctor_id: {$credentials['email']} / {$credentials['password']}<br>";
    }
    
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
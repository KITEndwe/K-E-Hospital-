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
        'doc1' => ['email' => 'mwilabanda@kehospital.co.zm', 'password' => 'MwilaBanda@2024'],
        'doc2' => ['email' => 'mutintaphiri@kehospital.co.zm', 'password' => 'MutintaPhiri@2024'],
        'doc3' => ['email' => 'luyandozulu@kehospital.co.zm', 'password' => 'LuyandoZulu@2024'],
        'doc4' => ['email' => 'christophertembo@kehospital.co.zm', 'password' => 'Christopher@2024'],
        'doc5' => ['email' => 'chipomwansa@kehospital.co.zm', 'password' => 'ChipoMwansa@2024'],
        'doc6' => ['email' => 'kelvinmulenga@kehospital.co.zm', 'password' => 'KelvinMulenga@2024'],
        'doc7' => ['email' => 'patricktembo@kehospital.co.zm', 'password' => 'PatrickTembo@2024'],
        'doc8' => ['email' => 'lilianchanda@kehospital.co.zm', 'password' => 'LillianChanda@2024'],
        'doc9' => ['email' => 'thandiwekapasa@kehospital.co.zm', 'password' => 'Thandiwekapasa@2024'],
        'doc10' => ['email' => 'josephmwansa@kehospital.co.zm', 'password' => 'JosephMwansa@2024']
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
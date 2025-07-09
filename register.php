<?php
require 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Input sanitization
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirmPassword'] ?? '';

    // Server-side validation
    if ($name == '' || $email == '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6 || $password !== $confirm) {
        echo "<script>window.onload=function(){document.getElementById('regMsg').textContent='Registration failed. Please check your details.';document.getElementById('regMsg').className='text-pink-600 text-sm mt-5 text-center';}</script>";
        exit;
    }

    // Create table if not exists
    $conn->query("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(80) NOT NULL,
        email VARCHAR(120) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Check for duplicate email
    $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    if($stmt->num_rows > 0){
        echo "<script>window.onload=function(){document.getElementById('regMsg').textContent='Email already registered.';document.getElementById('regMsg').className='text-pink-600 text-sm mt-5 text-center';}</script>";
        $stmt->close();
        exit;
    }
    $stmt->close();

    // Hash password
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param('sss', $name, $email, $hash);
    if($stmt->execute()){
        echo "<script>window.onload=function(){document.getElementById('regMsg').textContent='Registration successful!';document.getElementById('regMsg').className='text-green-600 text-sm mt-5 text-center';document.getElementById('regForm').reset();}</script>";
    }else{
        echo "<script>window.onload=function(){document.getElementById('regMsg').textContent='Registration failed. Try again.';document.getElementById('regMsg').className='text-pink-600 text-sm mt-5 text-center';}</script>";
    }
    $stmt->close();
    $conn->close();
} else {
    header('Location: register.html');
    exit;
}
?>

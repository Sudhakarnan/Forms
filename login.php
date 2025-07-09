<?php
session_start();
include 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Server-side validation
    if (empty($email) || empty($password)) {
        die("Both fields are required.");
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format.");
    }

    // Look up user
    $stmt = $conn->prepare("SELECT id, password, name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($user_id, $hash, $name);
        $stmt->fetch();
        if (password_verify($password, $hash)) {
            // Success: set session and redirect (update as needed)
            $_SESSION['user_id'] = $user_id;
            $_SESSION['name'] = $name;
            echo "<script>alert('Login successful!'); window.location.href='dashboard.html';</script>";
            exit;
        } else {
            // Password wrong
            echo "<script>alert('Incorrect password.'); window.location.href='login.html';</script>";
            exit;
        }
    } else {
        // Email not found
        echo "<script>alert('No account with this email.'); window.location.href='login.html';</script>";
        exit;
    }
    $stmt->close();
}
$conn->close();
?>

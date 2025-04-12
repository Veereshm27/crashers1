<?php
// Start session
session_start();

// Database connection configuration
$db_host = "localhost";     // Usually 'localhost' for local development
$db_user = "root";          // Default username for XAMPP/WAMP
$db_pass = "";              // Default password for XAMPP/WAMP is empty
$db_name = "user_authentication";

// Create database connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Main request handling
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Determine if this is a login or registration based on form fields
    if (isset($_POST['email'])) {
        // Registration form (has email field)
        handleRegistration($conn);
    } else {
        // Login form (no email field)
        handleLogin($conn);
    }
} else {
    // If this page is accessed directly without form submission
    header("Location: index.php");
    exit();
}

// Function to handle login
function handleLogin($conn) {
    // Get form data
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    
    // Validate form data
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "Username and password are required";
        header("Location: index.php");
        exit();
    }
    
    // Check user in database
    $sql = "SELECT id, username, password FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password (using password_hash)
        if (password_verify($password, $user['password'])) {
            // Password is correct, create session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            // Redirect to dashboard or home page
            header("Location:homepage.html ");
            exit();
        } else {
            // Invalid password
            $_SESSION['error'] = "Invalid username or password";
            header("Location: index.php");
            exit();
        }
    } else {
        // User not found
        $_SESSION['error'] = "Invalid username or password";
        header("Location: index.php");
        exit();
    }
    
    $stmt->close();
}

// Function to handle registration
function handleRegistration($conn) {
    // Get form data
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    
    // Validate form data
    if (empty($username) || empty($email) || empty($password)) {
        $_SESSION['error'] = "All fields are required";
        header("Location: index.php");
        exit();
    }
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format";
        header("Location: index.php");
        exit();
    }
    
    // Check if username already exists
    $check_user = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($check_user);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['error'] = "Username already exists";
        header("Location: index.php");
        exit();
    }
    
    // Check if email already exists
    $check_email = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($check_email);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['error'] = "Email already exists";
        header("Location: index.php");
        exit();
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user into database
    $insert_user = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insert_user);
    $stmt->bind_param("sss", $username, $email, $hashed_password);
    
    if ($stmt->execute()) {
        // Registration successful
        $_SESSION['success'] = "Registration successful! You can now login.";
        header("Location: index.php");
        exit();
    } else {
        // Registration failed
        $_SESSION['error'] = "Registration failed: " . $conn->error;
        header("Location: index.php");
        exit();
    }
    
    $stmt->close();
}

// Close database connection
$conn->close();
?>
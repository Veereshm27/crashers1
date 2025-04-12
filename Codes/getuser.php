<?php
// Database connection parameters
$host = 'localhost';
$db = 'skill_assessment';
$user = 'root'; // Replace with your database username
$pass = ''; // Replace with your database password
$charset = 'utf8mb4';

// Set up DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// Set up options for PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Check if ID parameter is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'User ID is required']);
    exit;
}

$userId = intval($_GET['id']);

try {
    // Create a PDO instance
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Prepare and execute the query to get user data
    $stmt = $pdo->prepare('SELECT id, name, email, created_at FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    
    // Fetch user data
    $userData = $stmt->fetch();
    
    if (!$userData) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }
    
    // Get the user's experience level (based on their most recent assessment)
    $stmtLevel = $pdo->prepare('SELECT proficiency_level FROM assessment_results 
                              WHERE user_id = ? ORDER BY assessment_date DESC LIMIT 1');
    $stmtLevel->execute([$userId]);
    $levelData = $stmtLevel->fetch();
    
    if ($levelData) {
        $userData['experience'] = $levelData['proficiency_level'];
    } else {
        $userData['experience'] = 'beginner'; // Default if no assessments
    }
    
    // Return user data as JSON
    header('Content-Type: application/json');
    echo json_encode($userData);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
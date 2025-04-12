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

try {
    // Create a PDO instance
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Clear existing data (optional)
    $pdo->exec("DELETE FROM assessment_results");
    $pdo->exec("DELETE FROM users");
    
    // Insert sample users
    $users = [
        ['name' => 'Alex Johnson', 'email' => 'alex@example.com'],
        ['name' => 'Emma Smith', 'email' => 'emma@example.com'],
        ['name' => 'Michael Brown', 'email' => 'michael@example.com'],
        ['name' => 'Sophia Davis', 'email' => 'sophia@example.com'],
        ['name' => 'Oliver Wilson', 'email' => 'oliver@example.com']
    ];
    
    $userStmt = $pdo->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
    
    foreach ($users as $user) {
        $userStmt->execute([$user['name'], $user['email']]);
    }
    
    // Get the inserted user IDs
    $userIds = [];
    for ($i = 1; $i <= count($users); $i++) {
        $userIds[] = $i;
    }
    
    // Insert sample assessment results
    $assessmentStmt = $pdo->prepare("
        INSERT INTO assessment_results 
        (user_id, total_score, proficiency_level, sql_score, dbms_score, nosql_score, excel_score, assessment_date) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    // Sample proficiency levels
    $proficiencyLevels = ['beginner', 'intermediate', 'advanced', 'expert'];
    
    // Generate multiple assessments for each user with gradual improvement
    foreach ($userIds as $userId) {
        // Number of assessments for this user (2-5)
        $numAssessments = rand(2, 5);
        
        for ($i = 0; $i < $numAssessments; $i++) {
            // Assessment date - older assessments first
            $date = date('Y-m-d H:i:s', strtotime("-" . (($numAssessments - $i) * 30) . " days"));
            
            // Base scores for this assessment
            $baseScore = 40 + ($i * 10) + rand(-5, 5); // Gradual improvement
            
            // Individual scores
            $sqlScore = min(100, $baseScore + rand(-10, 10));
            $dbmsScore = min(100, $baseScore + rand(-10, 10));
            $nosqlScore = min(100, $baseScore + rand(-15, 5)); // NoSQL tends to be harder
            $excelScore = min(100, $baseScore + rand(0, 15)); // Excel tends to be easier
            
            // Total score (average of all)
            $totalScore = (int)round(($sqlScore + $dbmsScore + $nosqlScore + $excelScore) / 4);
            
            // Determine proficiency level based on total score
            $proficiencyIndex = 0;
            if ($totalScore >= 40 && $totalScore < 60) {
                $proficiencyIndex = 0; // beginner
            } else if ($totalScore >= 60 && $totalScore < 75) {
                $proficiencyIndex = 1; // intermediate
            } else if ($totalScore >= 75 && $totalScore < 90) {
                $proficiencyIndex = 2; // advanced
            } else if ($totalScore >= 90) {
                $proficiencyIndex = 3; // expert
            }
            $proficiency = $proficiencyLevels[$proficiencyIndex];
            
            // Insert the assessment
            $assessmentStmt->execute([
                $userId,
                $totalScore,
                $proficiency,
                $sqlScore,
                $dbmsScore,
                $nosqlScore,
                $excelScore,
                $date
            ]);
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo "Sample data has been inserted successfully!";
    
} catch (PDOException $e) {
    // Roll back transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error: " . $e->getMessage();
}
?>
<?php
// Test database connection and table creation
echo "Testing database connection...\n";

try {
    require_once __DIR__ . '/db.php';
    echo "✓ Database connection successful!\n";
    
    // Test if users table exists and has the right structure
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'users'");
    $stmt->execute();
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        echo "✓ Users table exists!\n";
        
        // Check if the security question columns exist
        $stmt = $pdo->prepare("DESCRIBE users");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $requiredColumns = ['security_a1_hash', 'security_a2_hash', 'security_a3_hash'];
        $missingColumns = [];
        
        foreach ($requiredColumns as $col) {
            if (!in_array($col, $columns)) {
                $missingColumns[] = $col;
            }
        }
        
        if (empty($missingColumns)) {
            echo "✓ All required security question columns exist!\n";
            echo "Database setup is complete and working correctly.\n";
            echo "\nYou can now:\n";
            echo "1. Register a new user at register.php\n";
            echo "2. Test the forgot password flow at forgot_password.php\n";
        } else {
            echo "✗ Missing columns: " . implode(', ', $missingColumns) . "\n";
        }
    } else {
        echo "✗ Users table does not exist!\n";
        echo "Please run the database setup.\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
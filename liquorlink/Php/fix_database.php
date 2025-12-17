<?php
// Database fix script to add missing columns
require_once __DIR__ . '/db.php';

try {
    echo "Checking and fixing database structure...\n";
    
    // Get current columns
    $stmt = $pdo->prepare("DESCRIBE users");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Current columns: " . implode(', ', $columns) . "\n";
    
    // Define missing columns that need to be added
    $missingColumns = [];
    $requiredColumns = [
        'security_a2_hash' => 'VARCHAR(255) NOT NULL AFTER security_a1_hash',
        'security_a3_hash' => 'VARCHAR(255) NOT NULL AFTER security_q2',
        'sex' => 'VARCHAR(20) NOT NULL AFTER address',
        'email' => 'VARCHAR(100) NOT NULL UNIQUE AFTER sex'
    ];
    
    // Check which columns are missing
    foreach ($requiredColumns as $col => $definition) {
        if (!in_array($col, $columns)) {
            $missingColumns[$col] = $definition;
        }
    }
    
    if (empty($missingColumns)) {
        echo "✓ All required columns exist!\n";
    } else {
        echo "Adding missing columns:\n";
        
        // Add each missing column
        foreach ($missingColumns as $col => $definition) {
            $sql = "ALTER TABLE users ADD COLUMN $col $definition";
            try {
                $pdo->exec($sql);
                echo "✓ Added column: $col\n";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                    echo "- Column $col already exists\n";
                } else {
                    echo "✗ Error adding $col: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    // Verify final structure
    echo "\nVerifying final table structure...\n";
    $stmt = $pdo->prepare("DESCRIBE users");
    $stmt->execute();
    $finalColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredColumns = ['security_a1_hash', 'security_a2_hash', 'security_a3_hash', 'sex', 'email'];
    $missingFinal = [];
    
    foreach ($requiredColumns as $col) {
        if (!in_array($col, $finalColumns)) {
            $missingFinal[] = $col;
        }
    }
    
    if (empty($missingFinal)) {
        echo "✓ All required columns are now present!\n";
        echo "Final columns: " . implode(', ', $finalColumns) . "\n";
        echo "\nDatabase fix completed successfully!\n";
        echo "You can now test user registration at register.php\n";
    } else {
        echo "✗ Still missing: " . implode(', ', $missingFinal) . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
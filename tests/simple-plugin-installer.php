<?php
/**
 * Simple plugin installer using direct database operations
 */

// Load our test bootstrap
require_once __DIR__ . '/bootstrap.php';

// Connect to the database
$dbConfig = [
    'host' => 'localhost',
    'port' => '3306',
    'database' => 'limesurvey_vendor_test',
    'username' => 'root',
    'password' => 'root',
];

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']}", 
        $dbConfig['username'], 
        $dbConfig['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database: {$dbConfig['database']}\n";
    
    // Check if plugin already exists
    $stmt = $pdo->prepare("SELECT * FROM lime_plugins WHERE name = :name");
    $stmt->execute(['name' => 'StructureImEx']);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        echo "Plugin already exists in database with ID: {$existing['id']}\n";
        echo "Active status: " . ($existing['active'] ? 'YES' : 'NO') . "\n";
        
        if (!$existing['active']) {
            // Activate it
            $stmt = $pdo->prepare("UPDATE lime_plugins SET active = 1 WHERE id = :id");
            $stmt->execute(['id' => $existing['id']]);
            echo "Plugin activated!\n";
        }
    } else {
        // Insert the plugin
        $stmt = $pdo->prepare("
            INSERT INTO lime_plugins (name, plugin_type, active, priority, version, load_error, load_error_message) 
            VALUES (:name, :type, :active, :priority, :version, :load_error, :load_error_message)
        ");
        
        $result = $stmt->execute([
            'name' => 'StructureImEx',
            'type' => 'user',
            'active' => 1,
            'priority' => 0,
            'version' => '1.0.0',
            'load_error' => 0,
            'load_error_message' => null
        ]);
        
        if ($result) {
            $pluginId = $pdo->lastInsertId();
            echo "Plugin installed successfully with ID: $pluginId\n";
        } else {
            echo "Failed to install plugin\n";
        }
    }
    
    // Verify installation
    echo "\n=== All plugins in database ===\n";
    $stmt = $pdo->query("SELECT * FROM lime_plugins ORDER BY id");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("ID: %d, Name: %s, Type: %s, Active: %s\n", 
            $row['id'], 
            $row['name'], 
            $row['plugin_type'],
            $row['active'] ? 'YES' : 'NO'
        );
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
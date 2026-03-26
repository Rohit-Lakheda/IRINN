<?php

/**
 * Database Connection Test Script
 * 
 * This script tests the database connection and displays the connection status.
 * Access it via: http://your-domain.com/dbtest.php
 */

// Bootstrap Laravel
require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Set content type to HTML
header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-top: 0;
        }
        .status {
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .info-item {
            margin: 8px 0;
            padding: 8px;
            background: #f8f9fa;
            border-left: 3px solid #007bff;
            padding-left: 15px;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            border: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔌 Database Connection Test</h1>
        
        <?php
        try {
            // Get database configuration
            $config = config('database');
            $defaultConnection = $config['default'];
            $connectionConfig = $config['connections'][$defaultConnection] ?? null;
            
            echo '<div class="info">';
            echo '<strong>Testing Connection:</strong> ' . strtoupper($defaultConnection) . '<br>';
            if ($connectionConfig) {
                echo '<strong>Host:</strong> ' . ($connectionConfig['host'] ?? 'N/A') . '<br>';
                echo '<strong>Database:</strong> ' . ($connectionConfig['database'] ?? 'N/A') . '<br>';
                echo '<strong>Username:</strong> ' . ($connectionConfig['username'] ?? 'N/A') . '<br>';
            }
            echo '</div>';
            
            // Test database connection
            $startTime = microtime(true);
            $pdo = \Illuminate\Support\Facades\DB::connection()->getPdo();
            $connectionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            // Get database version
            $version = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
            
            // Test a simple query
            $testQuery = \Illuminate\Support\Facades\DB::select('SELECT 1 as test');
            
            // Get connection name
            $connectionName = \Illuminate\Support\Facades\DB::connection()->getName();
            
            // Success message
            echo '<div class="status success">';
            echo '✅ <strong>SUCCESS!</strong> Database connection is working properly.';
            echo '</div>';
            
            echo '<div class="info">';
            echo '<h3>Connection Details:</h3>';
            echo '<div class="info-item"><strong>Connection Name:</strong> ' . htmlspecialchars($connectionName) . '</div>';
            echo '<div class="info-item"><strong>Database Version:</strong> ' . htmlspecialchars($version) . '</div>';
            echo '<div class="info-item"><strong>Connection Time:</strong> ' . $connectionTime . ' ms</div>';
            echo '<div class="info-item"><strong>PDO Driver:</strong> ' . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . '</div>';
            echo '<div class="info-item"><strong>Test Query:</strong> ' . ($testQuery ? 'Passed ✓' : 'Failed ✗') . '</div>';
            echo '</div>';
            
            // Try to get table count (if possible)
            try {
                if ($defaultConnection === 'mysql' || $defaultConnection === 'mariadb') {
                    $tableCount = \Illuminate\Support\Facades\DB::select("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = ?", [$connectionConfig['database']]);
                    echo '<div class="info-item"><strong>Tables in Database:</strong> ' . $tableCount[0]->count . '</div>';
                } elseif ($defaultConnection === 'pgsql') {
                    $tableCount = \Illuminate\Support\Facades\DB::select("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = 'public'");
                    echo '<div class="info-item"><strong>Tables in Database:</strong> ' . $tableCount[0]->count . '</div>';
                } elseif ($defaultConnection === 'sqlite') {
                    $tableCount = \Illuminate\Support\Facades\DB::select("SELECT COUNT(*) as count FROM sqlite_master WHERE type='table'");
                    echo '<div class="info-item"><strong>Tables in Database:</strong> ' . $tableCount[0]->count . '</div>';
                }
            } catch (Exception $e) {
                // Silently fail if we can't get table count
            }
            
        } catch (\Illuminate\Database\QueryException $e) {
            echo '<div class="status error">';
            echo '❌ <strong>CONNECTION FAILED!</strong><br>';
            echo 'Error: ' . htmlspecialchars($e->getMessage());
            echo '</div>';
            
            echo '<div class="info">';
            echo '<h3>Error Details:</h3>';
            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
            echo '</div>';
            
        } catch (\PDOException $e) {
            echo '<div class="status error">';
            echo '❌ <strong>PDO ERROR!</strong><br>';
            echo 'Error: ' . htmlspecialchars($e->getMessage());
            echo '</div>';
            
            echo '<div class="info">';
            echo '<h3>Error Details:</h3>';
            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
            echo '</div>';
            
        } catch (Exception $e) {
            echo '<div class="status error">';
            echo '❌ <strong>UNEXPECTED ERROR!</strong><br>';
            echo 'Error: ' . htmlspecialchars($e->getMessage());
            echo '</div>';
            
            echo '<div class="info">';
            echo '<h3>Error Details:</h3>';
            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
            echo '</div>';
        }
        ?>
        
        <div class="info" style="margin-top: 30px;">
            <strong>Note:</strong> This is a diagnostic script. Remove or secure this file in production.
        </div>
    </div>
</body>
</html>

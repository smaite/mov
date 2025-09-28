<?php
// Simple installation script for Sasto Hub
$step = $_GET['step'] ?? 1;
$success = false;
$error = '';

// Database configuration
$dbConfig = [
    'host' => 'localhost',
    'name' => 'sasto_hub',
    'user' => 'root',
    'pass' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 1) {
        // Step 1: Database configuration
        $dbConfig['host'] = $_POST['db_host'] ?? 'localhost';
        $dbConfig['name'] = $_POST['db_name'] ?? 'sasto_hub';
        $dbConfig['user'] = $_POST['db_user'] ?? 'root';
        $dbConfig['pass'] = $_POST['db_pass'] ?? '';
        
        // Test database connection
        try {
            $dsn = "mysql:host={$dbConfig['host']};charset=utf8mb4";
            $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create database if it doesn't exist
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbConfig['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbConfig['name']}`");
            
            // Update config file
            $configContent = file_get_contents('config/database.php');
            $configContent = str_replace("define('DB_HOST', 'localhost');", "define('DB_HOST', '{$dbConfig['host']}');", $configContent);
            $configContent = str_replace("define('DB_NAME', 'sasto_hub');", "define('DB_NAME', '{$dbConfig['name']}');", $configContent);
            $configContent = str_replace("define('DB_USER', 'root');", "define('DB_USER', '{$dbConfig['user']}');", $configContent);
            $configContent = str_replace("define('DB_PASS', '');", "define('DB_PASS', '{$dbConfig['pass']}');", $configContent);
            
            file_put_contents('config/database.php', $configContent);
            
            header('Location: install.php?step=2');
            exit();
            
        } catch (PDOException $e) {
            $error = 'Database connection failed: ' . $e->getMessage();
        }
    } elseif ($step == 2) {
        // Step 2: Import database schema
        try {
            require_once 'config/database.php';
            $database = new Database();
            $pdo = $database->getConnection();
            
            // Read and execute schema file
            $schema = file_get_contents('database/schema.sql');
            $pdo->exec($schema);
            
            header('Location: install.php?step=3');
            exit();
            
        } catch (Exception $e) {
            $error = 'Schema installation failed: ' . $e->getMessage();
        }
    } elseif ($step == 3) {
        // Step 3: Import demo data (optional)
        $importDemo = isset($_POST['import_demo']);
        
        try {
            if ($importDemo) {
                require_once 'config/database.php';
                $database = new Database();
                $pdo = $database->getConnection();
                
                // Read and execute demo data file
                $demoData = file_get_contents('database/demo_data.sql');
                $pdo->exec($demoData);
            }
            
            // Create uploads directories
            $uploadDirs = [
                'uploads',
                'uploads/products',
                'uploads/users',
                'uploads/vendors'
            ];
            
            foreach ($uploadDirs as $dir) {
                if (!file_exists($dir)) {
                    mkdir($dir, 0755, true);
                }
            }
            
            header('Location: install.php?step=4');
            exit();
            
        } catch (Exception $e) {
            $error = 'Demo data installation failed: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sasto Hub Installation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#ff6b35',
                        secondary: '#004643',
                        accent: '#f9bc60'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-primary mb-2">
                    <i class="fas fa-shopping-bag mr-2"></i>Sasto Hub
                </h1>
                <p class="text-gray-600">E-commerce Platform Installation</p>
            </div>

            <!-- Progress Bar -->
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold
                        <?php echo $step >= 1 ? 'bg-primary text-white' : 'bg-gray-300 text-gray-600'; ?>">1</div>
                    <span class="ml-2 text-sm">Database</span>
                </div>
                <div class="flex-1 h-1 mx-4 <?php echo $step > 1 ? 'bg-primary' : 'bg-gray-300'; ?>"></div>
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold
                        <?php echo $step >= 2 ? 'bg-primary text-white' : 'bg-gray-300 text-gray-600'; ?>">2</div>
                    <span class="ml-2 text-sm">Schema</span>
                </div>
                <div class="flex-1 h-1 mx-4 <?php echo $step > 2 ? 'bg-primary' : 'bg-gray-300'; ?>"></div>
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold
                        <?php echo $step >= 3 ? 'bg-primary text-white' : 'bg-gray-300 text-gray-600'; ?>">3</div>
                    <span class="ml-2 text-sm">Data</span>
                </div>
                <div class="flex-1 h-1 mx-4 <?php echo $step > 3 ? 'bg-primary' : 'bg-gray-300'; ?>"></div>
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold
                        <?php echo $step >= 4 ? 'bg-primary text-white' : 'bg-gray-300 text-gray-600'; ?>">4</div>
                    <span class="ml-2 text-sm">Complete</span>
                </div>
            </div>

            <!-- Main Content -->
            <div class="bg-white rounded-lg shadow-md p-8">
                <?php if ($error): ?>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                            <span class="font-semibold text-red-800">Error</span>
                        </div>
                        <p class="text-red-700 mt-1"><?php echo htmlspecialchars($error); ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($step == 1): ?>
                    <!-- Step 1: Database Configuration -->
                    <h2 class="text-2xl font-bold mb-4">Database Configuration</h2>
                    <p class="text-gray-600 mb-6">Configure your database connection settings.</p>
                    
                    <form method="POST">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Database Host</label>
                                <input type="text" name="db_host" value="<?php echo htmlspecialchars($dbConfig['host']); ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Database Name</label>
                                <input type="text" name="db_name" value="<?php echo htmlspecialchars($dbConfig['name']); ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Database Username</label>
                                <input type="text" name="db_user" value="<?php echo htmlspecialchars($dbConfig['user']); ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Database Password</label>
                                <input type="password" name="db_pass" value="<?php echo htmlspecialchars($dbConfig['pass']); ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                        </div>
                        
                        <button type="submit" class="w-full bg-primary text-white py-3 px-4 rounded-md hover:bg-opacity-90 mt-6">
                            Test Connection & Continue
                        </button>
                    </form>

                <?php elseif ($step == 2): ?>
                    <!-- Step 2: Import Schema -->
                    <h2 class="text-2xl font-bold mb-4">Import Database Schema</h2>
                    <p class="text-gray-600 mb-6">This will create all necessary tables for Sasto Hub.</p>
                    
                    <form method="POST">
                        <button type="submit" class="w-full bg-primary text-white py-3 px-4 rounded-md hover:bg-opacity-90">
                            Import Database Schema
                        </button>
                    </form>

                <?php elseif ($step == 3): ?>
                    <!-- Step 3: Import Demo Data -->
                    <h2 class="text-2xl font-bold mb-4">Import Demo Data</h2>
                    <p class="text-gray-600 mb-6">Optionally import demo data including sample products, users, and categories.</p>
                    
                    <form method="POST">
                        <div class="mb-6">
                            <label class="flex items-center">
                                <input type="checkbox" name="import_demo" checked class="mr-2">
                                <span>Import demo data (recommended for testing)</span>
                            </label>
                        </div>
                        
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                            <h3 class="font-semibold text-blue-800 mb-2">Demo Accounts:</h3>
                            <ul class="text-sm text-blue-700 space-y-1">
                                <li><strong>Admin:</strong> admin@sastohub.com / password</li>
                                <li><strong>Customer:</strong> customer@test.com / password</li>
                                <li><strong>Vendor:</strong> vendor@test.com / password</li>
                            </ul>
                        </div>
                        
                        <button type="submit" class="w-full bg-primary text-white py-3 px-4 rounded-md hover:bg-opacity-90">
                            Continue
                        </button>
                    </form>

                <?php elseif ($step == 4): ?>
                    <!-- Step 4: Installation Complete -->
                    <div class="text-center">
                        <div class="mb-6">
                            <i class="fas fa-check-circle text-6xl text-green-500 mb-4"></i>
                            <h2 class="text-2xl font-bold mb-2">Installation Complete!</h2>
                            <p class="text-gray-600">Sasto Hub has been successfully installed.</p>
                        </div>
                        
                        <div class="space-y-4">
                            <a href="index.php" 
                               class="block w-full bg-primary text-white py-3 px-4 rounded-md hover:bg-opacity-90 text-center">
                                Go to Homepage
                            </a>
                            <a href="?page=login" 
                               class="block w-full bg-secondary text-white py-3 px-4 rounded-md hover:bg-opacity-90 text-center">
                                Admin Login
                            </a>
                        </div>
                        
                        <div class="mt-8 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <p class="text-sm text-yellow-800">
                                <strong>Security Note:</strong> Please delete this install.php file after installation for security reasons.
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</body>
</html>

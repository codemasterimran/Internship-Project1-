<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Screen management
$screen = $_GET['screen'] ?? 'welcome';

// Web App Version Management
$currentWebAppVersion = '1.0.0';

// Load updates from JSON file
$updatesFile = 'updates.json';
$updatesData = file_exists($updatesFile) ? json_decode(file_get_contents($updatesFile), true) : [];
$latestWebAppVersion = !empty($updatesData['versions']) ? end($updatesData['versions'])['version'] : $currentWebAppVersion;

// Get the installed version from session or use default
$installedVersion = $_SESSION['installed_version'] ?? $currentWebAppVersion;

// Minimum requirements
$minRAM = 1; // GB
$minNodeVersion = '14.0.0';
$minDBVersion = '5.7.0';

// Database connection persistence
$dbConfig = [
    'host' => $_SESSION['db_host'] ?? 'localhost',
    'username' => $_SESSION['db_username'] ?? 'root',
    'password' => $_SESSION['db_password'] ?? '',
    'name' => $_SESSION['db_name'] ?? ''
];

// Handle update request
if (isset($_GET['action']) && $_GET['action'] === 'update' && $screen === 'welcome') {
    // Create backup directory if it doesn't exist
    if (!file_exists('backups')) {
        mkdir('backups', 0755, true);
    }
    
    // Create a unique backup directory for this update
    $backupDir = 'backups/' . date('Y-m-d_His');
    mkdir($backupDir, 0755);
    
    // Backup the current web app files (without zip)
    $webappBackupDir = $backupDir . '/webapp_files';
    mkdir($webappBackupDir, 0755);
    
    // Copy all PHP files to the backup directory
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator('.'),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($files as $name => $file) {
        if (!$file->isDir() && $file->getExtension() === 'php') {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen(dirname(__FILE__)) + 1);
            $destPath = $webappBackupDir . '/' . $relativePath;
            
            // Create directory structure if needed
            $destDir = dirname($destPath);
            if (!file_exists($destDir)) {
                mkdir($destDir, 0755, true);
            }
            
            copy($filePath, $destPath);
        }
    }
    
    // Create log file
    $logContent = "Update Log\n";
    $logContent .= "==========\n";
    $logContent .= "Date: " . date('Y-m-d H:i:s') . "\n";
    $logContent .= "Previous Version: " . $installedVersion . "\n";
    $logContent .= "New Version: " . $latestWebAppVersion . "\n";
    $logContent .= "Backup Location: " . $backupDir . "\n";
    $logContent .= "Backup Contents:\n";
    $logContent .= "- Webapp files (PHP)\n";
    
    // Backup SQL content if available
    if (isset($_SESSION['sql_content']) && !empty($_SESSION['sql_content'])) {
        $sqlBackupFile = $backupDir . '/database_backup.sql';
        file_put_contents($sqlBackupFile, $_SESSION['sql_content']);
        $logContent .= "- Database SQL dump\n";
    }
    
    // Write log file
    file_put_contents($backupDir . '/update.log', $logContent);
    
    // Update installed version in session
    $_SESSION['installed_version'] = $latestWebAppVersion;
    
    // Set success message
    $_SESSION['update_success'] = [
        'previous_version' => $installedVersion,
        'new_version' => $latestWebAppVersion,
        'backup_location' => $backupDir,
        'log_content' => $logContent
    ];
    
    // Redirect to prevent form resubmission
    header("Location: ?screen=welcome");
    exit();
}

// Function to get latest versions from APIs
function getLatestVersions() {
    $versions = [
        'node_latest' => '0.0.0',
        'db_latest' => '0.0.0',
        'webapp_latest' => '0.0.0'
    ];

    // Get latest Node.js version
    $nodeContext = stream_context_create(['http' => ['timeout' => 2]]);
    $nodeData = @file_get_contents('https://nodejs.org/dist/index.json', false, $nodeContext);
    if ($nodeData) {
        $nodeReleases = json_decode($nodeData, true);
        if ($nodeReleases && isset($nodeReleases[0]['version'])) {
            $versions['node_latest'] = ltrim($nodeReleases[0]['version'], 'v');
        }
    }

    // Get latest MySQL version
    $mysqlContext = stream_context_create(['http' => ['timeout' => 2]]);
    $mysqlData = @file_get_contents('https://api.github.com/repos/mysql/mysql-server/tags', false, $mysqlContext);
    if ($mysqlData) {
        $mysqlTags = json_decode($mysqlData, true);
        if ($mysqlTags && isset($mysqlTags[0]['name'])) {
            $versions['db_latest'] = ltrim($mysqlTags[0]['name'], 'mysql-');
        }
    }

    // Get latest webapp version from updates.json
    global $latestWebAppVersion;
    $versions['webapp_latest'] = $latestWebAppVersion;
    
    return $versions;
}

function detectSystemInfo() {
    $systemInfo = [
        'ram' => 0,
        'node_version' => '0.0.0',
        'db_version' => '0.0.0',
        'db_service' => false,
        'detection_errors' => []
    ];

    // Improved RAM detection with multiple methods
    try {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows RAM detection
            $output = @shell_exec('wmic memorychip get capacity');
            if ($output && preg_match_all('/\d+/', $output, $matches)) {
                $systemInfo['ram'] = round(array_sum($matches[0]) / (1024 * 1024 * 1024), 1);
            } else {
                // Fallback for Windows if wmic fails
                $output = @shell_exec('systeminfo | find "Total Physical Memory"');
                if ($output && preg_match('/\d+/', $output, $matches)) {
                    $systemInfo['ram'] = round($matches[0] / 1024, 1);
                }
            }
        } else {
            // Linux/macOS RAM detection
            $output = @shell_exec('free -g | grep Mem');
            if ($output && preg_match('/Mem:\s+(\d+)/', $output, $matches)) {
                $systemInfo['ram'] = (int)$matches[1];
            } else {
                // Alternative Linux method
                $output = @shell_exec('grep MemTotal /proc/meminfo');
                if ($output && preg_match('/\d+/', $output, $matches)) {
                    $systemInfo['ram'] = round($matches[0] / (1024 * 1024), 1);
                }
            }
        }
    } catch (Exception $e) {
        $systemInfo['detection_errors'][] = "RAM detection failed: " . $e->getMessage();
    }

    // Node.js detection with multiple fallbacks
    try {
        // Try direct node command
        $output = @shell_exec('node -v');
        if ($output && preg_match('/v?(\d+\.\d+\.\d+)/', $output, $matches)) {
            $systemInfo['node_version'] = $matches[1];
        } else {
            // Try with full path (common locations)
            $paths = [
                '/usr/local/bin/node',
                '/usr/bin/node',
                'C:\\Program Files\\nodejs\\node.exe'
            ];
            
            foreach ($paths as $path) {
                if (file_exists($path)) {
                    $output = @shell_exec('"' . $path . '" -v');
                    if ($output && preg_match('/v?(\d+\.\d+\.\d+)/', $output, $matches)) {
                        $systemInfo['node_version'] = $matches[1];
                        break;
                    }
                }
            }
            
            // If still not found, try which/where
            if ($systemInfo['node_version'] === '0.0.0') {
                $cmd = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'where node' : 'which node';
                $nodePath = @shell_exec($cmd);
                if ($nodePath) {
                    $output = @shell_exec(trim($nodePath) . ' -v');
                    if ($output && preg_match('/v?(\d+\.\d+\.\d+)/', $output, $matches)) {
                        $systemInfo['node_version'] = $matches[1];
                    }
                }
            }
        }
    } catch (Exception $e) {
        $systemInfo['detection_errors'][] = "Node.js detection failed: " . $e->getMessage();
    }

    // MySQL detection with improved error handling
    try {
        // First try standard MySQL connection
        $conn = @new mysqli('localhost', 'root', '');
        
        if (!$conn->connect_error) {
            $systemInfo['db_service'] = true;
            $result = $conn->query("SELECT VERSION()");
            if ($result && $row = $result->fetch_array()) {
                if (preg_match('/\d+\.\d+\.\d+/', $row[0], $matches)) {
                    $systemInfo['db_version'] = $matches[0];
                }
            }
            $conn->close();
        } else {
            // If standard connection fails, try detecting version via command line
            $output = @shell_exec('mysql --version');
            if ($output && preg_match('/\d+\.\d+\.\d+/', $output, $matches)) {
                $systemInfo['db_version'] = $matches[0];
                $systemInfo['db_service'] = true; // Assume service is running if we got version
            } else {
                // Try common MySQL paths
                $paths = [
                    '/usr/local/mysql/bin/mysql',
                    '/usr/bin/mysql',
                    'C:\\xampp\\mysql\\bin\\mysql.exe'
                ];
                
                foreach ($paths as $path) {
                    if (file_exists($path)) {
                        $output = @shell_exec('"' . $path . '" --version');
                        if ($output && preg_match('/\d+\.\d+\.\d+/', $output, $matches)) {
                            $systemInfo['db_version'] = $matches[0];
                            $systemInfo['db_service'] = true;
                            break;
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {
        $systemInfo['detection_errors'][] = "MySQL detection failed: " . $e->getMessage();
    }

    return $systemInfo;
}

// Function to backup database
function backupDatabase($conn, $database) {
    $backup = [
        'tables' => [],
        'data' => [],
        'timestamp' => date('Y-m-d H:i:s')
    ];

    // Get all tables
    $conn->select_db($database);
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_row()) {
        $table = $row[0];
        $backup['tables'][] = $table;

        // Backup table structure
        $createResult = $conn->query("SHOW CREATE TABLE `$table`");
        if ($createResult) {
            $createRow = $createResult->fetch_row();
            $backup['data'][$table]['structure'] = $createRow[1];
        }

        // Backup table data
        $dataResult = $conn->query("SELECT * FROM `$table`");
        $backup['data'][$table]['rows'] = [];
        while ($dataRow = $dataResult->fetch_assoc()) {
            $backup['data'][$table]['rows'][] = $dataRow;
        }
    }

    return $backup;
}

// Function to restore database from backup
function restoreDatabase($conn, $database, $backup) {
    // Create database if it doesn't exist
    $conn->query("CREATE DATABASE IF NOT EXISTS `$database`");
    $conn->select_db($database);

    // Drop all existing tables first
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_row()) {
        $conn->query("DROP TABLE `{$row[0]}`");
    }

    // Restore tables and data
    foreach ($backup['tables'] as $table) {
        if (isset($backup['data'][$table])) {
            // Create table
            $conn->query($backup['data'][$table]['structure']);

            // Insert data if exists
            if (!empty($backup['data'][$table]['rows'])) {
                $columns = array_keys($backup['data'][$table]['rows'][0]);
                $columnsStr = implode('`, `', $columns);
                
                foreach ($backup['data'][$table]['rows'] as $row) {
                    $values = array_map(function($value) use ($conn) {
                        return "'" . $conn->real_escape_string($value) . "'";
                    }, array_values($row));
                    $valuesStr = implode(', ', $values);
                    
                    $conn->query("INSERT INTO `$table` (`$columnsStr`) VALUES ($valuesStr)");
                }
            }
        }
    }
}

// Initialize variables
$ram = $nodeVersion = $dbVersion = '';
$submitted = $requirementsMet = false;
$connectionError = $uploadError = $successMessage = '';
$sqlContent = $_SESSION['sql_content'] ?? '';
$tables = [];
$dbName = $_SESSION['db_name'] ?? '';

// Get latest versions
$latestVersions = getLatestVersions();

// Check for web app version update - THIS IS THE CRUCIAL FIX
$webAppUpdateAvailable = version_compare($_SESSION['installed_version'] ?? $currentWebAppVersion, $latestVersions['webapp_latest'], '<');
$webAppVersionStatus = '';
if ($webAppUpdateAvailable) {
    $webAppVersionStatus = 'outdated';
} elseif (version_compare($_SESSION['installed_version'] ?? $currentWebAppVersion, $latestVersions['webapp_latest'], '>')) {
    $webAppVersionStatus = 'development';
} else {
    $webAppVersionStatus = 'latest';
}
// Handle requirements check
if ($screen === 'auto-check') {
    $detectedInfo = detectSystemInfo();
    $ram = $detectedInfo['ram'];
    $nodeVersion = $detectedInfo['node_version'];
    $dbVersion = $detectedInfo['db_version'];
    $dbService = $detectedInfo['db_service'];
    $submitted = true;
    
    $requirementsMet = ($ram >= $minRAM) && 
                      (version_compare($nodeVersion, $minNodeVersion, '>=')) &&
                      (version_compare($dbVersion, $minDBVersion, '>=')) &&
                      $dbService;
}

// Handle manual entry submission
if ($screen === 'manual-entry' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $ram = $_POST['ram'] ?? '';
    $nodeVersion = $_POST['node_version'] ?? '';
    $dbVersion = $_POST['db_version'] ?? '';
    $submitted = true;
    
    $requirementsMet = ($ram >= $minRAM) && 
                      (version_compare($nodeVersion, $minNodeVersion, '>=')) &&
                      (version_compare($dbVersion, $minDBVersion, '>='));
}

// Handle SQL upload and execution
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['upload_sql'])) {
        $fixedSqlPath = 'D:/xamp/htdocs/uploads/test.sql';
        if (file_exists($fixedSqlPath)) {
            $_SESSION['sql_content'] = file_get_contents($fixedSqlPath);
            $_SESSION['db_name'] = $_POST['db_name'] ?? '';
            $_SESSION['db_host'] = $_POST['db_host'] ?? 'localhost';
            $_SESSION['db_username'] = $_POST['db_username'] ?? 'root';
            $_SESSION['db_password'] = $_POST['db_password'] ?? '';
            header("Location: ?screen=sql_editor");
            exit();
        } else {
            $uploadError = 'SQL file not found at '.$fixedSqlPath;
        }
    }
    
    if (isset($_POST['execute_sql'])) {
        try {
            $host = $_POST['db_host'] ?? $_SESSION['db_host'] ?? 'localhost';
            $username = $_POST['db_username'] ?? $_SESSION['db_username'] ?? 'root';
            $password = $_POST['db_password'] ?? $_SESSION['db_password'] ?? '';
            $database = $_POST['db_name'] ?? $_SESSION['db_name'] ?? '';
            
            if (empty($database)) {
                throw new Exception("Database name cannot be empty");
            }
            
            $conn = new mysqli($host, $username, $password);
            
            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }
            
            // Create database if it doesn't exist
            $conn->query("CREATE DATABASE IF NOT EXISTS `$database`");
            $conn->select_db($database);
            
            // Backup existing database before executing new SQL
            $backup = backupDatabase($conn, $database);
            $_SESSION['db_backup'] = $backup;
            
            // Execute SQL from file
            $sqlContent = $_POST['sql_content'] ?? '';
            $queries = explode(';', $sqlContent);
            
            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query)) {
                    $conn->query($query);
                }
            }
            
            // Get tables from database after execution
            $result = $conn->query("SHOW TABLES");
            $tables = [];
            while ($row = $result->fetch_array()) {
                $tables[] = $row[0];
            }
            
            $successMessage = "SQL executed successfully in database '$database'! Found " . count($tables) . " tables.";
            $successMessage .= " (Previous state backed up at " . $backup['timestamp'] . ")";
            
            $_SESSION['tables'] = $tables;
            $_SESSION['current_db'] = $database;
            
        } catch (Exception $e) {
            $connectionError = $e->getMessage();
        }
    }
    
    // Handle restore from backup
    if (isset($_POST['restore_backup'])) {
        try {
            if (!isset($_SESSION['db_backup'])) {
                throw new Exception("No backup available to restore");
            }
            
            $host = $_POST['db_host'] ?? $_SESSION['db_host'] ?? 'localhost';
            $username = $_POST['db_username'] ?? $_SESSION['db_username'] ?? 'root';
            $password = $_POST['db_password'] ?? $_SESSION['db_password'] ?? '';
            $database = $_POST['db_name'] ?? $_SESSION['db_name'] ?? '';
            
            $conn = new mysqli($host, $username, $password);
            restoreDatabase($conn, $database, $_SESSION['db_backup']);
            
            $successMessage = "Database successfully restored from backup (" . $_SESSION['db_backup']['timestamp'] . ")";
            
            // Update tables list after restore
            $conn->select_db($database);
            $result = $conn->query("SHOW TABLES");
            $tables = [];
            while ($row = $result->fetch_array()) {
                $tables[] = $row[0];
            }
            $_SESSION['tables'] = $tables;
            
        } catch (Exception $e) {
            $connectionError = $e->getMessage();
        }
    }
    
    // Handle download SQL
    if (isset($_POST['save_sql'])) {
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="database_backup.sql"');
        echo $_POST['sql_content'];
        exit();
    }
}

// Images
$randomInstallerImage = 'https://cdn-icons-png.flaticon.com/512/2092/2092693.png';
$randomDatabaseImage = 'https://cdn-icons-png.flaticon.com/512/4299/4299956.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup Wizard v<?= htmlspecialchars($currentWebAppVersion) ?></title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="bg-animation">
        <div class="bg-circle bg-circle-1"></div>
        <div class="bg-circle bg-circle-2"></div>
        <div class="bg-circle bg-circle-3"></div>
    </div>

    <div class="app-container">
        <?php if (isset($_SESSION['update_success'])): ?>
        <div class="update-notification success">
            <div class="notification-content">
                <i class="fas fa-check-circle"></i>
                <div>
                    <p>Webapp successfully updated to v<?= htmlspecialchars($_SESSION['update_success']['new_version']) ?>!</p>
                    <p class="small-text">Backup created in <?= htmlspecialchars($_SESSION['update_success']['backup_location']) ?></p>
                    <div class="update-details">
                        <p><strong>Previous Version:</strong> <?= htmlspecialchars($_SESSION['update_success']['previous_version']) ?></p>
                        <p><strong>New Version:</strong> <?= htmlspecialchars($_SESSION['update_success']['new_version']) ?></p>
                        <p><strong>Backup Location:</strong> <?= htmlspecialchars($_SESSION['update_success']['backup_location']) ?></p>
                    </div>
                </div>
                <button class="button primary small" onclick="this.parentElement.parentElement.style.display='none'">Dismiss</button>
            </div>
        </div>
        <?php unset($_SESSION['update_success']); ?>
        <?php endif; ?>

        <?php if ($webAppUpdateAvailable): ?>
        <div class="version-notification">
            <div class="notification-content">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <p>A new version (v<?= htmlspecialchars($latestVersions['webapp_latest']) ?>) is available.</p>
                    <p class="small-text">You're currently using v<?= htmlspecialchars($currentWebAppVersion) ?></p>
                </div>
                <a href="?screen=welcome&action=update" class="button primary small">Update Now</a>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($screen === 'welcome'): ?>
        <div class="screen welcome-screen active">
            <img src="<?= $randomInstallerImage ?>" alt="Installer" class="screen-image">
            <h1>Database Setup Wizard v<?= htmlspecialchars($currentWebAppVersion) ?></h1>
            <div class="version-badge <?= $webAppVersionStatus ?>">
                <?php if ($webAppVersionStatus === 'outdated'): ?>
                    <i class="fas fa-exclamation-circle"></i> Update Available
                <?php elseif ($webAppVersionStatus === 'development'): ?>
                    <i class="fas fa-flask"></i> Development Version
                <?php else: ?>
                    <i class="fas fa-check-circle"></i> Latest Version
                <?php endif; ?>
            </div>
            <p>Configure your database in simple steps</p>
            <div class="button-group">
                <a href="?screen=requirements-info" class="button primary">Get Started</a>
            </div>
        </div>

        <?php elseif ($screen === 'requirements-info'): ?>
        <div class="screen requirements-info-screen active">
            <h1>System Requirements</h1>
            <div class="requirements-info">
                <p>Before proceeding, ensure your system meets these minimum requirements:</p>
                <ul>
                    <li><strong>RAM:</strong> Minimum <?= $minRAM ?>GB</li>
                    <li><strong>Node.js Version:</strong> <?= $minNodeVersion ?> or higher</li>
                    <li><strong>Database:</strong> MySQL running on XAMPP</li>
                    <li><strong>Database Version:</strong> <?= $minDBVersion ?> or higher</li>
                </ul>
            </div>
            <div class="button-group">
                <a href="?screen=welcome" class="button secondary"><i class="fas fa-arrow-left"></i> Back</a>
                <a href="?screen=check-method" class="button primary">Continue</a>
            </div>
        </div>

        <?php elseif ($screen === 'check-method'): ?>
        <div class="screen check-method-screen active">
            <h1>Verification Method</h1>
            <p>Choose how you want to verify system requirements:</p>
            
            <div class="method-cards">
                <div class="method-card" onclick="window.location='?screen=auto-check'">
                    <div class="method-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <h3>Auto Check</h3>
                    <p>Automatically detect system versions</p>
                    <div class="button-group">
                        <a href="?screen=auto-check" class="button primary">Select</a>
                    </div>
                </div>
                
                <div class="method-card" onclick="window.location='?screen=manual-entry'">
                    <div class="method-icon">
                        <i class="fas fa-keyboard"></i>
                    </div>
                    <h3>Manual Entry</h3>
                    <p>Enter system versions manually</p>
                    <div class="button-group">
                        <a href="?screen=manual-entry" class="button primary">Select</a>
                    </div>
                </div>
            </div>
            
            <div class="button-group">
                <a href="?screen=requirements-info" class="button secondary"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
        </div>
        <?php elseif ($screen === 'auto-check'): ?>
<div class="screen auto-check-screen active">
    <h1>System Verification (Auto Check)</h1>
    <p>Detecting your system specifications...</p>
    
    <?php
    $detectedInfo = detectSystemInfo();
    $ram = $detectedInfo['ram'];
    $nodeVersion = $detectedInfo['node_version'];
    $dbVersion = $detectedInfo['db_version'];
    $dbService = $detectedInfo['db_service'];
    $submitted = true;
    
    // Show detection errors if any
    if (!empty($detectedInfo['detection_errors'])) {
        echo '<div class="error-message">';
        echo '<p><i class="fas fa-exclamation-triangle"></i> Some automatic detections failed:</p>';
        echo '<ul>';
        foreach ($detectedInfo['detection_errors'] as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul>';
        echo '<p>Please verify the requirements manually or contact your system administrator.</p>';
        echo '</div>';
    }
    
    $requirementsMet = ($ram >= $minRAM) && 
                      (version_compare($nodeVersion, $minNodeVersion, '>=')) &&
                      (version_compare($dbVersion, $minDBVersion, '>=')) &&
                      $dbService;
    ?>
    
    <div class="verification-results">
        <!-- RAM Detection -->
        <div class="requirement-item <?= $ram >= $minRAM ? 'met' : 'unmet' ?>">
            <div class="req-icon">
                <i class="fas fa-<?= $ram >= $minRAM ? 'check' : 'times' ?>-circle"></i>
            </div>
            <div class="req-details">
                <h3>RAM</h3>
                <div class="req-versions">
                    <span class="current-version"><?= $ram > 0 ? $ram . ' GB' : 'Not detected' ?></span>
                    <span class="vs">vs</span>
                    <span class="required-version">≥ <?= $minRAM ?> GB</span>
                </div>
                <?php if ($ram < $minRAM): ?>
                <p class="req-message"><i class="fas fa-info-circle"></i> Increase your system memory</p>
                <?php elseif ($ram == 0): ?>
                <p class="req-message"><i class="fas fa-info-circle"></i> Could not detect RAM automatically</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Database Service Detection -->
        <div class="requirement-item <?= $dbService ? 'met' : 'unmet' ?>">
            <div class="req-icon">
                <i class="fas fa-<?= $dbService ? 'check' : 'times' ?>-circle"></i>
            </div>
            <div class="req-details">
                <h3>Database Service</h3>
                <div class="req-versions">
                    <span class="current-version"><?= $dbService ? 'Running' : ($dbVersion !== '0.0.0' ? 'Version found but service status unknown' : 'Not detected') ?></span>
                    <span class="vs">vs</span>
                    <span class="required-version">Running</span>
                </div>
                <?php if (!$dbService): ?>
                <p class="req-message"><i class="fas fa-info-circle"></i> Start your MySQL service or verify installation</p>
                <?php endif; ?>
            </div>
        </div>
        
                <div class="requirement-item <?= version_compare($dbVersion, $minDBVersion, '>=') ? 'met' : 'unmet' ?>">
                    <div class="req-icon">
                        <i class="fas fa-<?= version_compare($dbVersion, $minDBVersion, '>=') ? 'check' : 'times' ?>-circle"></i>
                    </div>
                    <div class="req-details">
                        <h3>Database Version</h3>
                        <div class="req-versions">
                            <span class="current-version"><?= $dbVersion ?></span>
                            <span class="vs">vs</span>
                            <span class="required-version">≥ <?= $minDBVersion ?></span>
                        </div>
                        <?php if (version_compare($dbVersion, $minDBVersion, '<')): ?>
                        <p class="req-message"><i class="fas fa-info-circle"></i> Upgrade your database</p>
                        <?php endif; ?>
                        <div class="version-check">
                            <?php if (version_compare($dbVersion, $latestVersions['db_latest'], '<')): ?>
                                <p class="update-available"><i class="fas fa-arrow-circle-up"></i> New version available: <?= $latestVersions['db_latest'] ?></p>
                            <?php else: ?>
                                <p class="up-to-date"><i class="fas fa-check-circle"></i> You have the latest version</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="requirement-item <?= version_compare($nodeVersion, $minNodeVersion, '>=') ? 'met' : 'unmet' ?>">
                    <div class="req-icon">
                        <i class="fas fa-<?= version_compare($nodeVersion, $minNodeVersion, '>=') ? 'check' : 'times' ?>-circle"></i>
                    </div>
                    <div class="req-details">
                        <h3>Node.js Version</h3>
                        <div class="req-versions">
                            <span class="current-version"><?= $nodeVersion ?></span>
                            <span class="vs">vs</span>
                            <span class="required-version">≥ <?= $minNodeVersion ?></span>
                        </div>
                        <?php if (version_compare($nodeVersion, $minNodeVersion, '<')): ?>
                        <p class="req-message"><i class="fas fa-info-circle"></i> Upgrade Node.js</p>
                        <?php endif; ?>
                        <div class="version-check">
                            <?php if (version_compare($nodeVersion, $latestVersions['node_latest'], '<')): ?>
                                <p class="update-available"><i class="fas fa-arrow-circle-up"></i> New version available: <?= $latestVersions['node_latest'] ?></p>
                            <?php else: ?>
                                <p class="up-to-date"><i class="fas fa-check-circle"></i> You have the latest version</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="requirements-summary">
                    <?php if ($requirementsMet): ?>
                        <p class="success"><i class="fas fa-check-circle"></i> All requirements are satisfied!</p>
                        <div class="button-group">
                            <a href="?screen=auto-check" class="button secondary"><i class="fas fa-sync-alt"></i> Re-check</a>
                            <a href="?screen=database" class="button primary">Continue to Database Setup</a>
                        </div>
                    <?php else: ?>
                        <p class="error"><i class="fas fa-exclamation-circle"></i> Some requirements are not met</p>
                        <div class="button-group">
                            <a href="?screen=auto-check" class="button secondary"><i class="fas fa-sync-alt"></i> Re-check</a>
                            <a href="?screen=manual-entry" class="button primary">Enter Manually</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="button-group">
                <a href="?screen=check-method" class="button secondary"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
        </div>

        <?php elseif ($screen === 'manual-entry'): ?>
        <div class="screen manual-entry-screen active">
            <h1>System Verification (Manual Entry)</h1>
            <p>Please enter your system specifications:</p>
            
            <form method="post">
                <div class="verification-results">
                    <div class="requirement-item">
                        <div class="req-icon">
                            <i class="fas fa-memory"></i>
                        </div>
                        <div class="req-details">
                            <h3>RAM</h3>
                            <div class="form-group">
                                <input type="number" step="0.1" name="ram" placeholder="Enter RAM in GB" 
                                       value="<?= htmlspecialchars($ram) ?>" required>
                                <p class="req-message"><i class="fas fa-info-circle"></i> Minimum required: <?= $minRAM ?>GB</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="requirement-item met">
                        <div class="req-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="req-details">
                            <h3>Database Service</h3>
                            <div class="req-versions">
                                <span class="current-version">Running</span>
                                <span class="vs">vs</span>
                                <span class="required-version">Running</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="requirement-item">
                        <div class="req-icon">
                            <i class="fas fa-database"></i>
                        </div>
                        <div class="req-details">
                            <h3>Database Version</h3>
                            <div class="form-group">
                                <input type="text" name="db_version" placeholder="Enter MySQL version (e.g. 5.7.0)" 
                                       value="<?= htmlspecialchars($dbVersion) ?>" required>
                                <p class="req-message"><i class="fas fa-info-circle"></i> Minimum required: <?= $minDBVersion ?></p>
                                <div class="version-check">
                                    <?php if (version_compare($dbVersion, $latestVersions['db_latest'], '<') && !empty($dbVersion)): ?>
                                        <p class="update-available"><i class="fas fa-arrow-circle-up"></i> New version available: <?= $latestVersions['db_latest'] ?></p>
                                    <?php elseif (!empty($dbVersion)): ?>
                                        <p class="up-to-date"><i class="fas fa-check-circle"></i> You have the latest version</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="requirement-item">
                        <div class="req-icon">
                            <i class="fas fa-code"></i>
                        </div>
                        <div class="req-details">
                            <h3>Node.js Version</h3>
                            <div class="form-group">
                                <input type="text" name="node_version" placeholder="Enter Node.js version (e.g. 14.0.0)" 
                                       value="<?= htmlspecialchars($nodeVersion) ?>" required>
                                <p class="req-message"><i class="fas fa-info-circle"></i> Minimum required: <?= $minNodeVersion ?></p>
                                <div class="version-check">
                                    <?php if (version_compare($nodeVersion, $latestVersions['node_latest'], '<') && !empty($nodeVersion)): ?>
                                        <p class="update-available"><i class="fas fa-arrow-circle-up"></i> New version available: <?= $latestVersions['node_latest'] ?></p>
                                    <?php elseif (!empty($nodeVersion)): ?>
                                        <p class="up-to-date"><i class="fas fa-check-circle"></i> You have the latest version</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="requirements-summary">
                        <?php if ($submitted && $requirementsMet): ?>
                            <p class="success"><i class="fas fa-check-circle"></i> All requirements are satisfied!</p>
                            <div class="button-group">
                                <button type="submit" class="button secondary"><i class="fas fa-sync-alt"></i> Re-check</button>
                                <a href="?screen=database" class="button primary">Continue to Database Setup</a>
                            </div>
                        <?php elseif ($submitted && !$requirementsMet): ?>
                            <p class="error"><i class="fas fa-exclamation-circle"></i> Some requirements are not met</p>
                            <div class="button-group">
                                <button type="submit" class="button secondary"><i class="fas fa-sync-alt"></i> Re-check</button>
                            </div>
                        <?php else: ?>
                            <div class="button-group">
                                <button type="submit" class="button primary">Check Requirements</button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
            
            <div class="button-group">
                <a href="?screen=check-method" class="button secondary"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
        </div>
        
        <?php elseif ($screen === 'database'): ?>
        <div class="screen database-screen active">
            <img src="<?= $randomDatabaseImage ?>" alt="Database" class="screen-image">
            <h1>Database Setup</h1>
            <p>The system will automatically use the SQL file from D:\xamp\htdocs\uploads\test.sql</p>
            
            <?php if ($uploadError): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <p><?= htmlspecialchars($uploadError) ?></p>
            </div>
            <?php endif; ?>
            
            <form method="post" class="upload-form">
                <div class="form-group">
                    <label for="db_host">MySQL Server Address</label>
                    <input type="text" id="db_host" name="db_host" value="<?= htmlspecialchars($dbConfig['host']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="db_username">Username</label>
                    <input type="text" id="db_username" name="db_username" value="<?= htmlspecialchars($dbConfig['username']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="db_password">Password</label>
                    <input type="password" id="db_password" name="db_password" value="<?= htmlspecialchars($dbConfig['password']) ?>">
                </div>
                
                <div class="form-group">
                    <label for="db_name">Database Name</label>
                    <input type="text" id="db_name" name="db_name" value="<?= htmlspecialchars($dbConfig['name']) ?>" placeholder="Enter any database name" required>
                    <p class="req-message"><i class="fas fa-info-circle"></i> Database will be created if it doesn't exist</p>
                </div>
                
                <div class="button-group">
                    <button type="submit" name="upload_sql" class="button primary">
                        <i class="fas fa-upload"></i> Execute SQL
                    </button>
                </div>
            </form>
            
            <div class="button-group">
                <a href="?screen=<?= $submitted && isset($autoCheck) && $autoCheck ? 'auto-check' : 'manual-entry' ?>" class="button secondary"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
        </div>

        <?php elseif ($screen === 'sql_editor'): ?>
        <div class="screen sql-editor-screen active">
            <h1>SQL Execution Results</h1>
            
            <?php if (isset($successMessage)): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <p><?= htmlspecialchars($successMessage) ?></p>
            </div>
            
            <?php if (!empty($_SESSION['tables'])): ?>
            <div class="tables-list">
                <h3>Tables in Database (<?= htmlspecialchars($_SESSION['current_db'] ?? '') ?>):</h3>
                <ul>
                    <?php foreach ($_SESSION['tables'] as $table): ?>
                    <li><i class="fas fa-table"></i> <?= htmlspecialchars($table) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            <?php endif; ?>
            
            <?php if (isset($connectionError)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <p><?= htmlspecialchars($connectionError) ?></p>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['db_backup'])): ?>
            <div class="backup-info">
                <p><i class="fas fa-history"></i> Backup created at <?= $_SESSION['db_backup']['timestamp'] ?> with <?= count($_SESSION['db_backup']['tables']) ?> tables</p>
                <form method="post" style="margin-top: 1rem;">
                    <input type="hidden" name="db_host" value="<?= htmlspecialchars($dbConfig['host']) ?>">
                    <input type="hidden" name="db_username" value="<?= htmlspecialchars($dbConfig['username']) ?>">
                    <input type="hidden" name="db_password" value="<?= htmlspecialchars($dbConfig['password']) ?>">
                    <input type="hidden" name="db_name" value="<?= htmlspecialchars($dbConfig['name']) ?>">
                    <button type="submit" name="restore_backup" class="button secondary small">
                        <i class="fas fa-undo"></i> Restore from Backup
                    </button>
                </form>
            </div>
            <?php endif; ?>
            
            <form method="post" class="sql-editor-form">
                <div class="form-group">
                    <label>SQL File Loaded From:</label>
                    <div class="file-path-display">
                        <i class="fas fa-file-code"></i> 
                        D:\xamp\htdocs\uploads\test.sql
                    </div>
                </div>
                
                <input type="hidden" name="sql_content" value="<?= htmlspecialchars($sqlContent) ?>">
                <input type="hidden" name="db_host" value="<?= htmlspecialchars($dbConfig['host']) ?>">
                <input type="hidden" name="db_username" value="<?= htmlspecialchars($dbConfig['username']) ?>">
                <input type="hidden" name="db_password" value="<?= htmlspecialchars($dbConfig['password']) ?>">
                <input type="hidden" name="db_name" value="<?= htmlspecialchars($_SESSION['db_name'] ?? '') ?>">
                
                <div class="button-group">
                    <button type="submit" name="execute_sql" class="button primary">
                        <i class="fas fa-play"></i> Execute SQL
                    </button>
                    <button type="submit" name="save_sql" class="button secondary">
                        <i class="fas fa-download"></i> Download SQL
                    </button>
                </div>
            </form>
            
            <div class="button-group">
                <a href="?screen=database" class="button secondary"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
        </div>
        <?php endif; ?>
        
    </div>

    <script>
        // Background animations
        document.addEventListener('DOMContentLoaded', function() {
            const circles = document.querySelectorAll('.bg-circle');
            circles.forEach((circle, index) => {
                circle.style.left = `${Math.random() * 100}%`;
                circle.style.top = `${Math.random() * 100}%`;
                circle.style.width = `${10 + Math.random() * 20}px`;
                circle.style.height = circle.style.width;
                circle.style.animationDuration = `${20 + Math.random() * 40}s`;
                circle.style.animationDelay = `${Math.random() * 10}s`;
            });
        });
    </script>
</body>
</html>
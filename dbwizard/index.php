<?php
// Start output buffering at the very beginning
ob_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '512M'); // Set reasonable memory limit
set_time_limit(0); // Remove time limit for script execution
ignore_user_abort(true); // Continue execution even if client disconnects

// Set maximum execution time for large files
ini_set('max_execution_time', 0);
ini_set('max_input_time', 0);

// Increase PHP upload limits
ini_set('upload_max_filesize', '4G');
ini_set('post_max_size', '4G');

// Start session
session_start();

// Screen management
$screen = $_GET['screen'] ?? 'welcome';

// Web App Version Management
$currentWebAppVersion = '1.1.0';

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

// Check for existing database configuration
$dbConfigFile = 'db_config.php';
$hasExistingConfig = file_exists($dbConfigFile);
$existingConfig = $hasExistingConfig ? include($dbConfigFile) : null;

// Database connection persistence
$dbConfig = [
    'host' => $_SESSION['db_host'] ?? $existingConfig['host'] ?? 'localhost',
    'username' => $_SESSION['db_username'] ?? $existingConfig['username'] ?? 'root',
    'password' => $_SESSION['db_password'] ?? $existingConfig['password'] ?? '',
    'name' => $_SESSION['db_name'] ?? $existingConfig['name'] ?? ''
];

// Function to process large SQL files in chunks
function executeLargeSQLFile($filePath, $dbConfig) {
    if (!file_exists($filePath)) {
        throw new Exception("SQL file not found: $filePath");
    }

    // Create database if it doesn't exist
    $conn = new mysqli($dbConfig['host'], $dbConfig['username'], $dbConfig['password']);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    $database = $dbConfig['name'];
    $conn->query("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $conn->select_db($database);

    // Set MySQL session variables for improved performance with large imports
    $conn->query("SET autocommit=0");
    $conn->query("SET unique_checks=0");
    $conn->query("SET foreign_key_checks=0");
    $conn->query("SET sql_log_bin=0"); // Disable binary logging if available
    
    // Initialize counters
    $stats = [
        'total_queries' => 0,
        'successful_queries' => 0,
        'skipped_queries' => 0,
        'failed_queries' => 0,
        'total_size' => filesize($filePath),
        'processed_size' => 0
    ];

    // Stream and process the file in chunks
    $handle = fopen($filePath, 'r');
    if (!$handle) {
        throw new Exception("Cannot open SQL file: $filePath");
    }

    // Variables for storing SQL statements across chunks
    $query = '';
    $delimiter = ';';

    // Process file in chunks
    while (!feof($handle)) {
        // Read a chunk of data
        $chunk = fread($handle, 8192); // 8KB chunks
        $stats['processed_size'] += strlen($chunk);
        
        // Update progress in session for AJAX tracking
        $_SESSION['sql_progress'] = [
            'total_size' => $stats['total_size'],
            'processed_size' => $stats['processed_size'],
            'percentage' => min(99, round(($stats['processed_size'] / $stats['total_size']) * 100, 2))
        ];
        
        // Include this chunk in the current query
        $query .= $chunk;
        
        // Process all complete statements in this chunk
        $offset = 0;
        while (($pos = strpos($query, $delimiter, $offset)) !== false) {
            $statement = substr($query, 0, $pos);
            $offset = $pos + strlen($delimiter);
            
            // Check for DELIMITER command which can change the delimiter
            if (preg_match('/^\s*DELIMITER\s+(.+)$/i', trim($statement), $matches)) {
                $delimiter = $matches[1];
                $query = substr($query, $offset);
                $offset = 0;
                continue;
            }
            
            // Skip empty statements
            if (trim($statement) === '') {
                $query = substr($query, $offset);
                $offset = 0;
                continue;
            }
            
            // Check if it's a CREATE TABLE statement and add IF NOT EXISTS
            if (preg_match('/^\s*CREATE\s+TABLE\s+(?!IF\s+NOT\s+EXISTS\s+)/i', $statement)) {
                $statement = preg_replace('/CREATE\s+TABLE\s+/i', 'CREATE TABLE IF NOT EXISTS ', $statement);
            }
            
            // Execute the statement
            $stats['total_queries']++;
            try {
                $result = $conn->query($statement);
                if ($result !== false) {
                    $stats['successful_queries']++;
                } else {
                    // Log the error
    error_log("SQL Error: " . $conn->error . " in query: " . substr($statement, 0, 200));
                    // Check if error is due to table already existing
                    if ($conn->errno == 1050) { // Table already exists error
                        $stats['skipped_queries']++;
                    } else {
                        $stats['failed_queries']++;
                        // Store the error in session for debugging
        $_SESSION['sql_errors'][] = [
            'error' => $conn->error,
            'errno' => $conn->errno,
            'query' => substr($statement, 0, 200) . (strlen($statement) > 200 ? '...' : '')
        ];
                    }
                }
            } catch (Exception $e) {
                $stats['failed_queries']++;
            }
            
            // Remove processed statement from query string
            $query = substr($query, $offset);
            $offset = 0;
        }
        
        // Commit transactions periodically to avoid huge transaction logs
        if ($stats['total_queries'] % 100 === 0) {
            $conn->commit();
        }
    }
    
    // Execute final query if there's anything left
    if (trim($query) !== '') {
        $stats['total_queries']++;
        try {
            $result = $conn->query($query);
            if ($result !== false) {
                $stats['successful_queries']++;
            } else {
                if ($conn->errno == 1050) {
                    $stats['skipped_queries']++;
                } else {
                    $stats['failed_queries']++;
                }
            }
        } catch (Exception $e) {
            $stats['failed_queries']++;
        }
    }
    
    // Close the file
    fclose($handle);
    
    // Re-enable checks and commit the final transaction
    $conn->query("SET autocommit=1");
    $conn->query("SET unique_checks=1");
    $conn->query("SET foreign_key_checks=1");
    $conn->query("SET sql_log_bin=1");
    $conn->commit();
    
    // Get all tables in the database
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    if ($result) {
        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }
    }
    $_SESSION['tables'] = $tables;
    
    $conn->close();
    
    // Set completion flag
    $_SESSION['sql_progress']['percentage'] = 100;
    
    return $stats;
}

// Handle AJAX progress request
if(isset($_GET['action']) && $_GET['action'] == 'check_progress') {
    header('Content-Type: application/json');
    
    // Return actual progress if available, otherwise use simulation
    if (isset($_SESSION['sql_progress'])) {
        $progress = $_SESSION['sql_progress'];
    } else {
        // Simulate progress steps if real progress not available
        $progress = isset($_SESSION['simulated_progress']) ? $_SESSION['simulated_progress'] : 0;
        
        if($progress < 100) {
            $progress += rand(1, 3); // Smaller increments for more realistic simulation
            if($progress > 99) $progress = 99; // Never reach 100% in simulation mode
            $_SESSION['simulated_progress'] = $progress;
        }
        
        $progress = [
            'percentage' => $progress,
            'processed_size' => 0,
            'total_size' => 100
        ];
    }
    
    $response = [
        'progress' => $progress['percentage'],
        'processed_mb' => isset($progress['processed_size']) ? round($progress['processed_size'] / 1048576, 2) : 0,
        'total_mb' => isset($progress['total_size']) ? round($progress['total_size'] / 1048576, 2) : 0,
        'complete' => ($progress['percentage'] >= 100)
    ];
    
    echo json_encode($response);
    exit;
}

// Handle form submissions without redirects
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle saving database configuration
    if (isset($_POST['save_db_config'])) {
        $configContent = "<?php\n// db_config.php - Auto-generated database configuration\nreturn [\n";
        $configContent .= "    'host' => '" . addslashes($_POST['db_host']) . "',\n";
        $configContent .= "    'username' => '" . addslashes($_POST['db_username']) . "',\n";
        $configContent .= "    'password' => '" . addslashes($_POST['db_password']) . "',\n";
        $configContent .= "    'name' => '" . addslashes($_POST['db_name']) . "'\n";
        $configContent .= "];\n";
        
        file_put_contents($dbConfigFile, $configContent);
        
        // Update session with new values
        $_SESSION['db_host'] = $_POST['db_host'];
        $_SESSION['db_username'] = $_POST['db_username'];
        $_SESSION['db_password'] = $_POST['db_password'];
        $_SESSION['db_name'] = $_POST['db_name'];
        
        // Stay on the same screen
        $screen = 'sql_editor';
        $_SESSION['db_config_saved'] = true;
    }
    
    // Handle SQL file selection and validation
    if (isset($_POST['select_sql_file'])) {
        $sqlFile = $_POST['sql_file_path'];
        if (file_exists($sqlFile)) {
            $_SESSION['sql_file_path'] = $sqlFile;
            $_SESSION['sql_file_size'] = filesize($sqlFile);
            $screen = 'sql_editor';
        } else {
            $_SESSION['error'] = "SQL file not found: $sqlFile";
            $screen = 'sql_editor';
        }
    }
    
    // Handle SQL execution
    if (isset($_POST['execute_sql'])) {
        // Reset progress for new execution
        unset($_SESSION['sql_progress']);
        unset($_SESSION['simulated_progress']);
        
        try {
            $sqlFile = $_POST['sql_file_path'] ?? $_SESSION['sql_file_path'] ?? '';
            
            if (empty($sqlFile) || !file_exists($sqlFile)) {
                throw new Exception("SQL file not found or not specified");
            }
            
            // Start execution and capture statistics
            $result = executeLargeSQLFile($sqlFile, $dbConfig);
            
            // Store results in session
            $_SESSION['sql_success'] = true;
            $_SESSION['stats'] = $result;
            $_SESSION['current_db'] = $dbConfig['name'] ?? 'database';
            $_SESSION['show_success_before_redirect'] = true;
            
            $screen = 'sql_editor';
            
        } catch (Exception $e) {
            $_SESSION['error'] = "Error: " . $e->getMessage();
            $screen = 'sql_editor';
        }
    }
}

// Handle update request with database backup
if (isset($_GET['action']) && $_GET['action'] === 'update' && $screen === 'welcome') {
    // Create backup directory if it doesn't exist
    if (!file_exists('backups')) {
        if (!mkdir('backups', 0755, true)) {
            die("Failed to create backups directory");
        }
    }
    
    // Create a unique backup directory for this update
    $backupDir = 'backups/' . date('Y-m-d_His');
    if (!mkdir($backupDir, 0755)) {
        die("Failed to create backup directory: $backupDir");
    }
    
    // Backup the current web app files (without zip)
    $webappBackupDir = $backupDir . '/webapp_files';
    if (!mkdir($webappBackupDir, 0755)) {
        die("Failed to create webapp backup directory");
    }
    
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
                if (!mkdir($destDir, 0755, true)) {
                    die("Failed to create directory: $destDir");
                }
            }
            
            if (!copy($filePath, $destPath)) {
                die("Failed to copy file: $filePath");
            }
        }
    }
    
    // Database backup function - optimized for larger databases
    function backupDatabaseTables($dbConfig, $backupDir) {
        try {
            // Connect to database
            $conn = new mysqli(
                $dbConfig['host'],
                $dbConfig['username'],
                $dbConfig['password'],
                $dbConfig['name']
            );
            
            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }
            
            // Get all tables
            $tables = array();
            $result = $conn->query("SHOW TABLES");
            while ($row = $result->fetch_row()) {
                $tables[] = $row[0];
            }
            
            if (empty($tables)) {
                return "No tables found to backup";
            }
            
            // Create SQL file and open in append mode
            $sqlFile = $backupDir . '/database_backup.sql';
            $handle = fopen($sqlFile, 'w');
            
            if (!$handle) {
                throw new Exception("Cannot create backup file: $sqlFile");
            }
            
            // Write header
            fwrite($handle, "-- Database Backup\n");
            fwrite($handle, "-- Date: " . date('Y-m-d H:i:s') . "\n");
            fwrite($handle, "-- Database: `" . $dbConfig['name'] . "`\n\n");
            
            // Write database creation statement
            fwrite($handle, "CREATE DATABASE IF NOT EXISTS `" . $dbConfig['name'] . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;\n");
            fwrite($handle, "USE `" . $dbConfig['name'] . "`;\n\n");
            
            // Process each table
            foreach ($tables as $table) {
                // Table structure
                $res = $conn->query("SHOW CREATE TABLE `$table`");
                $row = $res->fetch_row();
                fwrite($handle, "-- Table structure for table `$table`\n");
                fwrite($handle, "DROP TABLE IF EXISTS `$table`;\n");
                fwrite($handle, $row[1] . ";\n\n");
                
                // Table data - use chunking to handle large tables
                fwrite($handle, "-- Dumping data for table `$table`\n");
                
                // Get row count for batching
                $countRes = $conn->query("SELECT COUNT(*) FROM `$table`");
                $countRow = $countRes->fetch_row();
                $totalRows = $countRow[0];
                
                if ($totalRows > 0) {
                    // Batch size - adjust based on table structure
                    $batchSize = 1000;
                    
                    for ($offset = 0; $offset < $totalRows; $offset += $batchSize) {
                        $batchRes = $conn->query("SELECT * FROM `$table` LIMIT $offset, $batchSize");
                        
                        if ($batchRes->num_rows > 0) {
                            // Get column names for INSERT statement
                            $columnsRes = $conn->query("SHOW COLUMNS FROM `$table`");
                            $columns = [];
                            while ($col = $columnsRes->fetch_assoc()) {
                                $columns[] = "`" . $col['Field'] . "`";
                            }
                            
                            // Start INSERT statement
                            $insertHeader = "INSERT INTO `$table` (" . implode(', ', $columns) . ") VALUES\n";
                            $firstRow = true;
                            $rowCount = 0;
                            $insertValues = '';
                            
                            while ($row = $batchRes->fetch_assoc()) {
                                $rowCount++;
                                $values = array_map(function($v) use ($conn) {
                                    if ($v === null) {
                                        return 'NULL';
                                    }
                                    return "'" . $conn->real_escape_string($v) . "'";
                                }, array_values($row));
                                
                                // Add comma if not first row
                                if (!$firstRow) {
                                    $insertValues .= ",\n";
                                }
                                
                                $insertValues .= "(" . implode(', ', $values) . ")";
                                $firstRow = false;
                                
                                // Write in batches of 100 rows to avoid creating huge strings in memory
                                if ($rowCount >= 100) {
                                    fwrite($handle, $insertHeader . $insertValues . ";\n\n");
                                    $insertValues = '';
                                    $firstRow = true;
                                    $rowCount = 0;
                                }
                            }
                            
                            // Write any remaining rows
                            if (!$firstRow) {
                                fwrite($handle, $insertHeader . $insertValues . ";\n\n");
                            }
                        }
                    }
                }
            }
            
            fclose($handle);
            $conn->close();
            
            return "Database backup created successfully";
            
        } catch (Exception $e) {
            return "Backup failed: " . $e->getMessage();
        }
    }

    // Execute database backup
    $backupResult = backupDatabaseTables($dbConfig, $backupDir);
    
    // Create log file
    $logContent = "Update Log\n";
    $logContent .= "==========\n";
    $logContent .= "Date: " . date('Y-m-d H:i:s') . "\n";
    $logContent .= "Previous Version: " . $installedVersion . "\n";
    $logContent .= "New Version: " . $latestWebAppVersion . "\n";
    $logContent .= "Backup Location: " . $backupDir . "\n";
    $logContent .= "Backup Contents:\n";
    $logContent .= "- Webapp files (PHP)\n";
    $logContent .= "- Database backup: " . $backupResult . "\n";
    
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
    
    // Stay on welcome screen
    $screen = 'welcome';
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

// Initialize variables
$ram = $nodeVersion = $dbVersion = '';
$submitted = $requirementsMet = false;
$connectionError = $uploadError = $successMessage = '';
$sqlContent = $_SESSION['sql_content'] ?? '';
$tables = [];
$dbName = $_SESSION['db_name'] ?? '';

// Get latest versions
$latestVersions = getLatestVersions();

// Check for web app version update
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

// Images
$randomInstallerImage = 'https://cdn-icons-png.flaticon.com/512/2092/2092693.png';
$randomDatabaseImage = 'https://cdn-icons-png.flaticon.com/512/4299/4299956.png';

// Flush output buffer before HTML
ob_end_flush();
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Progress bar styles */
        .progress-container {
            width: 100%;
            background-color: #f0f0f0;
            border-radius: 10px;
            margin: 20px 0;
            overflow: hidden;
            position: relative;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .progress-bar {
            height: 20px;
            background: linear-gradient(90deg, #4CAF50, #8BC34A);
            width: 0%;
            border-radius: 10px;
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .progress-text {
            color: white;
            font-size: 12px;
            font-weight: bold;
            text-shadow: 1px 1px 1px rgba(0,0,0,0.3);
            position: absolute;
            width: 100%;
            text-align: center;
        }
        
        .execution-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.6);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            flex-direction: column;
        }
        
        .execution-overlay .progress-container {
            width: 80%;
            max-width: 500px;
            margin-bottom: 20px;
        }
        
        .execution-message {
            color: white;
            text-align: center;
            font-size: 18px;
            margin-bottom: 20px;
        }
    </style>
    <?php if (isset($_SESSION['show_success_before_redirect'])): ?>
    <meta http-equiv="refresh" content="3;url=?screen=welcome">
    <?php endif; ?>
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
            
            <?php if (isset($_SESSION['sql_success'])): ?>
            <div class="success-message" style="margin-top: 20px;">
                <i class="fas fa-check-circle"></i>
                <p>SQL executed successfully in database '<?= htmlspecialchars($_SESSION['current_db']) ?>'!</p>
                <?php if (isset($_SESSION['stats'])): ?>
                <div class="execution-stats">
                    <p>Total queries: <?= $_SESSION['stats']['total_queries'] ?></p>
                    <p>Successful: <?= $_SESSION['stats']['successful_queries'] ?></p>
                    <?php if ($_SESSION['stats']['skipped_queries'] > 0): ?>
                    <p>Skipped (duplicate tables): <?= $_SESSION['stats']['skipped_queries'] ?></p>
                    <?php endif; ?>
                    <?php if ($_SESSION['stats']['failed_queries'] > 0): ?>
                    <p>Failed: <?= $_SESSION['stats']['failed_queries'] ?></p>
                    <?php endif; ?>
                </div>
                <?php unset($_SESSION['stats']); ?>
                <?php endif; ?>
                <?php if (!empty($_SESSION['tables'])): ?>
                <div class="tables-list">
                    <h3>Tables in database:</h3>
                    <ul>
                        <?php foreach ($_SESSION['tables'] as $table): ?>
                        <li><i class="fas fa-table"></i> <?= htmlspecialchars($table) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
            <?php 
                unset($_SESSION['sql_success']);
                unset($_SESSION['tables']);
                unset($_SESSION['current_db']);
            ?>
            <?php endif; ?>
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
            
            <?php if (!empty($detectedInfo['detection_errors'])): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <p>Some automatic detections failed:</p>
                <ul>
                    <?php foreach ($detectedInfo['detection_errors'] as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
                <p>Please verify the requirements manually or contact your system administrator.</p>
            </div>
            <?php endif; ?>
            
            <div class="verification-results">
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
            
            <?php if ($hasExistingConfig): ?>
            <div class="existing-config-notice">
                <i class="fas fa-info-circle"></i>
                <p>Existing database configuration found. You can use these credentials or update them below.</p>
            </div>
            <?php endif; ?>
            
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
                    <input type="text" id="db_name" name="db_name" value="<?= htmlspecialchars($dbConfig['name']) ?>" placeholder="Enter database name" required>
                    <p class="req-message"><i class="fas fa-info-circle"></i> Database will be created if it doesn't exist</p>
                </div>
                
                <div class="button-group">
                    <button type="submit" name="save_db_config" class="button primary">
                        <i class="fas fa-save"></i> Save Configuration & Continue
                    </button>
                </div>
            </form>
            
            <div class="button-group">
                <a href="?screen=<?= $submitted && isset($autoCheck) && $autoCheck ? 'auto-check' : 'manual-entry' ?>" class="button secondary"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
        </div>
        <?php elseif ($screen === 'sql_editor'): ?>
        <div class="screen sql-editor-screen active">
            <h1>SQL Execution</h1>
            
            <?php if (isset($_SESSION['show_success_before_redirect'])): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <p>Database operations completed successfully in '<?= htmlspecialchars($_SESSION['current_db'] ?? 'database') ?>'!</p>
                <?php if (!empty($_SESSION['tables'])): ?>
                <div class="tables-list">
                    <h3>Current tables in database:</h3>
                    <ul>
                        <?php foreach ($_SESSION['tables'] as $table): ?>
                        <li><i class="fas fa-table"></i> <?= htmlspecialchars($table) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                <p>Redirecting to home screen in 3 seconds...</p>
            </div>
            <?php 
                unset($_SESSION['show_success_before_redirect']);
                unset($_SESSION['error']);
            ?>
            <?php endif; ?>
            
            <form method="post" id="sql-execution-form">
    <div class="form-group">
        <label for="sql_file_path">SQL File Location:</label>
        <input type="text" id="sql_file_path" name="sql_file_path" 
               value="<?= isset($_SESSION['sql_file_path']) ? htmlspecialchars($_SESSION['sql_file_path']) : 'D:\\xamp\\htdocs\\dbwizard\\test.sql' ?>" 
               required>
    </div>
    
    <input type="hidden" name="db_host" value="<?= htmlspecialchars($dbConfig['host']) ?>">
    <input type="hidden" name="db_username" value="<?= htmlspecialchars($dbConfig['username']) ?>">
    <input type="hidden" name="db_password" value="<?= htmlspecialchars($dbConfig['password']) ?>">
    <input type="hidden" name="db_name" value="<?= htmlspecialchars($dbConfig['name']) ?>">
    
    <div class="button-group">
        <button type="submit" name="execute_sql" id="execute-sql-btn" class="button primary">
            <i class="fas fa-play"></i> Execute SQL
        </button>
    </div>
</form>
            
            <!-- Progress bar container -->
            <div id="progress-container" style="display: none; margin-top: 20px;">
                <h3>Processing SQL File...</h3>
                <div class="progress-bar-container">
                    <div class="progress-bar" id="sql-progress-bar"></div>
                </div>
                <p id="progress-status">0%</p>
            </div>
            
            <!-- Result container -->
            <div id="execution-result" style="display: none; margin-top: 20px;"></div>
            
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
            
            // SQL execution with progress bar
            const executeBtn = document.getElementById('execute-sql-btn');
            if (executeBtn) {
                executeBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Get form data
                    const form = document.getElementById('sql-execution-form');
                    const formData = new FormData(form);
                    formData.append('execute_sql', '1');
                    
                    // Show progress bar
                    const progressContainer = document.getElementById('progress-container');
                    const progressBar = document.getElementById('sql-progress-bar');
                    const progressStatus = document.getElementById('progress-status');
                    const executionResult = document.getElementById('execution-result');
                    
                    progressContainer.style.display = 'block';
                    executionResult.style.display = 'none';
                    executeBtn.disabled = true;
                    
                    // Function to check progress
                    function checkProgress() {
                        fetch('?action=check_progress')
                            .then(response => response.json())
                            .then(data => {
                                const progress = data.progress;
                                const processedMB = data.processed_mb;
                                const totalMB = data.total_mb;
                                
                                progressBar.style.width = progress + '%';
                                progressStatus.textContent = `${Math.round(progress)}% (${processedMB} MB of ${totalMB} MB processed)`;
                                
                                if (progress < 100) {
                                    setTimeout(checkProgress, 1000);
                                } else {
                                    // Execution complete
                                    executionResult.innerHTML = `
                                        <div class="success-message">
                                            <i class="fas fa-check-circle"></i>
                                            <p>Database operations completed successfully!</p>
                                            <p>Redirecting to home screen in 3 seconds...</p>
                                        </div>
                                    `;
                                    executionResult.style.display = 'block';
                                    
                                    // Redirect after delay
                                    setTimeout(function() {
                                        window.location.href = '?screen=welcome';
                                    }, 3000);
                                }
                            });
                    }
                    
                    // Start actual execution
                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        // Start checking progress
                        setTimeout(checkProgress, 1000);
                    })
                    .catch(error => {
                        progressBar.style.width = '100%';
                        progressStatus.textContent = '100%';
                        executionResult.innerHTML = `
                            <div class="error-message">
                                <i class="fas fa-exclamation-circle"></i>
                                <p>An error occurred during execution: ${error.message}</p>
                            </div>
                        `;
                        executionResult.style.display = 'block';
                        executeBtn.disabled = false;
                    });
                });
            }
        });
    </script>
    
    
    <style>
        /* Add styles for progress bar */
        .progress-bar-container {
            width: 100%;
            height: 20px;
            background-color: #f0f0f0;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .progress-bar {
            height: 100%;
            width: 0;
            background: linear-gradient(90deg, #4caf50, #8bc34a);
            border-radius: 10px;
            transition: width 0.4s ease;
        }
        
        #progress-status {
            text-align: center;
            font-weight: bold;
            margin-top: 5px;
        }
    </style>
</body>
</html>

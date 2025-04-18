<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Screen management
$screen = $_GET['screen'] ?? 'welcome';

// Minimum requirements
$minRAM = 1; // GB
$minNodeVersion = '14.0.0';
$minDBVersion = '5.7.0';

// System detection function
function detectSystemInfo() {
    $systemInfo = [
        'ram' => 0,
        'node_version' => '0.0.0',
        'db_version' => '0.0.0',
        'db_service' => false
    ];

    // Detect RAM
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $output = shell_exec('wmic memorychip get capacity');
        if ($output && preg_match_all('/\d+/', $output, $matches)) {
            $systemInfo['ram'] = round(array_sum($matches[0]) / (1024 * 1024 * 1024), 1);
        }
    } else {
        $output = shell_exec('free -g | grep Mem');
        if ($output && preg_match('/Mem:\s+(\d+)/', $output, $matches)) {
            $systemInfo['ram'] = (int)$matches[1];
        }
    }

    // Detect Node.js version
    $output = shell_exec('node -v');
    if ($output && preg_match('/v?(\d+\.\d+\.\d+)/', $output, $matches)) {
        $systemInfo['node_version'] = $matches[1];
    }

    // Detect MySQL version and service status
    try {
        $conn = new mysqli('localhost', 'root', '');
        if (!$conn->connect_error) {
            $systemInfo['db_service'] = true;
            $result = $conn->query("SELECT VERSION()");
            if ($result && $row = $result->fetch_array()) {
                if (preg_match('/\d+\.\d+\.\d+/', $row[0], $matches)) {
                    $systemInfo['db_version'] = $matches[0];
                }
            }
            $conn->close();
        }
    } catch (Exception $e) {
        // Ignore errors for detection
    }

    return $systemInfo;
}

// Initialize variables
$ram = $nodeVersion = $dbVersion = '';
$submitted = $requirementsMet = false;
$connectionError = $uploadError = $successMessage = '';
$sqlContent = $_SESSION['sql_content'] ?? '';
$tables = [];

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
        if (isset($_FILES['sql_file']) && $_FILES['sql_file']['error'] === UPLOAD_ERR_OK) {
            $fileExt = strtolower(pathinfo($_FILES['sql_file']['name'], PATHINFO_EXTENSION));
            if ($fileExt === 'sql') {
                $_SESSION['sql_content'] = file_get_contents($_FILES['sql_file']['tmp_name']);
                header("Location: ?screen=sql_editor");
                exit();
            } else {
                $uploadError = 'Only .sql files are allowed';
            }
        } else {
            $uploadError = 'Error uploading file';
        }
    }
    
    if (isset($_POST['execute_sql'])) {
        try {
            $host = $_POST['db_host'] ?? 'localhost';
            $username = $_POST['db_username'] ?? 'root';
            $password = $_POST['db_password'] ?? '';
            $database = $_POST['db_name'] ?? 'test1';
            
            $conn = new mysqli($host, $username, $password, $database);
            
            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }
            
            // Execute SQL from file
            $sqlContent = $_POST['sql_content'] ?? '';
            $queries = explode(';', $sqlContent);
            
            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query)) {
                    $conn->query($query);
                }
            }
            
            // Get tables from database
            $result = $conn->query("SHOW TABLES");
            while ($row = $result->fetch_array()) {
                $tables[] = $row[0];
            }
            
            $successMessage = "SQL executed successfully! Found " . count($tables) . " tables.";
            $_SESSION['tables'] = $tables;
            
        } catch (Exception $e) {
            $connectionError = $e->getMessage();
        }
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
    <title>Database Setup Wizard</title>
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
        <?php if ($screen === 'welcome'): ?>
        <div class="screen welcome-screen active">
            <img src="<?= $randomInstallerImage ?>" alt="Installer" class="screen-image">
            <h1>Database Setup Wizard</h1>
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
            
            <div class="verification-results">
                <div class="requirement-item <?= $ram >= $minRAM ? 'met' : 'unmet' ?>">
                    <div class="req-icon">
                        <i class="fas fa-<?= $ram >= $minRAM ? 'check' : 'times' ?>-circle"></i>
                    </div>
                    <div class="req-details">
                        <h3>RAM</h3>
                        <div class="req-versions">
                            <span class="current-version"><?= $ram ?> GB</span>
                            <span class="vs">vs</span>
                            <span class="required-version">≥ <?= $minRAM ?> GB</span>
                        </div>
                        <?php if ($ram < $minRAM): ?>
                        <p class="req-message"><i class="fas fa-info-circle"></i> Increase your system memory</p>
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
                            <span class="current-version"><?= $dbService ? 'Running' : 'Not Running' ?></span>
                            <span class="vs">vs</span>
                            <span class="required-version">Running</span>
                        </div>
                        <?php if (!$dbService): ?>
                        <p class="req-message"><i class="fas fa-info-circle"></i> Start your MySQL service</p>
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
            <p>Upload your SQL file to execute in your XAMPP MySQL database</p>
            
            <?php if ($uploadError): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <p><?= htmlspecialchars($uploadError) ?></p>
            </div>
            <?php endif; ?>
            
            <form method="post" enctype="multipart/form-data" class="upload-form">
                <div class="form-group">
                    <label for="db_host">MySQL Server Address</label>
                    <input type="text" id="db_host" name="db_host" value="localhost" required>
                </div>
                
                <div class="form-group">
                    <label for="db_username">Username</label>
                    <input type="text" id="db_username" name="db_username" value="root" required>
                </div>
                
                <div class="form-group">
                    <label for="db_password">Password</label>
                    <input type="password" id="db_password" name="db_password">
                </div>
                
                <div class="form-group">
                    <label for="db_name">Database Name</label>
                    <input type="text" id="db_name" name="db_name" value="test1" required>
                </div>
                
                <div class="form-group">
                    <label for="sql_file">SQL File</label>
                    <input type="file" id="sql_file" name="sql_file" accept=".sql" required>
                </div>
                
                <div class="button-group">
                    <button type="submit" name="upload_sql" class="button primary">
                        <i class="fas fa-upload"></i> Upload & Execute
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
                <h3>Tables in Database:</h3>
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
            
            <form method="post" class="sql-editor-form">
                <div class="form-group">
                    <label for="sql_content">SQL Content</label>
                    <textarea id="sql_content" name="sql_content" rows="15" class="sql-editor"><?= 
                        htmlspecialchars($sqlContent) 
                    ?></textarea>
                </div>
                
                <input type="hidden" name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>">
                <input type="hidden" name="db_username" value="<?= htmlspecialchars($_POST['db_username'] ?? 'root') ?>">
                <input type="hidden" name="db_password" value="<?= htmlspecialchars($_POST['db_password'] ?? '') ?>">
                <input type="hidden" name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? 'test1') ?>">
                
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
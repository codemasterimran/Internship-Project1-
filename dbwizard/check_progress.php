<?php
session_start();

$pidFile = 'sql_execution.pid';
$outputFile = 'sql_execution.log';

// Check if process is still running
if (file_exists($pidFile)) {
    $pid = trim(file_get_contents($pidFile));
    
    // Check if process is running
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows
        $output = shell_exec("tasklist /FI \"PID eq $pid\" 2>NUL");
        $isRunning = strpos($output, 'mysql.exe') !== false;
    } else {
        // Linux/Unix
        $output = shell_exec("ps -p $pid");
        $isRunning = strpos($output, 'mysql') !== false;
    }
    
    if (!$isRunning) {
        // Process completed
        unlink($pidFile);
        
        // Check for errors
        if (file_exists($outputFile)) {
            $outputContent = file_get_contents($outputFile);
            if (strpos($outputContent, 'ERROR') !== false || strpos($outputContent, 'error') !== false) {
                echo json_encode(['error' => 'SQL execution failed. Check the log file.']);
                exit;
            }
        }
        
        echo json_encode(['completed' => true, 'progress' => 100]);
        exit;
    }
    
    // Estimate progress (this is just a simulation)
    $progress = min(100, intval(filemtime($pidFile) / 10)); // Simple progress estimation
    echo json_encode(['completed' => false, 'progress' => $progress]);
} else {
    echo json_encode(['error' => 'Process not found']);
}
?>
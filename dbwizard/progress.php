<?php
session_start();
header('Content-Type: application/json');

if (isset($_SESSION['progress'])) {
    echo json_encode($_SESSION['progress']);
} else {
    echo json_encode(['percent' => 0, 'processed' => 0, 'total' => 1, 'created' => [], 'skipped' => []]);
}
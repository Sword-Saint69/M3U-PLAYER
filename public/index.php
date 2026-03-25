<?php
// Route requests
$action = $_GET['action'] ?? '';

if ($action === 'proxy') {
    require_once '../src/proxy.php';
    exit;
}

if ($action === 'parse') {
    require_once '../src/parser.php';
    exit;
}

// Serve the main HTML player
readfile(__DIR__ . '/player.html');

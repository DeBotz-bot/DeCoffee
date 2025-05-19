<?php
require_once 'config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order'])) {
    $order = json_decode($_POST['order'], true);
    
    // Store the latest order in a file for polling
    file_put_contents('latest_order.json', $_POST['order']);
    
    echo json_encode(['success' => true]);
}
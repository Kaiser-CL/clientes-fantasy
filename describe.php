<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
require_once 'config/database.php';
$db = new Database();
$conn = $db->conectar();
$stmt = $conn->query("DESCRIBE usuarios");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

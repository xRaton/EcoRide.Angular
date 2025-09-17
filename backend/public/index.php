<?php
header("Access-Control-Allow-Origin: http://localhost:4200"); 
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../libs/Response.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Router.php';

<?php
// public/index.php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../libs/Response.php';
require_once __DIR__ . '/../libs/JwtHandler.php';

// controllers
require_once __DIR__ . '/../api/controllers/AuthController.php';
require_once __DIR__ . '/../api/controllers/UsersController.php';
require_once __DIR__ . '/../api/controllers/VehiclesController.php';
require_once __DIR__ . '/../api/controllers/RidesController.php';
require_once __DIR__ . '/../api/controllers/BookingsController.php';
require_once __DIR__ . '/../api/controllers/ReviewsController.php';
require_once __DIR__ . '/../api/controllers/AdminController.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
if($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];

$jwtHandler = new JwtHandler();

function getBearerToken(){
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $auth = $headers['Authorization'] ?? $headers['authorization'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? null);
    if($auth && preg_match('/Bearer\s(.*)$/i',$auth,$m)) return $m[1];
    return null;
}

function require_auth(){
    global $jwtHandler;
    $token = getBearerToken();
    if(!$token) Response::json(['error'=>'Token manquant'],401);
    $decoded = $jwtHandler->validate($token);
    if(!$decoded) Response::json(['error'=>'Token invalide ou expiré'],401);
    return $decoded; // object
}

// routing
// simple routing by path segments
$segments = array_values(array_filter(explode('/', $uri)));

try {
    // public routes
    if ($uri === '/api/register' && $method === 'POST') {
        (new AuthController())->register($input);
    } elseif ($uri === '/api/login' && $method === 'POST') {
        (new AuthController())->login($input);
    }
    // protected routes
    elseif (preg_match('#^/api/me$#',$uri) && $method === 'GET') {
        $user = require_auth(); (new UsersController())->me($user);
    }
    // vehicles
    elseif (preg_match('#^/api/vehicles$#',$uri)) {
        if($method === 'GET'){ $user = require_auth(); (new UsersController())->listVehicles($user); }
        elseif($method === 'POST'){ $user = require_auth(); (new UsersController())->addVehicle($user,$input); }
    } elseif (preg_match('#^/api/vehicles/(\d+)$#',$uri,$m) && $method === 'GET') {
        (new VehiclesController())->get((int)$m[1]);
    }
    // rides
    elseif (preg_match('#^/api/rides$#',$uri)) {
        if($method === 'GET'){ (new RidesController())->list($_GET); }
        elseif($method === 'POST'){ $user = require_auth(); (new RidesController())->create($user,$input); }
    } elseif (preg_match('#^/api/rides/(\d+)$#',$uri,$m) && $method === 'GET') {
        (new RidesController())->detail((int)$m[1]);
    } elseif (preg_match('#^/api/rides/(\d+)/start$#',$uri,$m) && $method === 'POST') {
        $user = require_auth(); (new RidesController())->start((int)$m[1], $user);
    } elseif (preg_match('#^/api/rides/(\d+)/finish$#',$uri,$m) && $method === 'POST') {
        $user = require_auth(); (new RidesController())->finish((int)$m[1], $user);
    } elseif (preg_match('#^/api/rides/(\d+)/cancel$#',$uri,$m) && $method === 'POST') {
        $user = require_auth(); (new RidesController())->cancel((int)$m[1], $user);
    }
    // bookings
    elseif (preg_match('#^/api/bookings/(\d+)/request$#',$uri,$m) && $method === 'POST') {
        $user = require_auth(); (new BookingsController())->requestJoin((int)$m[1], $user, $input);
    } elseif (preg_match('#^/api/bookings/(\d+)/confirm$#',$uri,$m) && $method === 'POST') {
        $user = require_auth(); (new BookingsController())->confirm((int)$m[1], $user);
    } elseif (preg_match('#^/api/bookings/(\d+)/complete$#',$uri,$m) && $method === 'POST') {
        $user = require_auth(); (new BookingsController())->complete((int)$m[1], $user);
    } elseif (preg_match('#^/api/bookings/(\d+)/cancel$#',$uri,$m) && $method === 'POST') {
        $user = require_auth(); (new BookingsController())->cancel((int)$m[1], $user);
    }
    // reviews
    elseif (preg_match('#^/api/reviews/(\d+)/add$#',$uri,$m) && $method === 'POST') {
        $user = require_auth(); (new ReviewsController())->add((int)$m[1], $user, $input);
    } elseif ($uri === '/api/reviews/pending' && $method === 'GET') {
        $user = require_auth(); (new ReviewsController())->pending($user);
    } elseif (preg_match('#^/api/reviews/(\d+)/moderate$#',$uri,$m) && $method === 'POST') {
        $user = require_auth(); (new ReviewsController())->moderate((int)$m[1], $user, $input);
    }
    // admin
    elseif (preg_match('#^/api/admin/create_employee$#',$uri,$m) && $method === 'POST') {
        $user = require_auth(); (new AdminController())->createEmployee($user, $input);
    } elseif (preg_match('#^/api/admin/(\d+)/suspend$#',$uri,$m) && $method === 'POST') {
        $user = require_auth(); (new AdminController())->suspend($user, (int)$m[1]);
    } elseif (preg_match('#^/api/admin/(\d+)/unsuspend$#',$uri,$m) && $method === 'POST') {
        $user = require_auth(); (new AdminController())->unsuspend($user, (int)$m[1]);
    } elseif (preg_match('#^/api/admin/stats$#',$uri,$m) && $method === 'GET') {
        $user = require_auth(); (new AdminController())->stats($user, $_GET['day'] ?? null);
    }
    else {
        http_response_code(404);
        echo json_encode(['error'=>'Route introuvable']);
    }
} catch (Exception $e){
    http_response_code(500);
    echo json_encode(['error'=>'Server error','details'=>$e->getMessage()]);
}


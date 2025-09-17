<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../libs/Response.php';
require_once __DIR__ . '/../config/Database.php';

$pdo = Database::getConnection();

// Récupère l'URL brute
$rawPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Supprime les éventuels préfixes /backend/public ou /public
$cleanPath = preg_replace('#^/(backend/)?public#', '', $cleanPath);

// Si rien, alors c'est la racine
if ($cleanPath === '' || $cleanPath === '/') {
    $path = '/';
} else {
    $path = $cleanPath;
}

$method = $_SERVER['REQUEST_METHOD'];


// Récupère le token Bearer du header Authorization
function getBearerToken() {
    $headers = getallheaders();
    if (!empty($headers['Authorization']) && preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
        return $matches[1];
    }
    return null;
}

// Vérifie le JWT et renvoie l'ID utilisateur
function checkAuth() {
    $token = getBearerToken();
    if (!$token) Response::json(['error' => 'Token manquant'], 401);

    try {
        $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));
        return $decoded->user_id;
    } catch (Exception $e) {
        Response::json(['error' => 'Token invalide'], 401);
    }
}

// -------------------- ROUTES ------------------------

switch ($path) {

    case '/':
        Response::json(['message' => 'API EcoRide en ligne 🚗']);
        break;

    // ----------- AUTH ---------------

    case '/api/register':
        if ($method !== 'POST') Response::json(['error' => 'Méthode non autorisée'], 405);

        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? null;
        $password = $input['password'] ?? null;

        if (!$email || !$password) Response::json(['error' => 'Champs manquants'], 400);

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) Response::json(['error' => 'Email déjà utilisé'], 400);

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
        $stmt->execute([$email, $hash]);

        Response::json(['message' => 'Utilisateur créé']);
        break;

    case '/api/login':
        if ($method !== 'POST') Response::json(['error' => 'Méthode non autorisée'], 405);

        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? null;
        $password = $input['password'] ?? null;

        if (!$email || !$password) Response::json(['error' => 'Champs manquants'], 400);

        $stmt = $pdo->prepare("SELECT id, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            Response::json(['error' => 'Identifiants invalides'], 401);
        }

        $payload = [
            'user_id' => $user['id'],
            'exp' => time() + 3600 // expire dans 1h
        ];
        $jwt = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

        Response::json(['token' => $jwt]);
        break;

    // ----------- TRAJETS CRUD ---------------

    case '/api/trajets':
        $userId = checkAuth();

        if ($method === 'GET') {
            $stmt = $pdo->query("SELECT * FROM trajets");
            Response::json($stmt->fetchAll(PDO::FETCH_ASSOC));
        }

        if ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $depart = $input['depart'] ?? null;
            $arrivee = $input['arrivee'] ?? null;
            $places = $input['places'] ?? null;
            $date = $input['date'] ?? null;

            if (!$depart || !$arrivee || !$places || !$date) {
                Response::json(['error' => 'Champs manquants'], 400);
            }

            $stmt = $pdo->prepare("INSERT INTO trajets (user_id, depart, arrivee, places, date) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $depart, $arrivee, $places, $date]);

            Response::json(['message' => 'Trajet créé']);
        }

        Response::json(['error' => 'Méthode non autorisée'], 405);
        break;

    default:
        Response::json(['error' => 'Route introuvable'], 404);
        break;
}

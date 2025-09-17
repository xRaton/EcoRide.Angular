<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once __DIR__ . '/../vendor/autoload.php';

class JwtHandler {
    private $secret;
    private $algo = 'HS256';

    public function __construct() {
        $this->secret = $_ENV['JWT_SECRET'] ?? 'change_this_secret';
    }

    public function generate($payload, $ttl = 3600) {
        $now = time();
        $token = array_merge(['iat' => $now, 'exp' => $now + $ttl], $payload);
        return JWT::encode($token, $this->secret, $this->algo);
    }

    public function validate($jwt) {
        try {
            $decoded = JWT::decode($jwt, new Key($this->secret, $this->algo));
            return $decoded;
        } catch (Exception $e) {
            return null;
        }
    }
}

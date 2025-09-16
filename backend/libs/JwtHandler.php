<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtHandler {
    private string $secret;
    private string $algo = 'HS256';

    public function __construct() {
        $this->secret = $_ENV['JWT_SECRET'] ?? 'change_this_secret';
    }

    public function generate(array $payload, int $ttl = 3600): string {
        $now = time();
        $token = array_merge([
            'iat' => $now,
            'exp' => $now + $ttl
        ], $payload);
        return JWT::encode($token, $this->secret, $this->algo);
    }

    public function validate(string $jwt) {
        try {
            $decoded = JWT::decode($jwt, new Key($this->secret, $this->algo));
            return $decoded;
        } catch (Exception $e) {
            return null;
        }
    }
}


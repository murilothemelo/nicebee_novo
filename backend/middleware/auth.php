<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$JWT_SECRET = 'your-super-secret-jwt-key-change-in-production';

function generateJWT($user_id, $email, $type) {
    global $JWT_SECRET;
    
    $payload = [
        'user_id' => $user_id,
        'email' => $email,
        'type' => $type,
        'iat' => time(),
        'exp' => time() + (24 * 60 * 60) // 24 hours
    ];
    
    return JWT::encode($payload, $JWT_SECRET, 'HS256');
}

function verifyJWT($token) {
    global $JWT_SECRET;
    
    try {
        $decoded = JWT::decode($token, new Key($JWT_SECRET, 'HS256'));
        return (array) $decoded;
    } catch (Exception $e) {
        return false;
    }
}

function requireAuth() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Token not provided']);
        exit();
    }
    
    $token = $matches[1];
    $decoded = verifyJWT($token);
    
    if (!$decoded) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid token']);
        exit();
    }
    
    return $decoded;
}

function getCurrentUser() {
    global $pdo;
    
    $auth = requireAuth();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$auth['user_id']]);
    return $stmt->fetch();
}

function hasPermission($requiredTypes) {
    $user = getCurrentUser();
    return in_array($user['type'], $requiredTypes);
}

function requirePermission($requiredTypes) {
    if (!hasPermission($requiredTypes)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
        exit();
    }
}
?>
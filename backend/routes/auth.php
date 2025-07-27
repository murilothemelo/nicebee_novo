<?php
function handleAuthRoutes($method, $id) {
    global $pdo;
    
    switch ($method) {
        case 'POST':
            if ($id === 'login') {
                handleLogin();
            } elseif ($id === 'logout') {
                handleLogout();
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Auth endpoint not found']);
            }
            break;
            
        case 'GET':
            if ($id === 'me') {
                handleMe();
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Auth endpoint not found']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
}

function handleLogin() {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email and password are required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($password, $user['password'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
            return;
        }
        
        $token = generateJWT($user['id'], $user['email'], $user['type']);
        
        // Remove password from response
        unset($user['password']);
        
        echo json_encode([
            'success' => true,
            'user' => $user,
            'token' => $token
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function handleLogout() {
    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
}

function handleMe() {
    $user = getCurrentUser();
    unset($user['password']);
    
    echo json_encode([
        'success' => true,
        'data' => $user
    ]);
}
?>
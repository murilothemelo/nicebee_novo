<?php
function handleUserRoutes($method, $id) {
    global $pdo;
    
    switch ($method) {
        case 'GET':
            if ($id) {
                getUserById($id);
            } else {
                getAllUsers();
            }
            break;
            
        case 'POST':
            createUser();
            break;
            
        case 'PUT':
            if ($id) {
                updateUser($id);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'User ID required']);
            }
            break;
            
        case 'DELETE':
            if ($id) {
                deleteUser($id);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'User ID required']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
}

function getAllUsers() {
    global $pdo;
    requirePermission(['admin', 'assistant']);
    
    try {
        $stmt = $pdo->query("SELECT id, name, email, type, phone, specialty, created_at, updated_at FROM users ORDER BY created_at DESC");
        $users = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $users
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function getUserById($id) {
    global $pdo;
    requirePermission(['admin', 'assistant']);
    
    try {
        $stmt = $pdo->prepare("SELECT id, name, email, type, phone, specialty, created_at, updated_at FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'User not found']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $user
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function createUser() {
    global $pdo;
    requirePermission(['admin', 'assistant']);
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $required_fields = ['name', 'email', 'password', 'type'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Field $field is required"]);
            return;
        }
    }
    
    try {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$input['email']]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
            return;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, type, phone, specialty) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $hashed_password = password_hash($input['password'], PASSWORD_DEFAULT);
        
        $stmt->execute([
            $input['name'],
            $input['email'],
            $hashed_password,
            $input['type'],
            $input['phone'] ?? null,
            $input['specialty'] ?? null
        ]);
        
        $user_id = $pdo->lastInsertId();
        
        // Get created user
        $stmt = $pdo->prepare("SELECT id, name, email, type, phone, specialty, created_at, updated_at FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'data' => $user,
            'message' => 'User created successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function updateUser($id) {
    global $pdo;
    $current_user = getCurrentUser();
    
    // Users can only edit their own profile unless they're admin/assistant
    if ($current_user['id'] != $id && !hasPermission(['admin', 'assistant'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Can only edit your own profile']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    try {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'User not found']);
            return;
        }
        
        // Build update query dynamically
        $update_fields = [];
        $params = [];
        
        if (isset($input['name'])) {
            $update_fields[] = 'name = ?';
            $params[] = $input['name'];
        }
        
        if (isset($input['email'])) {
            // Check if email already exists for other users
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$input['email'], $id]);
            if ($stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Email already exists']);
                return;
            }
            
            $update_fields[] = 'email = ?';
            $params[] = $input['email'];
        }
        
        if (isset($input['password']) && !empty($input['password'])) {
            $update_fields[] = 'password = ?';
            $params[] = password_hash($input['password'], PASSWORD_DEFAULT);
        }
        
        if (isset($input['type']) && hasPermission(['admin', 'assistant'])) {
            $update_fields[] = 'type = ?';
            $params[] = $input['type'];
        }
        
        if (isset($input['phone'])) {
            $update_fields[] = 'phone = ?';
            $params[] = $input['phone'];
        }
        
        if (isset($input['specialty'])) {
            $update_fields[] = 'specialty = ?';
            $params[] = $input['specialty'];
        }
        
        if (empty($update_fields)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No fields to update']);
            return;
        }
        
        $params[] = $id;
        $sql = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        // Get updated user
        $stmt = $pdo->prepare("SELECT id, name, email, type, phone, specialty, created_at, updated_at FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'data' => $user,
            'message' => 'User updated successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function deleteUser($id) {
    global $pdo;
    requirePermission(['admin', 'assistant']);
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'User not found']);
            return;
        }
        
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
?>
<?php
function handleCompanionRoutes($method, $id) {
    switch ($method) {
        case 'GET':
            if ($id) {
                getCompanionById($id);
            } else {
                getAllCompanions();
            }
            break;
            
        case 'POST':
            createCompanion();
            break;
            
        case 'PUT':
            if ($id) {
                updateCompanion($id);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Companion ID required']);
            }
            break;
            
        case 'DELETE':
            if ($id) {
                deleteCompanion($id);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Companion ID required']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
}

function getAllCompanions() {
    global $pdo;
    $current_user = getCurrentUser();
    
    try {
        $sql = "
            SELECT c.*, p.name as patient_name
            FROM companions c
            LEFT JOIN patients p ON c.patient_id = p.id
        ";
        
        $params = [];
        
        // Professionals can only see companions related to their patients
        if ($current_user['type'] === 'professional') {
            $sql .= " WHERE p.responsible_id = ? OR c.patient_id IS NULL";
            $params[] = $current_user['id'];
        }
        
        $sql .= " ORDER BY c.name ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $companions = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $companions
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function getCompanionById($id) {
    global $pdo;
    $current_user = getCurrentUser();
    
    try {
        $sql = "
            SELECT c.*, p.name as patient_name
            FROM companions c
            LEFT JOIN patients p ON c.patient_id = p.id
            WHERE c.id = ?
        ";
        
        $params = [$id];
        
        // Professionals can only see companions related to their patients
        if ($current_user['type'] === 'professional') {
            $sql .= " AND (p.responsible_id = ? OR c.patient_id IS NULL)";
            $params[] = $current_user['id'];
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $companion = $stmt->fetch();
        
        if (!$companion) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Companion not found']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $companion
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function createCompanion() {
    global $pdo;
    $current_user = getCurrentUser();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['name'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Name is required']);
        return;
    }
    
    // Check if user can assign companion to this patient
    if (isset($input['patient_id']) && $current_user['type'] === 'professional') {
        $stmt = $pdo->prepare("SELECT responsible_id FROM patients WHERE id = ?");
        $stmt->execute([$input['patient_id']]);
        $patient = $stmt->fetch();
        
        if (!$patient || $patient['responsible_id'] != $current_user['id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Can only assign companions to your own patients']);
            return;
        }
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO companions (name, phone, email, patient_id) 
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $input['name'],
            $input['phone'] ?? null,
            $input['email'] ?? null,
            $input['patient_id'] ?? null
        ]);
        
        $companion_id = $pdo->lastInsertId();
        
        // Get created companion with relationships
        $stmt = $pdo->prepare("
            SELECT c.*, p.name as patient_name
            FROM companions c
            LEFT JOIN patients p ON c.patient_id = p.id
            WHERE c.id = ?
        ");
        $stmt->execute([$companion_id]);
        $companion = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'data' => $companion,
            'message' => 'Companion created successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function updateCompanion($id) {
    global $pdo;
    $current_user = getCurrentUser();
    
    // Check if user can edit this companion
    if ($current_user['type'] === 'professional') {
        $stmt = $pdo->prepare("
            SELECT c.*, p.responsible_id 
            FROM companions c 
            LEFT JOIN patients p ON c.patient_id = p.id 
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        $companion = $stmt->fetch();
        
        if (!$companion) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Companion not found']);
            return;
        }
        
        if ($companion['patient_id'] && $companion['responsible_id'] != $current_user['id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Can only edit companions related to your own patients']);
            return;
        }
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    try {
        // Check if companion exists
        $stmt = $pdo->prepare("SELECT id FROM companions WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Companion not found']);
            return;
        }
        
        // Build update query dynamically
        $update_fields = [];
        $params = [];
        
        $allowed_fields = ['name', 'phone', 'email', 'patient_id'];
        
        foreach ($allowed_fields as $field) {
            if (isset($input[$field])) {
                // Check patient assignment permission
                if ($field === 'patient_id' && $input[$field] && $current_user['type'] === 'professional') {
                    $stmt = $pdo->prepare("SELECT responsible_id FROM patients WHERE id = ?");
                    $stmt->execute([$input[$field]]);
                    $patient = $stmt->fetch();
                    
                    if (!$patient || $patient['responsible_id'] != $current_user['id']) {
                        http_response_code(403);
                        echo json_encode(['success' => false, 'message' => 'Can only assign companions to your own patients']);
                        return;
                    }
                }
                
                $update_fields[] = "$field = ?";
                $params[] = $input[$field];
            }
        }
        
        if (empty($update_fields)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No fields to update']);
            return;
        }
        
        $params[] = $id;
        $sql = "UPDATE companions SET " . implode(', ', $update_fields) . " WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        // Get updated companion
        $stmt = $pdo->prepare("
            SELECT c.*, p.name as patient_name
            FROM companions c
            LEFT JOIN patients p ON c.patient_id = p.id
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        $companion = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'data' => $companion,
            'message' => 'Companion updated successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function deleteCompanion($id) {
    global $pdo;
    $current_user = getCurrentUser();
    
    // Only admin and assistant can delete companions
    if (!hasPermission(['admin', 'assistant'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM companions WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Companion not found']);
            return;
        }
        
        $stmt = $pdo->prepare("DELETE FROM companions WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Companion deleted successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
?>
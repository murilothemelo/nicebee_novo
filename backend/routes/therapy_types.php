<?php
function handleTherapyTypeRoutes($method, $id) {
    switch ($method) {
        case 'GET':
            if ($id) {
                getTherapyTypeById($id);
            } else {
                getAllTherapyTypes();
            }
            break;
            
        case 'POST':
            createTherapyType();
            break;
            
        case 'PUT':
            if ($id) {
                updateTherapyType($id);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Therapy type ID required']);
            }
            break;
            
        case 'DELETE':
            if ($id) {
                deleteTherapyType($id);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Therapy type ID required']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
}

function getAllTherapyTypes() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT * FROM therapy_types ORDER BY specialty ASC, name ASC");
        $types = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $types
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function getTherapyTypeById($id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM therapy_types WHERE id = ?");
        $stmt->execute([$id]);
        $type = $stmt->fetch();
        
        if (!$type) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Therapy type not found']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $type
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function createTherapyType() {
    global $pdo;
    requirePermission(['admin', 'assistant']);
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $required_fields = ['name', 'specialty'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Field $field is required"]);
            return;
        }
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO therapy_types (name, description, specialty) 
            VALUES (?, ?, ?)
        ");
        
        $stmt->execute([
            $input['name'],
            $input['description'] ?? null,
            $input['specialty']
        ]);
        
        $type_id = $pdo->lastInsertId();
        
        // Get created type
        $stmt = $pdo->prepare("SELECT * FROM therapy_types WHERE id = ?");
        $stmt->execute([$type_id]);
        $type = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'data' => $type,
            'message' => 'Therapy type created successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function updateTherapyType($id) {
    global $pdo;
    requirePermission(['admin', 'assistant']);
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    try {
        // Check if type exists
        $stmt = $pdo->prepare("SELECT id FROM therapy_types WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Therapy type not found']);
            return;
        }
        
        // Build update query dynamically
        $update_fields = [];
        $params = [];
        
        $allowed_fields = ['name', 'description', 'specialty'];
        
        foreach ($allowed_fields as $field) {
            if (isset($input[$field])) {
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
        $sql = "UPDATE therapy_types SET " . implode(', ', $update_fields) . " WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        // Get updated type
        $stmt = $pdo->prepare("SELECT * FROM therapy_types WHERE id = ?");
        $stmt->execute([$id]);
        $type = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'data' => $type,
            'message' => 'Therapy type updated successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function deleteTherapyType($id) {
    global $pdo;
    requirePermission(['admin', 'assistant']);
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM therapy_types WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Therapy type not found']);
            return;
        }
        
        $stmt = $pdo->prepare("DELETE FROM therapy_types WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Therapy type deleted successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
?>
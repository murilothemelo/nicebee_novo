<?php
function handleCommunityRoutes($method, $id, $action) {
    switch ($method) {
        case 'GET':
            if ($action === 'patient' && $id) {
                getCommunityByPatient($id);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid community endpoint']);
            }
            break;
            
        case 'POST':
            createCommunityMessage();
            break;
            
        case 'DELETE':
            if ($id) {
                deleteCommunityMessage($id);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Message ID required']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
}

function getCommunityByPatient($patient_id) {
    global $pdo;
    $current_user = getCurrentUser();
    
    // Check if user can access this patient's community
    if ($current_user['type'] === 'professional') {
        $stmt = $pdo->prepare("SELECT responsible_id FROM patients WHERE id = ?");
        $stmt->execute([$patient_id]);
        $patient = $stmt->fetch();
        
        if (!$patient || $patient['responsible_id'] != $current_user['id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Can only access your own patient community']);
            return;
        }
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, u.name as professional_name, u.specialty as professional_specialty
            FROM community c
            LEFT JOIN users u ON c.professional_id = u.id
            WHERE c.patient_id = ?
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$patient_id]);
        $messages = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $messages
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function createCommunityMessage() {
    global $pdo;
    $current_user = getCurrentUser();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $required_fields = ['patient_id', 'message'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Field $field is required"]);
            return;
        }
    }
    
    // Check if user can post to this patient's community
    if ($current_user['type'] === 'professional') {
        $stmt = $pdo->prepare("SELECT responsible_id FROM patients WHERE id = ?");
        $stmt->execute([$input['patient_id']]);
        $patient = $stmt->fetch();
        
        if (!$patient || $patient['responsible_id'] != $current_user['id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Can only post to your own patient community']);
            return;
        }
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO community (patient_id, professional_id, message) 
            VALUES (?, ?, ?)
        ");
        
        $stmt->execute([
            $input['patient_id'],
            $current_user['id'],
            $input['message']
        ]);
        
        $message_id = $pdo->lastInsertId();
        
        // Get created message with relationships
        $stmt = $pdo->prepare("
            SELECT c.*, u.name as professional_name, u.specialty as professional_specialty
            FROM community c
            LEFT JOIN users u ON c.professional_id = u.id
            WHERE c.id = ?
        ");
        $stmt->execute([$message_id]);
        $message = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'data' => $message,
            'message' => 'Community message created successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function deleteCommunityMessage($id) {
    global $pdo;
    $current_user = getCurrentUser();
    
    // Check if user can delete this message
    $stmt = $pdo->prepare("SELECT professional_id FROM community WHERE id = ?");
    $stmt->execute([$id]);
    $message = $stmt->fetch();
    
    if (!$message) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Message not found']);
        return;
    }
    
    if ($current_user['type'] === 'professional' && $message['professional_id'] != $current_user['id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Can only delete your own messages']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM community WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Community message deleted successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
?>
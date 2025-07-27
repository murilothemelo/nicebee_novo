<?php
function handleInsurancePlanRoutes($method, $id) {
    switch ($method) {
        case 'GET':
            if ($id) {
                getInsurancePlanById($id);
            } else {
                getAllInsurancePlans();
            }
            break;
            
        case 'POST':
            createInsurancePlan();
            break;
            
        case 'PUT':
            if ($id) {
                updateInsurancePlan($id);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Insurance plan ID required']);
            }
            break;
            
        case 'DELETE':
            if ($id) {
                deleteInsurancePlan($id);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Insurance plan ID required']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
}

function getAllInsurancePlans() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT * FROM insurance_plans ORDER BY name ASC");
        $plans = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $plans
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function getInsurancePlanById($id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM insurance_plans WHERE id = ?");
        $stmt->execute([$id]);
        $plan = $stmt->fetch();
        
        if (!$plan) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Insurance plan not found']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $plan
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function createInsurancePlan() {
    global $pdo;
    requirePermission(['admin', 'assistant']);
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['name'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Name is required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO insurance_plans (name, psychology_value, physiotherapy_value, occupational_therapy_value, speech_therapy_value, phone, email, start_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $input['name'],
            $input['psychology_value'] ?? 0.00,
            $input['physiotherapy_value'] ?? 0.00,
            $input['occupational_therapy_value'] ?? 0.00,
            $input['speech_therapy_value'] ?? 0.00,
            $input['phone'] ?? null,
            $input['email'] ?? null,
            $input['start_date'] ?? null
        ]);
        
        $plan_id = $pdo->lastInsertId();
        
        // Get created plan
        $stmt = $pdo->prepare("SELECT * FROM insurance_plans WHERE id = ?");
        $stmt->execute([$plan_id]);
        $plan = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'data' => $plan,
            'message' => 'Insurance plan created successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function updateInsurancePlan($id) {
    global $pdo;
    requirePermission(['admin', 'assistant']);
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    try {
        // Check if plan exists
        $stmt = $pdo->prepare("SELECT id FROM insurance_plans WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Insurance plan not found']);
            return;
        }
        
        // Build update query dynamically
        $update_fields = [];
        $params = [];
        
        $allowed_fields = ['name', 'psychology_value', 'physiotherapy_value', 'occupational_therapy_value', 'speech_therapy_value', 'phone', 'email', 'start_date'];
        
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
        $sql = "UPDATE insurance_plans SET " . implode(', ', $update_fields) . " WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        // Get updated plan
        $stmt = $pdo->prepare("SELECT * FROM insurance_plans WHERE id = ?");
        $stmt->execute([$id]);
        $plan = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'data' => $plan,
            'message' => 'Insurance plan updated successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function deleteInsurancePlan($id) {
    global $pdo;
    requirePermission(['admin', 'assistant']);
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM insurance_plans WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Insurance plan not found']);
            return;
        }
        
        $stmt = $pdo->prepare("DELETE FROM insurance_plans WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Insurance plan deleted successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
?>
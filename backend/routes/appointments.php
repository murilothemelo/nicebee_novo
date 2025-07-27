<?php
function handleAppointmentRoutes($method, $id) {
    switch ($method) {
        case 'GET':
            if ($id) {
                getAppointmentById($id);
            } else {
                getAllAppointments();
            }
            break;
            
        case 'POST':
            createAppointment();
            break;
            
        case 'PUT':
            if ($id) {
                updateAppointment($id);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Appointment ID required']);
            }
            break;
            
        case 'DELETE':
            if ($id) {
                deleteAppointment($id);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Appointment ID required']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
}

function getAllAppointments() {
    global $pdo;
    $current_user = getCurrentUser();
    
    try {
        $sql = "
            SELECT a.*, p.name as patient_name, u.name as professional_name, tt.name as therapy_type
            FROM appointments a
            LEFT JOIN patients p ON a.patient_id = p.id
            LEFT JOIN users u ON a.professional_id = u.id
            LEFT JOIN therapy_types tt ON a.therapy_type_id = tt.id
        ";
        
        $params = [];
        
        // Professionals can only see their own appointments
        if ($current_user['type'] === 'professional') {
            $sql .= " WHERE a.professional_id = ?";
            $params[] = $current_user['id'];
        }
        
        $sql .= " ORDER BY a.date DESC, a.time DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $appointments = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $appointments
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function getAppointmentById($id) {
    global $pdo;
    $current_user = getCurrentUser();
    
    try {
        $sql = "
            SELECT a.*, p.name as patient_name, u.name as professional_name, tt.name as therapy_type
            FROM appointments a
            LEFT JOIN patients p ON a.patient_id = p.id
            LEFT JOIN users u ON a.professional_id = u.id
            LEFT JOIN therapy_types tt ON a.therapy_type_id = tt.id
            WHERE a.id = ?
        ";
        
        $params = [$id];
        
        // Professionals can only see their own appointments
        if ($current_user['type'] === 'professional') {
            $sql .= " AND a.professional_id = ?";
            $params[] = $current_user['id'];
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $appointment = $stmt->fetch();
        
        if (!$appointment) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Appointment not found']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $appointment
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function createAppointment() {
    global $pdo;
    requirePermission(['admin', 'assistant']);
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $required_fields = ['patient_id', 'professional_id', 'therapy_type_id', 'date', 'time'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Field $field is required"]);
            return;
        }
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO appointments (patient_id, professional_id, therapy_type_id, date, time, frequency, status, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $input['patient_id'],
            $input['professional_id'],
            $input['therapy_type_id'],
            $input['date'],
            $input['time'],
            $input['frequency'] ?? 'single',
            $input['status'] ?? 'scheduled',
            $input['notes'] ?? null
        ]);
        
        $appointment_id = $pdo->lastInsertId();
        
        // Get created appointment with relationships
        $stmt = $pdo->prepare("
            SELECT a.*, p.name as patient_name, u.name as professional_name, tt.name as therapy_type
            FROM appointments a
            LEFT JOIN patients p ON a.patient_id = p.id
            LEFT JOIN users u ON a.professional_id = u.id
            LEFT JOIN therapy_types tt ON a.therapy_type_id = tt.id
            WHERE a.id = ?
        ");
        $stmt->execute([$appointment_id]);
        $appointment = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'data' => $appointment,
            'message' => 'Appointment created successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function updateAppointment($id) {
    global $pdo;
    $current_user = getCurrentUser();
    
    // Check if user can edit this appointment
    if ($current_user['type'] === 'professional') {
        $stmt = $pdo->prepare("SELECT professional_id FROM appointments WHERE id = ?");
        $stmt->execute([$id]);
        $appointment = $stmt->fetch();
        
        if (!$appointment || $appointment['professional_id'] != $current_user['id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Can only edit your own appointments']);
            return;
        }
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    try {
        // Check if appointment exists
        $stmt = $pdo->prepare("SELECT id FROM appointments WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Appointment not found']);
            return;
        }
        
        // Build update query dynamically
        $update_fields = [];
        $params = [];
        
        $allowed_fields = ['patient_id', 'professional_id', 'therapy_type_id', 'date', 'time', 'frequency', 'status', 'notes'];
        
        // Professionals can only update status and notes
        if ($current_user['type'] === 'professional') {
            $allowed_fields = ['status', 'notes'];
        }
        
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
        $sql = "UPDATE appointments SET " . implode(', ', $update_fields) . " WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        // Get updated appointment
        $stmt = $pdo->prepare("
            SELECT a.*, p.name as patient_name, u.name as professional_name, tt.name as therapy_type
            FROM appointments a
            LEFT JOIN patients p ON a.patient_id = p.id
            LEFT JOIN users u ON a.professional_id = u.id
            LEFT JOIN therapy_types tt ON a.therapy_type_id = tt.id
            WHERE a.id = ?
        ");
        $stmt->execute([$id]);
        $appointment = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'data' => $appointment,
            'message' => 'Appointment updated successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function deleteAppointment($id) {
    global $pdo;
    requirePermission(['admin', 'assistant']);
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM appointments WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Appointment not found']);
            return;
        }
        
        $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Appointment deleted successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
?>
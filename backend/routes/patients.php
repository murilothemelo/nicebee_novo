<?php
function handlePatientRoutes($method, $id, $action) {
    switch ($method) {
        case 'GET':
            if ($id && $action === 'medical-records') {
                getPatientMedicalRecords($id);
            } elseif ($id) {
                getPatientById($id);
            } else {
                getAllPatients();
            }
            break;
            
        case 'POST':
            if ($id && $action === 'medical-records') {
                uploadMedicalRecord($id);
            } else {
                createPatient();
            }
            break;
            
        case 'PUT':
            if ($id) {
                updatePatient($id);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Patient ID required']);
            }
            break;
            
        case 'DELETE':
            if ($id) {
                deletePatient($id);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Patient ID required']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
}

function getAllPatients() {
    global $pdo;
    $current_user = getCurrentUser();
    
    try {
        $sql = "
            SELECT p.*, u.name as responsible_name, ip.name as insurance_plan_name
            FROM patients p
            LEFT JOIN users u ON p.responsible_id = u.id
            LEFT JOIN insurance_plans ip ON p.insurance_plan_id = ip.id
        ";
        
        $params = [];
        
        // Professionals can only see their own patients
        if ($current_user['type'] === 'professional') {
            $sql .= " WHERE p.responsible_id = ?";
            $params[] = $current_user['id'];
        }
        
        $sql .= " ORDER BY p.created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $patients = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $patients
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function getPatientById($id) {
    global $pdo;
    $current_user = getCurrentUser();
    
    try {
        $sql = "
            SELECT p.*, u.name as responsible_name, ip.name as insurance_plan_name
            FROM patients p
            LEFT JOIN users u ON p.responsible_id = u.id
            LEFT JOIN insurance_plans ip ON p.insurance_plan_id = ip.id
            WHERE p.id = ?
        ";
        
        $params = [$id];
        
        // Professionals can only see their own patients
        if ($current_user['type'] === 'professional') {
            $sql .= " AND p.responsible_id = ?";
            $params[] = $current_user['id'];
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $patient = $stmt->fetch();
        
        if (!$patient) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Patient not found']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $patient
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function createPatient() {
    global $pdo;
    $current_user = getCurrentUser();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $required_fields = ['name', 'birth_date', 'category', 'gender'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Field $field is required"]);
            return;
        }
    }
    
    try {
        // Set responsible_id to current user for professionals, or use provided value for admin/assistant
        $responsible_id = $current_user['id'];
        if (hasPermission(['admin', 'assistant']) && isset($input['responsible_id'])) {
            $responsible_id = $input['responsible_id'];
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO patients (name, birth_date, category, gender, phone, email, address, responsible_id, insurance_plan_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $input['name'],
            $input['birth_date'],
            $input['category'],
            $input['gender'],
            $input['phone'] ?? null,
            $input['email'] ?? null,
            $input['address'] ?? null,
            $responsible_id,
            $input['insurance_plan_id'] ?? null
        ]);
        
        $patient_id = $pdo->lastInsertId();
        
        // Get created patient with relationships
        $stmt = $pdo->prepare("
            SELECT p.*, u.name as responsible_name, ip.name as insurance_plan_name
            FROM patients p
            LEFT JOIN users u ON p.responsible_id = u.id
            LEFT JOIN insurance_plans ip ON p.insurance_plan_id = ip.id
            WHERE p.id = ?
        ");
        $stmt->execute([$patient_id]);
        $patient = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'data' => $patient,
            'message' => 'Patient created successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function updatePatient($id) {
    global $pdo;
    $current_user = getCurrentUser();
    
    // Check if user can edit this patient
    $stmt = $pdo->prepare("SELECT responsible_id FROM patients WHERE id = ?");
    $stmt->execute([$id]);
    $patient = $stmt->fetch();
    
    if (!$patient) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Patient not found']);
        return;
    }
    
    if ($current_user['type'] === 'professional' && $patient['responsible_id'] != $current_user['id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Can only edit your own patients']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    try {
        // Build update query dynamically
        $update_fields = [];
        $params = [];
        
        $allowed_fields = ['name', 'birth_date', 'category', 'gender', 'phone', 'email', 'address', 'insurance_plan_id'];
        
        if (hasPermission(['admin', 'assistant'])) {
            $allowed_fields[] = 'responsible_id';
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
        $sql = "UPDATE patients SET " . implode(', ', $update_fields) . " WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        // Get updated patient
        $stmt = $pdo->prepare("
            SELECT p.*, u.name as responsible_name, ip.name as insurance_plan_name
            FROM patients p
            LEFT JOIN users u ON p.responsible_id = u.id
            LEFT JOIN insurance_plans ip ON p.insurance_plan_id = ip.id
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        $patient = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'data' => $patient,
            'message' => 'Patient updated successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function deletePatient($id) {
    global $pdo;
    $current_user = getCurrentUser();
    
    // Only admin and assistant can delete patients
    if (!hasPermission(['admin', 'assistant'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM patients WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Patient not found']);
            return;
        }
        
        $stmt = $pdo->prepare("DELETE FROM patients WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Patient deleted successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function getPatientMedicalRecords($patient_id) {
    global $pdo;
    $current_user = getCurrentUser();
    
    // Check if user can access this patient's records
    if ($current_user['type'] === 'professional') {
        $stmt = $pdo->prepare("SELECT responsible_id FROM patients WHERE id = ?");
        $stmt->execute([$patient_id]);
        $patient = $stmt->fetch();
        
        if (!$patient || $patient['responsible_id'] != $current_user['id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Can only access your own patient records']);
            return;
        }
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT mr.*, u.name as professional_name
            FROM medical_records mr
            LEFT JOIN users u ON mr.professional_id = u.id
            WHERE mr.patient_id = ?
            ORDER BY mr.created_at DESC
        ");
        $stmt->execute([$patient_id]);
        $records = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $records
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function uploadMedicalRecord($patient_id) {
    global $pdo;
    $current_user = getCurrentUser();
    
    // Check if user can upload to this patient
    if ($current_user['type'] === 'professional') {
        $stmt = $pdo->prepare("SELECT responsible_id FROM patients WHERE id = ?");
        $stmt->execute([$patient_id]);
        $patient = $stmt->fetch();
        
        if (!$patient || $patient['responsible_id'] != $current_user['id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Can only upload to your own patients']);
            return;
        }
    }
    
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'File upload failed']);
        return;
    }
    
    $type = $_POST['type'] ?? '';
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    
    if (empty($type) || empty($title)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Type and title are required']);
        return;
    }
    
    try {
        // Create uploads directory if it doesn't exist
        $upload_dir = 'uploads/medical_records/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file = $_FILES['file'];
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;
        
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to save file']);
            return;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO medical_records (patient_id, professional_id, type, title, description, file_path, file_name) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $patient_id,
            $current_user['id'],
            $type,
            $title,
            $description,
            $file_path,
            $file['name']
        ]);
        
        $record_id = $pdo->lastInsertId();
        
        // Get created record
        $stmt = $pdo->prepare("
            SELECT mr.*, u.name as professional_name
            FROM medical_records mr
            LEFT JOIN users u ON mr.professional_id = u.id
            WHERE mr.id = ?
        ");
        $stmt->execute([$record_id]);
        $record = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'data' => $record,
            'message' => 'Medical record uploaded successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
?>
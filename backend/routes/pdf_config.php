<?php
function handlePDFConfigRoutes($method, $action) {
    switch ($method) {
        case 'GET':
            getPDFConfig();
            break;
            
        case 'PUT':
            updatePDFConfig();
            break;
            
        case 'POST':
            if ($action === 'logo') {
                uploadLogo();
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'PDF config endpoint not found']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
}

function getPDFConfig() {
    global $pdo;
    $current_user = getCurrentUser();
    
    // Only admins can access PDF config
    if ($current_user['type'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Only admins can access PDF configuration']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM pdf_config WHERE admin_id = ?");
        $stmt->execute([$current_user['id']]);
        $config = $stmt->fetch();
        
        if (!$config) {
            // Create default config
            $stmt = $pdo->prepare("
                INSERT INTO pdf_config (admin_id, clinic_name, clinic_address, font_family, font_size, primary_color, show_description, show_observations, show_professional, show_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $current_user['id'],
                'Clínica Multidisciplinar',
                '',
                'Arial',
                12,
                '#2563EB',
                true,
                true,
                true,
                true
            ]);
            
            $config_id = $pdo->lastInsertId();
            
            $stmt = $pdo->prepare("SELECT * FROM pdf_config WHERE id = ?");
            $stmt->execute([$config_id]);
            $config = $stmt->fetch();
        }
        
        echo json_encode([
            'success' => true,
            'data' => $config
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function updatePDFConfig() {
    global $pdo;
    $current_user = getCurrentUser();
    
    // Only admins can update PDF config
    if ($current_user['type'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Only admins can update PDF configuration']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    try {
        // Check if config exists
        $stmt = $pdo->prepare("SELECT id FROM pdf_config WHERE admin_id = ?");
        $stmt->execute([$current_user['id']]);
        $config = $stmt->fetch();
        
        if (!$config) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'PDF config not found']);
            return;
        }
        
        // Build update query dynamically
        $update_fields = [];
        $params = [];
        
        $allowed_fields = [
            'clinic_name', 'clinic_address', 'header_text', 'footer_text', 
            'font_family', 'font_size', 'primary_color', 'show_description', 
            'show_observations', 'show_professional', 'show_date'
        ];
        
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
        
        $params[] = $config['id'];
        $sql = "UPDATE pdf_config SET " . implode(', ', $update_fields) . " WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        // Get updated config
        $stmt = $pdo->prepare("SELECT * FROM pdf_config WHERE id = ?");
        $stmt->execute([$config['id']]);
        $updated_config = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'data' => $updated_config,
            'message' => 'PDF configuration updated successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function uploadLogo() {
    global $pdo;
    $current_user = getCurrentUser();
    
    // Only admins can upload logo
    if ($current_user['type'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Only admins can upload logo']);
        return;
    }
    
    if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Logo upload failed']);
        return;
    }
    
    try {
        // Validate image file
        $file = $_FILES['logo'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        
        if (!in_array($file['type'], $allowed_types)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Only JPEG, PNG and GIF images are allowed']);
            return;
        }
        
        // Create uploads directory if it doesn't exist
        $upload_dir = 'uploads/logos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $file_name = 'logo_' . $current_user['id'] . '_' . uniqid() . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;
        
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to save logo']);
            return;
        }
        
        // Update PDF config with logo path
        $stmt = $pdo->prepare("UPDATE pdf_config SET logo_path = ? WHERE admin_id = ?");
        $stmt->execute([$file_path, $current_user['id']]);
        
        echo json_encode([
            'success' => true,
            'data' => ['logo_path' => $file_path],
            'message' => 'Logo uploaded successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
?>
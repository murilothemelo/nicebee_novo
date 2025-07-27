<?php
function handleEvolutionRoutes($method, $id, $action) {
    switch ($method) {
        case 'GET':
            if ($id && $action === 'pdf') {
                exportEvolutionPDF($id);
            } elseif ($action === 'patient' && $id) {
                getEvolutionsByPatient($id);
            } elseif ($id) {
                getEvolutionById($id);
            } else {
                getAllEvolutions();
            }
            break;
            
        case 'POST':
            createEvolution();
            break;
            
        case 'PUT':
            if ($id) {
                updateEvolution($id);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Evolution ID required']);
            }
            break;
            
        case 'DELETE':
            if ($id) {
                deleteEvolution($id);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Evolution ID required']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
}

function getAllEvolutions() {
    global $pdo;
    $current_user = getCurrentUser();
    
    try {
        $sql = "
            SELECT e.*, p.name as patient_name, u.name as professional_name
            FROM evolutions e
            LEFT JOIN patients p ON e.patient_id = p.id
            LEFT JOIN users u ON e.professional_id = u.id
        ";
        
        $params = [];
        
        // Professionals can only see their own evolutions
        if ($current_user['type'] === 'professional') {
            $sql .= " WHERE e.professional_id = ?";
            $params[] = $current_user['id'];
        }
        
        $sql .= " ORDER BY e.date DESC, e.created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $evolutions = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $evolutions
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function getEvolutionById($id) {
    global $pdo;
    $current_user = getCurrentUser();
    
    try {
        $sql = "
            SELECT e.*, p.name as patient_name, u.name as professional_name
            FROM evolutions e
            LEFT JOIN patients p ON e.patient_id = p.id
            LEFT JOIN users u ON e.professional_id = u.id
            WHERE e.id = ?
        ";
        
        $params = [$id];
        
        // Professionals can only see their own evolutions
        if ($current_user['type'] === 'professional') {
            $sql .= " AND e.professional_id = ?";
            $params[] = $current_user['id'];
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $evolution = $stmt->fetch();
        
        if (!$evolution) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Evolution not found']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $evolution
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function getEvolutionsByPatient($patient_id) {
    global $pdo;
    $current_user = getCurrentUser();
    
    // Check if user can access this patient's evolutions
    if ($current_user['type'] === 'professional') {
        $stmt = $pdo->prepare("SELECT responsible_id FROM patients WHERE id = ?");
        $stmt->execute([$patient_id]);
        $patient = $stmt->fetch();
        
        if (!$patient || $patient['responsible_id'] != $current_user['id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Can only access your own patient evolutions']);
            return;
        }
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT e.*, p.name as patient_name, u.name as professional_name
            FROM evolutions e
            LEFT JOIN patients p ON e.patient_id = p.id
            LEFT JOIN users u ON e.professional_id = u.id
            WHERE e.patient_id = ?
            ORDER BY e.date DESC, e.created_at DESC
        ");
        $stmt->execute([$patient_id]);
        $evolutions = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $evolutions
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function createEvolution() {
    global $pdo;
    $current_user = getCurrentUser();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $required_fields = ['patient_id', 'date', 'description'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Field $field is required"]);
            return;
        }
    }
    
    // Check if user can create evolution for this patient
    if ($current_user['type'] === 'professional') {
        $stmt = $pdo->prepare("SELECT responsible_id FROM patients WHERE id = ?");
        $stmt->execute([$input['patient_id']]);
        $patient = $stmt->fetch();
        
        if (!$patient || $patient['responsible_id'] != $current_user['id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Can only create evolutions for your own patients']);
            return;
        }
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO evolutions (patient_id, professional_id, date, description, observations) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $input['patient_id'],
            $current_user['id'],
            $input['date'],
            $input['description'],
            $input['observations'] ?? null
        ]);
        
        $evolution_id = $pdo->lastInsertId();
        
        // Get created evolution with relationships
        $stmt = $pdo->prepare("
            SELECT e.*, p.name as patient_name, u.name as professional_name
            FROM evolutions e
            LEFT JOIN patients p ON e.patient_id = p.id
            LEFT JOIN users u ON e.professional_id = u.id
            WHERE e.id = ?
        ");
        $stmt->execute([$evolution_id]);
        $evolution = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'data' => $evolution,
            'message' => 'Evolution created successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function updateEvolution($id) {
    global $pdo;
    $current_user = getCurrentUser();
    
    // Check if user can edit this evolution
    $stmt = $pdo->prepare("SELECT professional_id FROM evolutions WHERE id = ?");
    $stmt->execute([$id]);
    $evolution = $stmt->fetch();
    
    if (!$evolution) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Evolution not found']);
        return;
    }
    
    if ($current_user['type'] === 'professional' && $evolution['professional_id'] != $current_user['id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Can only edit your own evolutions']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    try {
        // Build update query dynamically
        $update_fields = [];
        $params = [];
        
        $allowed_fields = ['date', 'description', 'observations'];
        
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
        $sql = "UPDATE evolutions SET " . implode(', ', $update_fields) . " WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        // Get updated evolution
        $stmt = $pdo->prepare("
            SELECT e.*, p.name as patient_name, u.name as professional_name
            FROM evolutions e
            LEFT JOIN patients p ON e.patient_id = p.id
            LEFT JOIN users u ON e.professional_id = u.id
            WHERE e.id = ?
        ");
        $stmt->execute([$id]);
        $evolution = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'data' => $evolution,
            'message' => 'Evolution updated successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function deleteEvolution($id) {
    global $pdo;
    $current_user = getCurrentUser();
    
    // Check if user can delete this evolution
    $stmt = $pdo->prepare("SELECT professional_id FROM evolutions WHERE id = ?");
    $stmt->execute([$id]);
    $evolution = $stmt->fetch();
    
    if (!$evolution) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Evolution not found']);
        return;
    }
    
    if ($current_user['type'] === 'professional' && $evolution['professional_id'] != $current_user['id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Can only delete your own evolutions']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM evolutions WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Evolution deleted successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function exportEvolutionPDF($id) {
    global $pdo;
    $current_user = getCurrentUser();
    
    // Check if user can access this evolution
    $stmt = $pdo->prepare("SELECT professional_id FROM evolutions WHERE id = ?");
    $stmt->execute([$id]);
    $evolution = $stmt->fetch();
    
    if (!$evolution) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Evolution not found']);
        return;
    }
    
    if ($current_user['type'] === 'professional' && $evolution['professional_id'] != $current_user['id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Can only export your own evolutions']);
        return;
    }
    
    try {
        // Get evolution details
        $stmt = $pdo->prepare("
            SELECT e.*, p.name as patient_name, u.name as professional_name
            FROM evolutions e
            LEFT JOIN patients p ON e.patient_id = p.id
            LEFT JOIN users u ON e.professional_id = u.id
            WHERE e.id = ?
        ");
        $stmt->execute([$id]);
        $evolution_data = $stmt->fetch();
        
        // Get PDF config (if admin exists)
        $pdf_config = null;
        $stmt = $pdo->query("SELECT * FROM pdf_config LIMIT 1");
        $pdf_config = $stmt->fetch();
        
        // Generate simple PDF content (basic implementation)
        // In a real application, you would use a proper PDF library like TCPDF or mPDF
        $pdf_content = generateBasicPDF($evolution_data, $pdf_config);
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="evolucao_' . $evolution_data['patient_name'] . '_' . date('Y-m-d') . '.pdf"');
        header('Content-Length: ' . strlen($pdf_content));
        
        echo $pdf_content;
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error generating PDF']);
    }
}

function generateBasicPDF($evolution, $config) {
    // This is a simplified PDF generation
    // In production, use a proper PDF library
    $clinic_name = $config ? $config['clinic_name'] : 'Clínica Multidisciplinar';
    $clinic_address = $config ? $config['clinic_address'] : '';
    
    $content = "%PDF-1.4\n";
    $content .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
    $content .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
    $content .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n";
    $content .= "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
    
    $text_content = "BT\n";
    $text_content .= "/F1 16 Tf\n";
    $text_content .= "50 750 Td\n";
    $text_content .= "($clinic_name) Tj\n";
    $text_content .= "0 -30 Td\n";
    $text_content .= "/F1 12 Tf\n";
    $text_content .= "(Relatorio de Evolucao) Tj\n";
    $text_content .= "0 -40 Td\n";
    $text_content .= "(Paciente: " . $evolution['patient_name'] . ") Tj\n";
    $text_content .= "0 -20 Td\n";
    $text_content .= "(Profissional: " . $evolution['professional_name'] . ") Tj\n";
    $text_content .= "0 -20 Td\n";
    $text_content .= "(Data: " . date('d/m/Y', strtotime($evolution['date'])) . ") Tj\n";
    $text_content .= "0 -40 Td\n";
    $text_content .= "(Descricao:) Tj\n";
    $text_content .= "0 -20 Td\n";
    $text_content .= "(" . substr($evolution['description'], 0, 100) . "...) Tj\n";
    $text_content .= "ET\n";
    
    $content .= "5 0 obj\n<< /Length " . strlen($text_content) . " >>\nstream\n";
    $content .= $text_content;
    $content .= "\nendstream\nendobj\n";
    
    $content .= "xref\n0 6\n";
    $content .= "0000000000 65535 f \n";
    $content .= "0000000010 00000 n \n";
    $content .= "0000000079 00000 n \n";
    $content .= "0000000138 00000 n \n";
    $content .= "0000000273 00000 n \n";
    $content .= "0000000336 00000 n \n";
    
    $content .= "trailer\n<< /Size 6 /Root 1 0 R >>\n";
    $content .= "startxref\n" . (strlen($content) + 20) . "\n%%EOF\n";
    
    return $content;
}
?>
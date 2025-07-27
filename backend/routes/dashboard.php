<?php
function handleDashboardRoutes($method, $action) {
    switch ($method) {
        case 'GET':
            if ($action === 'stats') {
                getDashboardStats();
            } elseif ($action === 'upcoming-appointments') {
                getUpcomingAppointments();
            } elseif ($action === 'alerts') {
                getAlerts();
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Dashboard endpoint not found']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
}

function getDashboardStats() {
    global $pdo;
    $current_user = getCurrentUser();
    
    try {
        $stats = [];
        
        if ($current_user['type'] === 'professional') {
            // Professional sees only their own stats
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM patients WHERE responsible_id = ?");
            $stmt->execute([$current_user['id']]);
            $stats['totalPatients'] = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM appointments WHERE professional_id = ?");
            $stmt->execute([$current_user['id']]);
            $stats['totalAppointments'] = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM evolutions WHERE professional_id = ?");
            $stmt->execute([$current_user['id']]);
            $stats['totalEvolutions'] = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM appointments WHERE professional_id = ? AND status = 'completed'");
            $stmt->execute([$current_user['id']]);
            $stats['completedAppointments'] = $stmt->fetchColumn();
        } else {
            // Admin and assistant see all stats
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM patients");
            $stats['totalPatients'] = $stmt->fetchColumn();
            
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM appointments");
            $stats['totalAppointments'] = $stmt->fetchColumn();
            
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM evolutions");
            $stats['totalEvolutions'] = $stmt->fetchColumn();
            
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM appointments WHERE status = 'completed'");
            $stats['completedAppointments'] = $stmt->fetchColumn();
        }
        
        echo json_encode([
            'success' => true,
            'data' => $stats
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function getUpcomingAppointments() {
    global $pdo;
    $current_user = getCurrentUser();
    
    try {
        $sql = "
            SELECT a.*, p.name as patient_name, u.name as professional_name, tt.name as therapy_type
            FROM appointments a
            LEFT JOIN patients p ON a.patient_id = p.id
            LEFT JOIN users u ON a.professional_id = u.id
            LEFT JOIN therapy_types tt ON a.therapy_type_id = tt.id
            WHERE a.date >= CURDATE() AND a.status = 'scheduled'
        ";
        
        $params = [];
        
        if ($current_user['type'] === 'professional') {
            $sql .= " AND a.professional_id = ?";
            $params[] = $current_user['id'];
        }
        
        $sql .= " ORDER BY a.date ASC, a.time ASC LIMIT 10";
        
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

function getAlerts() {
    global $pdo;
    $current_user = getCurrentUser();
    
    try {
        $alerts = [];
        
        // Sample alerts - you can implement your own logic
        if ($current_user['type'] === 'professional') {
            // Check for appointments today
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM appointments a 
                WHERE a.professional_id = ? AND a.date = CURDATE() AND a.status = 'scheduled'
            ");
            $stmt->execute([$current_user['id']]);
            $today_appointments = $stmt->fetchColumn();
            
            if ($today_appointments > 0) {
                $alerts[] = [
                    'id' => 1,
                    'type' => 'info',
                    'message' => "Você tem $today_appointments atendimento(s) agendado(s) para hoje",
                    'created_at' => date('Y-m-d H:i:s')
                ];
            }
            
            // Check for overdue evolutions (patients without evolution in last 30 days)
            $stmt = $pdo->prepare("
                SELECT COUNT(DISTINCT p.id) as count
                FROM patients p
                LEFT JOIN evolutions e ON p.id = e.patient_id AND e.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                WHERE p.responsible_id = ? AND e.id IS NULL
            ");
            $stmt->execute([$current_user['id']]);
            $overdue_evolutions = $stmt->fetchColumn();
            
            if ($overdue_evolutions > 0) {
                $alerts[] = [
                    'id' => 2,
                    'type' => 'warning',
                    'message' => "$overdue_evolutions paciente(s) sem evolução nos últimos 30 dias",
                    'created_at' => date('Y-m-d H:i:s')
                ];
            }
        } else {
            // Admin/Assistant alerts
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM appointments WHERE date = CURDATE() AND status = 'scheduled'");
            $today_appointments = $stmt->fetchColumn();
            
            if ($today_appointments > 0) {
                $alerts[] = [
                    'id' => 1,
                    'type' => 'info',
                    'message' => "$today_appointments atendimento(s) agendado(s) para hoje",
                    'created_at' => date('Y-m-d H:i:s')
                ];
            }
            
            // Check for new patients this week
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM patients WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
            $new_patients = $stmt->fetchColumn();
            
            if ($new_patients > 0) {
                $alerts[] = [
                    'id' => 2,
                    'type' => 'info',
                    'message' => "$new_patients novo(s) paciente(s) cadastrado(s) esta semana",
                    'created_at' => date('Y-m-d H:i:s')
                ];
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => $alerts
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
?>
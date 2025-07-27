<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config/database.php';
require_once 'middleware/auth.php';
require_once 'routes/auth.php';
require_once 'routes/users.php';
require_once 'routes/patients.php';
require_once 'routes/appointments.php';
require_once 'routes/evolutions.php';
require_once 'routes/insurance_plans.php';
require_once 'routes/therapy_types.php';
require_once 'routes/companions.php';
require_once 'routes/community.php';
require_once 'routes/dashboard.php';
require_once 'routes/pdf_config.php';

// Parse URL
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = str_replace('/api', '', $path);
$path_parts = explode('/', trim($path, '/'));

$method = $_SERVER['REQUEST_METHOD'];
$resource = $path_parts[0] ?? '';
$id = $path_parts[1] ?? null;
$action = $path_parts[2] ?? null;

try {
    switch ($resource) {
        case 'auth':
            handleAuthRoutes($method, $id);
            break;
            
        case 'users':
            requireAuth();
            handleUserRoutes($method, $id);
            break;
            
        case 'patients':
            requireAuth();
            handlePatientRoutes($method, $id, $action);
            break;
            
        case 'appointments':
            requireAuth();
            handleAppointmentRoutes($method, $id);
            break;
            
        case 'evolutions':
            requireAuth();
            handleEvolutionRoutes($method, $id, $action);
            break;
            
        case 'insurance-plans':
            requireAuth();
            handleInsurancePlanRoutes($method, $id);
            break;
            
        case 'therapy-types':
            requireAuth();
            handleTherapyTypeRoutes($method, $id);
            break;
            
        case 'companions':
            requireAuth();
            handleCompanionRoutes($method, $id);
            break;
            
        case 'community':
            requireAuth();
            handleCommunityRoutes($method, $id, $action);
            break;
            
        case 'dashboard':
            requireAuth();
            handleDashboardRoutes($method, $id);
            break;
            
        case 'pdf-config':
            requireAuth();
            handlePDFConfigRoutes($method, $id);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
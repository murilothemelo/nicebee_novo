<?php
// Configurações de segurança

// Definir headers de segurança
function setSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\'; style-src \'self\' \'unsafe-inline\'; img-src \'self\' data: https:; font-src \'self\' data:');
}

// Validar e sanitizar entrada
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    return $data;
}

// Validar email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Validar senha forte
function validatePassword($password) {
    // Mínimo 8 caracteres, pelo menos uma letra maiúscula, uma minúscula e um número
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/', $password);
}

// Rate limiting simples (pode ser melhorado com Redis)
function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 300) {
    $file = sys_get_temp_dir() . '/rate_limit_' . md5($identifier);
    
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        
        if ($data['timestamp'] > time() - $timeWindow) {
            if ($data['attempts'] >= $maxAttempts) {
                return false;
            }
            $data['attempts']++;
        } else {
            $data = ['attempts' => 1, 'timestamp' => time()];
        }
    } else {
        $data = ['attempts' => 1, 'timestamp' => time()];
    }
    
    file_put_contents($file, json_encode($data));
    return true;
}

// Aplicar headers de segurança
setSecurityHeaders();
?>
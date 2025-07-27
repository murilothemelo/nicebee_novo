<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'clinica_gestao';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $e->getMessage()]);
            exit();
        }

        return $this->conn;
    }
}

// Initialize database connection
$database = new Database();
$pdo = $database->getConnection();

// Create tables if they don't exist
$createTables = "
-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    type ENUM('admin', 'assistant', 'professional') NOT NULL DEFAULT 'professional',
    phone VARCHAR(20),
    specialty VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insurance plans table
CREATE TABLE IF NOT EXISTS insurance_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    psychology_value DECIMAL(10,2) DEFAULT 0.00,
    physiotherapy_value DECIMAL(10,2) DEFAULT 0.00,
    occupational_therapy_value DECIMAL(10,2) DEFAULT 0.00,
    speech_therapy_value DECIMAL(10,2) DEFAULT 0.00,
    phone VARCHAR(20),
    email VARCHAR(255),
    start_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Patients table
CREATE TABLE IF NOT EXISTS patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    birth_date DATE NOT NULL,
    category VARCHAR(100) NOT NULL,
    gender ENUM('M', 'F', 'Other') NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(255),
    address TEXT,
    responsible_id INT NOT NULL,
    insurance_plan_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (responsible_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (insurance_plan_id) REFERENCES insurance_plans(id) ON DELETE SET NULL
);

-- Medical records table
CREATE TABLE IF NOT EXISTS medical_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    professional_id INT NOT NULL,
    type ENUM('document', 'report', 'evaluation', 'other') NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(500) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (professional_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Therapy types table
CREATE TABLE IF NOT EXISTS therapy_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    specialty VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Appointments table
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    professional_id INT NOT NULL,
    therapy_type_id INT NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    frequency ENUM('single', 'weekly', 'biweekly', 'monthly') NOT NULL DEFAULT 'single',
    status ENUM('scheduled', 'completed', 'cancelled') NOT NULL DEFAULT 'scheduled',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (professional_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (therapy_type_id) REFERENCES therapy_types(id) ON DELETE CASCADE
);

-- Evolutions table
CREATE TABLE IF NOT EXISTS evolutions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    professional_id INT NOT NULL,
    date DATE NOT NULL,
    description TEXT NOT NULL,
    observations TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (professional_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Companions table
CREATE TABLE IF NOT EXISTS companions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(255),
    patient_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL
);

-- Community table
CREATE TABLE IF NOT EXISTS community (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    professional_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (professional_id) REFERENCES users(id) ON DELETE CASCADE
);

-- PDF config table
CREATE TABLE IF NOT EXISTS pdf_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    clinic_name VARCHAR(255) NOT NULL DEFAULT 'Clínica Multidisciplinar',
    clinic_address TEXT,
    logo_path VARCHAR(500),
    header_text TEXT,
    footer_text TEXT,
    font_family VARCHAR(50) NOT NULL DEFAULT 'Arial',
    font_size INT NOT NULL DEFAULT 12,
    primary_color VARCHAR(7) NOT NULL DEFAULT '#2563EB',
    show_description BOOLEAN NOT NULL DEFAULT TRUE,
    show_observations BOOLEAN NOT NULL DEFAULT TRUE,
    show_professional BOOLEAN NOT NULL DEFAULT TRUE,
    show_date BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);
";

// Execute table creation
$statements = explode(';', $createTables);
foreach ($statements as $statement) {
    $statement = trim($statement);
    if (!empty($statement)) {
        try {
            $pdo->exec($statement);
        } catch (PDOException $e) {
            // Continue if table already exists
        }
    }
}

// Insert demo data
$demoUsers = "
INSERT IGNORE INTO users (id, name, email, password, type, phone, specialty) VALUES
(1, 'Administrador', 'admin@clinica.com', '" . password_hash('admin123', PASSWORD_DEFAULT) . "', 'admin', '(11) 99999-9999', 'Administração'),
(2, 'Assistente', 'assistente@clinica.com', '" . password_hash('assistente123', PASSWORD_DEFAULT) . "', 'assistant', '(11) 88888-8888', 'Assistência'),
(3, 'Dr. João Silva', 'profissional@clinica.com', '" . password_hash('profissional123', PASSWORD_DEFAULT) . "', 'professional', '(11) 77777-7777', 'Psicologia');
";

$demoInsurancePlans = "
INSERT IGNORE INTO insurance_plans (id, name, psychology_value, physiotherapy_value, occupational_therapy_value, speech_therapy_value, phone, email, start_date) VALUES
(1, 'Unimed', 150.00, 120.00, 130.00, 140.00, '(11) 3000-1000', 'contato@unimed.com.br', '2024-01-01'),
(2, 'Bradesco Saúde', 140.00, 110.00, 125.00, 135.00, '(11) 3000-2000', 'contato@bradescohealth.com.br', '2024-01-01'),
(3, 'SulAmérica', 160.00, 125.00, 135.00, 145.00, '(11) 3000-3000', 'contato@sulamerica.com.br', '2024-01-01');
";

$demoTherapyTypes = "
INSERT IGNORE INTO therapy_types (id, name, description, specialty) VALUES
(1, 'Psicoterapia Individual', 'Atendimento psicológico individual', 'Psicologia'),
(2, 'Terapia de Grupo', 'Sessões em grupo para desenvolvimento social', 'Psicologia'),
(3, 'Fisioterapia Respiratória', 'Tratamento focado no sistema respiratório', 'Fisioterapia'),
(4, 'Terapia Ocupacional', 'Desenvolvimento de habilidades funcionais', 'Terapia Ocupacional'),
(5, 'Fonoaudiologia', 'Tratamento de distúrbios da comunicação', 'Fonoaudiologia');
";

$demoPatients = "
INSERT IGNORE INTO patients (id, name, birth_date, category, gender, phone, email, responsible_id, insurance_plan_id) VALUES
(1, 'Ana Silva', '2015-03-15', 'Criança', 'F', '(11) 96666-6666', 'ana.silva@email.com', 3, 1),
(2, 'Carlos Santos', '1985-07-22', 'Adulto', 'M', '(11) 95555-5555', 'carlos.santos@email.com', 3, 2),
(3, 'Maria Oliveira', '1945-12-10', 'Idoso', 'F', '(11) 94444-4444', 'maria.oliveira@email.com', 3, 1);
";

try {
    $pdo->exec($demoUsers);
    $pdo->exec($demoInsurancePlans);
    $pdo->exec($demoTherapyTypes);
    $pdo->exec($demoPatients);
} catch (PDOException $e) {
    // Demo data already exists
}
?>
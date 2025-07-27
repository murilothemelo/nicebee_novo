<?php
// Arquivo para testar a conexão com o banco
// Coloque este arquivo na pasta public_html/api/ e acesse via navegador
// Depois de testar, DELETE este arquivo por segurança!

echo "<h2>Teste de Conexão - Clínica Multidisciplinar</h2>";

// Carregue as configurações do .env se existir
if (file_exists('.env')) {
    $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
    echo "✅ Arquivo .env encontrado<br>";
} else {
    echo "❌ Arquivo .env NÃO encontrado<br>";
}

// Configurações do banco
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? 'clinica_gestao';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? '';

echo "<br><strong>Configurações:</strong><br>";
echo "Host: $host<br>";
echo "Database: $dbname<br>";
echo "Username: $username<br>";
echo "Password: " . (empty($password) ? 'VAZIA' : str_repeat('*', strlen($password))) . "<br><br>";

// Teste de conexão
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "✅ <strong>CONEXÃO COM BANCO ESTABELECIDA COM SUCESSO!</strong><br><br>";
    
    // Testar se as tabelas existem
    $tables = ['users', 'patients', 'appointments', 'evolutions'];
    echo "<strong>Verificando tabelas:</strong><br>";
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "✅ Tabela '$table': $count registros<br>";
        } catch (Exception $e) {
            echo "❌ Tabela '$table': Não existe ou erro<br>";
        }
    }
    
    // Testar usuários padrão
    echo "<br><strong>Verificando usuários padrão:</strong><br>";
    try {
        $stmt = $pdo->query("SELECT name, email, type FROM users");
        $users = $stmt->fetchAll();
        
        if (count($users) > 0) {
            foreach ($users as $user) {
                echo "👤 {$user['name']} ({$user['email']}) - {$user['type']}<br>";
            }
        } else {
            echo "⚠️ Nenhum usuário encontrado<br>";
        }
    } catch (Exception $e) {
        echo "❌ Erro ao buscar usuários: " . $e->getMessage() . "<br>";
    }
    
} catch(PDOException $e) {
    echo "❌ <strong>ERRO DE CONEXÃO:</strong><br>";
    echo "Mensagem: " . $e->getMessage() . "<br>";
    echo "<br><strong>Possíveis soluções:</strong><br>";
    echo "1. Verifique se o banco de dados foi criado<br>";
    echo "2. Verifique se o usuário foi criado e tem permissões<br>";
    echo "3. Verifique se as configurações no .env estão corretas<br>";
    echo "4. Verifique se o nome do banco inclui o prefixo do seu usuário cPanel<br>";
}

echo "<br><hr>";
echo "<strong>⚠️ IMPORTANTE:</strong> Delete este arquivo após o teste por segurança!<br>";
echo "<strong>Arquivo:</strong> " . __FILE__;
?>
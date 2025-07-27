# 🚀 Guia Completo de Deploy no cPanel

## 📋 **PASSO 1: Preparar os Arquivos Localmente**

### 1.1 Build do Frontend
```bash
# No seu computador, na pasta do projeto
npm install
npm run build:prod
```

### 1.2 Preparar Backend
```bash
# Instalar dependências do Composer (se não tiver composer localmente, pule este passo)
cd backend
composer install --no-dev --optimize-autoloader
```

## 🗄️ **PASSO 2: Criar Banco de Dados MySQL no cPanel**

### 2.1 Acessar MySQL Databases
1. Entre no seu cPanel
2. Procure por **"MySQL Databases"** ou **"Bancos de Dados MySQL"**
3. Clique nele

### 2.2 Criar o Banco
```sql
-- Nome do banco (exemplo): seuusuario_clinica_gestao
-- O cPanel automaticamente adiciona seu usuário como prefixo
```

**No campo "Create New Database":**
- Digite: `clinica_gestao`
- Clique em **"Create Database"**
- O nome final será algo como: `seuusuario_clinica_gestao`

### 2.3 Criar Usuário do Banco
**No campo "MySQL Users" → "Add New User":**
- Username: `clinica_user`
- Password: `SuaSenhaSegura123!`
- Clique em **"Create User"**

### 2.4 Associar Usuário ao Banco
**Na seção "Add User to Database":**
- Selecione o usuário: `seuusuario_clinica_user`
- Selecione o banco: `seuusuario_clinica_gestao`
- Clique em **"Add"**
- Marque **"ALL PRIVILEGES"**
- Clique em **"Make Changes"**

## 🌐 **PASSO 3: Configurar Subdomínios**

### 3.1 Criar Subdomínio para API
1. No cPanel, procure **"Subdomains"** ou **"Subdomínios"**
2. Clique nele
3. **Criar subdomínio da API:**
   - Subdomain: `api`
   - Domain: `nicebee.com.br`
   - Document Root: `public_html/api`
   - Clique em **"Create"**

### 3.2 Criar Subdomínio para APP
1. **Criar subdomínio do frontend:**
   - Subdomain: `app`
   - Domain: `nicebee.com.br`
   - Document Root: `public_html/app`
   - Clique em **"Create"**

## 📁 **PASSO 4: Upload dos Arquivos**

### 4.1 Estrutura de Pastas no cPanel
```
public_html/
├── api/                    # Backend (api.nicebee.com.br)
│   ├── config/
│   ├── middleware/
│   ├── routes/
│   ├── vendor/
│   ├── uploads/
│   ├── index.php
│   ├── .htaccess
│   └── .env
└── app/                    # Frontend (app.nicebee.com.br)
    ├── assets/
    ├── index.html
    └── outros arquivos do build
```

### 4.2 Upload do Backend
1. No **File Manager** do cPanel, vá para `public_html/api/`
2. Faça upload de **TODOS** os arquivos da pasta `backend/` do seu projeto
3. **IMPORTANTE**: Não esqueça dos arquivos ocultos como `.htaccess`

### 4.3 Upload do Frontend
1. No **File Manager** do cPanel, vá para `public_html/app/`
2. Faça upload de **TODOS** os arquivos da pasta `dist/` (gerada pelo build)

## ⚙️ **PASSO 5: Configurar o Backend**

### 5.1 Criar arquivo .env
1. No File Manager, vá para `public_html/api/`
2. Crie um novo arquivo chamado `.env`
3. Cole o conteúdo abaixo (substitua pelos seus dados):

```env
# Configurações do Banco de Dados
DB_HOST=localhost
DB_NAME=seuusuario_clinica_gestao
DB_USER=seuusuario_clinica_user
DB_PASS=SuaSenhaSegura123!

# Configurações JWT
JWT_SECRET=sua-chave-super-secreta-jwt-mude-em-producao-123456789

# Configurações de Upload
UPLOAD_MAX_SIZE=10485760
UPLOAD_PATH=uploads/

# Configurações de E-mail (opcional)
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM=noreply@nicebee.com.br
```

### 5.2 Configurar Permissões
1. No File Manager, selecione a pasta `uploads/`
2. Clique em **"Permissions"**
3. Defina como **755** ou **777**
4. Marque **"Recurse into subdirectories"**
5. Clique em **"Change Permissions"**

### 5.3 Instalar Dependências do Composer (se necessário)
Se o seu cPanel tem acesso ao terminal:
```bash
cd public_html/api
composer install --no-dev --optimize-autoloader
```

Se não tiver, faça upload da pasta `vendor/` junto com os outros arquivos.

## 🔧 **PASSO 6: Configurar SSL**

### 6.1 Ativar SSL
1. No cPanel, procure **"SSL/TLS"**
2. Clique em **"Let's Encrypt SSL"** (se disponível)
3. Selecione os domínios:
   - `api.nicebee.com.br`
   - `app.nicebee.com.br`
4. Clique em **"Issue"**

### 6.2 Forçar HTTPS
1. Em **"SSL/TLS"** → **"Force HTTPS Redirect"**
2. Ative para ambos os subdomínios

## 🧪 **PASSO 7: Testar a Instalação**

### 7.1 Testar API
Acesse: `https://api.nicebee.com.br`
- Deve retornar: `{"success": false, "message": "Endpoint not found"}`

### 7.2 Testar Frontend
Acesse: `https://app.nicebee.com.br`
- Deve carregar a tela de login

### 7.3 Testar Login
Use as credenciais padrão:
- **Admin**: `admin@clinica.com` / `admin123`
- **Assistente**: `assistente@clinica.com` / `assistente123`
- **Profissional**: `profissional@clinica.com` / `profissional123`

## 🐛 **PASSO 8: Solução de Problemas**

### 8.1 Se a API não funcionar:
1. Verifique se o arquivo `.env` está correto
2. Teste a conexão com o banco:
```php
<?php
// Crie um arquivo teste.php na pasta api/
$host = 'localhost';
$dbname = 'seuusuario_clinica_gestao';
$username = 'seuusuario_clinica_user';
$password = 'SuaSenhaSegura123!';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    echo "Conexão OK!";
} catch(PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
```

### 8.2 Se o Frontend não carregar:
1. Verifique se todos os arquivos da pasta `dist/` foram enviados
2. Verifique se o arquivo `.htaccess` está na pasta `app/`

### 8.3 Erro de CORS:
1. Verifique se os domínios estão corretos no arquivo `backend/index.php`
2. Certifique-se de que está usando HTTPS

## 📝 **PASSO 9: Configurações Finais**

### 9.1 Alterar Senhas Padrão
1. Faça login como admin
2. Vá em **Usuários**
3. Altere as senhas dos usuários padrão

### 9.2 Configurar Perfil Admin
1. Vá em **Perfil**
2. Configure as informações da clínica
3. Faça upload do logo (se desejar)

## ✅ **Checklist Final**

- [ ] Banco de dados criado e configurado
- [ ] Subdomínios criados (api e app)
- [ ] Arquivos do backend enviados para `public_html/api/`
- [ ] Arquivos do frontend enviados para `public_html/app/`
- [ ] Arquivo `.env` criado e configurado
- [ ] Permissões da pasta `uploads/` configuradas
- [ ] SSL ativado para ambos os domínios
- [ ] Teste de login funcionando
- [ ] Senhas padrão alteradas

## 🎉 **Pronto!**

Sua Plataforma de Gestão Terapêutica está funcionando em:
- **Frontend**: https://app.nicebee.com.br
- **API**: https://api.nicebee.com.br

---

**💡 Dica**: Mantenha sempre um backup dos arquivos e do banco de dados!
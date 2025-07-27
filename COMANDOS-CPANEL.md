# 🎯 Comandos Específicos para cPanel

## 📊 **COMANDOS MYSQL NO CPANEL**

### 1. Criar Banco via phpMyAdmin (alternativo)
Se preferir usar o phpMyAdmin:

```sql
-- 1. Acesse phpMyAdmin no cPanel
-- 2. Clique em "New" para criar novo banco
-- 3. Nome: clinica_gestao
-- 4. Collation: utf8_general_ci
-- 5. Clique em "Create"
```

### 2. Criar Usuário via phpMyAdmin
```sql
-- Na aba "User accounts" do phpMyAdmin:
-- 1. Clique em "Add user account"
-- 2. User name: clinica_user
-- 3. Host name: localhost
-- 4. Password: SuaSenhaSegura123!
-- 5. Marque "Create database with same name and grant all privileges"
-- 6. Clique em "Go"
```

## 📁 **ESTRUTURA DE ARQUIVOS NO CPANEL**

### Onde colocar cada arquivo:
```
public_html/
├── api/                           # Subdomínio api.nicebee.com.br
│   ├── config/
│   │   ├── database.php
│   │   ├── cors.php
│   │   └── security.php
│   ├── middleware/
│   │   └── auth.php
│   ├── routes/
│   │   ├── auth.php
│   │   ├── users.php
│   │   ├── patients.php
│   │   ├── appointments.php
│   │   ├── evolutions.php
│   │   ├── insurance_plans.php
│   │   ├── therapy_types.php
│   │   ├── companions.php
│   │   ├── community.php
│   │   ├── dashboard.php
│   │   └── pdf_config.php
│   ├── vendor/                    # Dependências do Composer
│   ├── uploads/                   # Pasta para arquivos (permissão 777)
│   ├── index.php                  # Arquivo principal da API
│   ├── .htaccess                  # Configurações do Apache
│   ├── .env                       # Configurações (CRIAR MANUALMENTE)
│   └── composer.json
└── app/                           # Subdomínio app.nicebee.com.br
    ├── assets/                    # CSS, JS, imagens
    ├── index.html                 # Arquivo principal
    └── .htaccess                  # Redirecionamento SPA
```

## 🔧 **COMANDOS DE PERMISSÃO**

### Via File Manager do cPanel:
1. Selecione a pasta `uploads/`
2. Clique com botão direito → "Permissions"
3. Digite: `755` (ou `777` se der erro)
4. Marque "Recurse into subdirectories"
5. Clique "Change Permissions"

### Se tiver acesso SSH:
```bash
chmod -R 755 public_html/api/uploads/
chown -R seuusuario:seuusuario public_html/api/uploads/
```

## 🌐 **CONFIGURAÇÃO DE SUBDOMÍNIOS**

### Passo a passo no cPanel:

1. **Subdomínios** → **Create A New Subdomain**

2. **Para API:**
   - Subdomain: `api`
   - Domain: `nicebee.com.br`
   - Document Root: `public_html/api`

3. **Para APP:**
   - Subdomain: `app`
   - Domain: `nicebee.com.br`
   - Document Root: `public_html/app`

## 🔐 **CONFIGURAÇÃO SSL**

### Via cPanel:
1. **SSL/TLS** → **Let's Encrypt SSL**
2. Selecionar domínios:
   - ☑️ api.nicebee.com.br
   - ☑️ app.nicebee.com.br
3. Clique **"Issue"**

### Forçar HTTPS:
1. **SSL/TLS** → **Force HTTPS Redirect**
2. Ativar para ambos subdomínios

## 📝 **ARQUIVO .ENV PARA CPANEL**

Crie manualmente no File Manager (`public_html/api/.env`):

```env
# Substitua pelos seus dados reais:
DB_HOST=localhost
DB_NAME=seuusuario_clinica_gestao
DB_USER=seuusuario_clinica_user
DB_PASS=SuaSenhaSegura123!
JWT_SECRET=sua-chave-super-secreta-jwt-123456789
UPLOAD_MAX_SIZE=10485760
UPLOAD_PATH=uploads/
```

## 🧪 **TESTE RÁPIDO**

### 1. Teste da API:
Acesse: `https://api.nicebee.com.br`
**Resultado esperado:** `{"success": false, "message": "Endpoint not found"}`

### 2. Teste do Frontend:
Acesse: `https://app.nicebee.com.br`
**Resultado esperado:** Tela de login carregada

### 3. Teste de Login:
- Email: `admin@clinica.com`
- Senha: `admin123`

## ⚠️ **PROBLEMAS COMUNS**

### Erro 500 na API:
1. Verifique arquivo `.env`
2. Verifique permissões da pasta `uploads/`
3. Verifique se o Composer foi executado

### Frontend não carrega:
1. Verifique se arquivo `.htaccess` está na pasta `app/`
2. Verifique se todos arquivos da pasta `dist/` foram enviados

### Erro de CORS:
1. Verifique se está usando HTTPS
2. Verifique configuração de domínios no `index.php`

## 📞 **SUPORTE**

Se algo não funcionar:
1. Use o arquivo `test-connection.php` para testar o banco
2. Verifique os logs de erro do cPanel
3. Teste cada componente separadamente
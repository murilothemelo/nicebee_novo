# Plataforma de Gestão Terapêutica - Clínica Multidisciplinar

Sistema completo para gestão de clínicas multidisciplinares com diferentes perfis de usuário e funcionalidades específicas.

## 🚀 Tecnologias

- **Frontend**: React + TypeScript + Vite + Tailwind CSS
- **Backend**: PHP + MySQL
- **Autenticação**: JWT
- **Domínios**: 
  - Frontend: https://app.nicebee.com.br
  - API: https://api.nicebee.com.br

## 👥 Perfis de Usuário

### Admin
- Acesso total ao sistema
- Gera relatórios personalizados
- Gerencia usuários
- Personaliza layout de PDF

### Assistente
- Acesso total (exceto personalização de relatórios)
- Auxilia no dia a dia da clínica
- Gerencia pacientes e agendas

### Profissional
- Acesso restrito aos próprios pacientes
- Visualiza e gerencia apenas suas evoluções
- Acesso limitado à agenda (somente visualização)

## 📋 Funcionalidades

### 🏥 Gestão de Pacientes
- Cadastro completo de pacientes
- Prontuário eletrônico com upload de documentos
- Associação com planos médicos
- Histórico de evoluções

### 📅 Agenda
- Agendamentos com diferentes frequências
- Visualização por profissional
- Status de atendimento

### 📊 Relatórios e Evoluções
- Lançamento de evoluções por profissional
- Exportação em PDF personalizada
- Configurações de layout para admins

### 💬 Comunidade do Paciente
- Comunicação entre profissionais
- Histórico de interações
- Filtros por paciente e data

### 💳 Planos Médicos
- Cadastro de planos com valores por especialidade
- Gestão de convênios
- Associação com pacientes

### 🏥 Tipos de Terapia
- Categorização de terapias oferecidas
- Base para agendamentos e relatórios

### 👥 Acompanhantes Terapêuticos
- Cadastro de acompanhantes
- Associação opcional com pacientes

## 🛠️ Instalação e Configuração

### Backend (PHP)

1. **Configurar o banco de dados**:
   ```bash
   # Copie o arquivo de exemplo
   cp backend/.env.example backend/.env
   
   # Configure as variáveis no arquivo .env
   DB_HOST=localhost
   DB_NAME=clinica_gestao
   DB_USER=seu_usuario
   DB_PASS=sua_senha
   JWT_SECRET=sua_chave_secreta_jwt
   ```

2. **Instalar dependências**:
   ```bash
   cd backend
   composer install
   ```

3. **Configurar servidor web**:
   - Aponte o DocumentRoot para a pasta `backend/`
   - Certifique-se de que o mod_rewrite está habilitado
   - Configure SSL para HTTPS

### Frontend (React)

1. **Instalar dependências**:
   ```bash
   npm install
   ```

2. **Build para produção**:
   ```bash
   npm run build:prod
   ```

3. **Deploy**:
   - Faça upload dos arquivos da pasta `dist/` para o servidor
   - Configure o servidor web para servir o `index.html` para todas as rotas

## 🔐 Segurança

- Autenticação JWT com expiração
- CORS configurado para domínios específicos
- Headers de segurança configurados
- Validação de permissões por tipo de usuário
- Sanitização de dados de entrada

## 📁 Estrutura de Pastas

```
/
├── backend/                 # API PHP
│   ├── config/             # Configurações do banco
│   ├── middleware/         # Middleware de autenticação
│   ├── routes/            # Rotas da API
│   └── uploads/           # Arquivos enviados
├── src/                   # Frontend React
│   ├── components/        # Componentes reutilizáveis
│   ├── contexts/         # Contextos React
│   ├── lib/              # Utilitários e API
│   ├── pages/            # Páginas da aplicação
│   └── types/            # Tipos TypeScript
└── public/               # Arquivos estáticos
```

## 🚀 Deploy

### Servidor de Produção

1. **Configurar domínios**:
   - `app.nicebee.com.br` → Frontend (pasta `dist/`)
   - `api.nicebee.com.br` → Backend (pasta `backend/`)

2. **Configurar SSL**:
   - Instalar certificados SSL para ambos os domínios
   - Redirecionar HTTP para HTTPS

3. **Configurar banco de dados**:
   - Criar banco MySQL
   - Importar estrutura (será criada automaticamente na primeira execução)
   - Configurar usuário com permissões adequadas

## 📞 Suporte

Para suporte técnico ou dúvidas sobre o sistema, entre em contato através dos canais oficiais da clínica.

## 📄 Licença

Sistema proprietário desenvolvido para Clínica Multidisciplinar.
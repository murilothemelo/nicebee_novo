#!/bin/bash

# Script de deploy para produção
# Execute este script no servidor de produção

echo "🚀 Iniciando deploy da Plataforma de Gestão Terapêutica..."

# Configurações
FRONTEND_DIR="/var/www/app.nicebee.com.br"
BACKEND_DIR="/var/www/api.nicebee.com.br"
BACKUP_DIR="/var/backups/clinica-$(date +%Y%m%d-%H%M%S)"

# Criar backup
echo "📦 Criando backup..."
mkdir -p $BACKUP_DIR
cp -r $FRONTEND_DIR $BACKUP_DIR/frontend-backup 2>/dev/null || true
cp -r $BACKEND_DIR $BACKUP_DIR/backend-backup 2>/dev/null || true

# Deploy do Frontend
echo "🎨 Fazendo deploy do frontend..."
if [ -d "dist" ]; then
    rsync -av --delete dist/ $FRONTEND_DIR/
    echo "✅ Frontend deployado com sucesso!"
else
    echo "❌ Pasta dist não encontrada. Execute 'npm run build:prod' primeiro."
    exit 1
fi

# Deploy do Backend
echo "🔧 Fazendo deploy do backend..."
rsync -av --delete backend/ $BACKEND_DIR/ --exclude='.env' --exclude='uploads/*'

# Configurar permissões
echo "🔐 Configurando permissões..."
chown -R www-data:www-data $FRONTEND_DIR
chown -R www-data:www-data $BACKEND_DIR
chmod -R 755 $FRONTEND_DIR
chmod -R 755 $BACKEND_DIR
chmod -R 777 $BACKEND_DIR/uploads

# Instalar dependências do Composer (se necessário)
if [ -f "$BACKEND_DIR/composer.json" ]; then
    echo "📚 Instalando dependências do Composer..."
    cd $BACKEND_DIR
    composer install --no-dev --optimize-autoloader
fi

# Verificar arquivo .env
if [ ! -f "$BACKEND_DIR/.env" ]; then
    echo "⚠️  Arquivo .env não encontrado no backend!"
    echo "📝 Copie o arquivo .env.example e configure as variáveis:"
    echo "   cp $BACKEND_DIR/.env.example $BACKEND_DIR/.env"
    echo "   nano $BACKEND_DIR/.env"
fi

# Reiniciar serviços
echo "🔄 Reiniciando serviços..."
systemctl reload apache2 || systemctl reload nginx

echo "✅ Deploy concluído com sucesso!"
echo "🌐 Frontend: https://app.nicebee.com.br"
echo "🔗 API: https://api.nicebee.com.br"
echo "💾 Backup salvo em: $BACKUP_DIR"

# Verificar se os sites estão respondendo
echo "🔍 Verificando sites..."
if curl -s -o /dev/null -w "%{http_code}" https://app.nicebee.com.br | grep -q "200"; then
    echo "✅ Frontend está respondendo"
else
    echo "❌ Frontend não está respondendo"
fi

if curl -s -o /dev/null -w "%{http_code}" https://api.nicebee.com.br | grep -q "200\|404"; then
    echo "✅ API está respondendo"
else
    echo "❌ API não está respondendo"
fi

echo "🎉 Deploy finalizado!"
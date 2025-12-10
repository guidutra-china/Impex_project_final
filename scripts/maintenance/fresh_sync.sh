#!/bin/bash

###############################################################################
# Script de Sincronização Completa do Banco de Dados
# 
# Este script reseta completamente o banco de dados local e sincroniza
# com o estado atual do GitHub.
#
# ATENÇÃO: Este script irá APAGAR TODOS OS DADOS do banco de dados local!
#
# Uso: bash fresh_sync.sh
###############################################################################

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}==================================================${NC}"
echo -e "${BLUE}  Script de Sincronização Completa do Banco${NC}"
echo -e "${BLUE}==================================================${NC}"
echo ""

# Verificar se estamos no diretório correto
if [ ! -f "artisan" ]; then
    echo -e "${RED}❌ ERRO: Este script deve ser executado no diretório raiz do projeto Laravel${NC}"
    echo -e "${RED}   (onde está o arquivo 'artisan')${NC}"
    exit 1
fi

# Verificar se o arquivo .env existe
if [ ! -f ".env" ]; then
    echo -e "${RED}❌ ERRO: Arquivo .env não encontrado${NC}"
    echo -e "${YELLOW}   Copie o .env.example e configure suas credenciais:${NC}"
    echo -e "${YELLOW}   cp .env.example .env${NC}"
    exit 1
fi

echo -e "${YELLOW}⚠️  ATENÇÃO: Este script irá:${NC}"
echo -e "${YELLOW}   1. Fazer backup do banco de dados atual (opcional)${NC}"
echo -e "${YELLOW}   2. APAGAR TODOS OS DADOS do banco de dados${NC}"
echo -e "${YELLOW}   3. Recriar todas as tabelas do zero${NC}"
echo -e "${YELLOW}   4. Executar seeders (se disponíveis)${NC}"
echo ""
echo -e "${RED}   TODOS OS DADOS LOCAIS SERÃO PERDIDOS!${NC}"
echo ""

# Confirmação
read -p "Deseja continuar? (digite 'SIM' em maiúsculas para confirmar): " confirm

if [ "$confirm" != "SIM" ]; then
    echo -e "${RED}❌ Operação cancelada pelo usuário${NC}"
    exit 0
fi

echo ""

# Perguntar sobre backup
echo -e "${BLUE}Deseja fazer backup do banco de dados antes de resetar?${NC}"
read -p "(s/n): " backup_choice

if [ "$backup_choice" = "s" ] || [ "$backup_choice" = "S" ]; then
    echo ""
    echo -e "${BLUE}🔄 Criando backup do banco de dados...${NC}"
    
    # Criar diretório de backups se não existir
    mkdir -p storage/backups
    
    # Nome do arquivo de backup com timestamp
    BACKUP_FILE="storage/backups/backup_$(date +%Y%m%d_%H%M%S).sql"
    
    # Tentar fazer backup (funciona apenas para MySQL/MariaDB)
    if php artisan db:show 2>/dev/null | grep -q "mysql"; then
        # Extrair credenciais do .env
        DB_HOST=$(grep DB_HOST .env | cut -d '=' -f2)
        DB_PORT=$(grep DB_PORT .env | cut -d '=' -f2)
        DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f2)
        DB_USERNAME=$(grep DB_USERNAME .env | cut -d '=' -f2)
        DB_PASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2)
        
        # Fazer backup usando mysqldump
        if command -v mysqldump &> /dev/null; then
            mysqldump -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "$BACKUP_FILE" 2>/dev/null || true
            
            if [ -f "$BACKUP_FILE" ] && [ -s "$BACKUP_FILE" ]; then
                echo -e "${GREEN}✅ Backup criado: $BACKUP_FILE${NC}"
            else
                echo -e "${YELLOW}⚠️  Não foi possível criar backup automático${NC}"
                echo -e "${YELLOW}   Você pode fazer backup manual se necessário${NC}"
            fi
        else
            echo -e "${YELLOW}⚠️  mysqldump não encontrado. Pulando backup.${NC}"
        fi
    else
        echo -e "${YELLOW}⚠️  Backup automático disponível apenas para MySQL${NC}"
    fi
    
    echo ""
fi

# Passo 1: Verificar e corrigir mudanças locais
echo -e "${BLUE}📝 Passo 1/6: Verificando mudanças locais...${NC}"
if git diff --quiet; then
    echo -e "${GREEN}✅ Nenhuma mudança local detectada${NC}"
else
    echo -e "${YELLOW}⚠️  Mudanças locais detectadas${NC}"
    echo ""
    echo -e "${BLUE}Escolha uma opção:${NC}"
    echo -e "  1) Fazer commit das mudanças locais"
    echo -e "  2) Descartar mudanças locais (reset hard)"
    echo -e "  3) Fazer stash das mudanças (salvar temporariamente)"
    echo -e "  4) Cancelar operação"
    echo ""
    read -p "Opção (1-4): " git_choice
    
    case $git_choice in
        1)
            echo -e "${BLUE}🔄 Fazendo commit das mudanças...${NC}"
            git add -A
            read -p "Mensagem do commit: " commit_msg
            if [ -z "$commit_msg" ]; then
                commit_msg="chore: sync local changes before fresh sync"
            fi
            git commit -m "$commit_msg"
            echo -e "${GREEN}✅ Commit realizado${NC}"
            ;;
        2)
            echo -e "${YELLOW}🗑️  Descartando mudanças locais...${NC}"
            git reset --hard HEAD
            git clean -fd
            echo -e "${GREEN}✅ Mudanças descartadas${NC}"
            ;;
        3)
            echo -e "${BLUE}💾 Salvando mudanças no stash...${NC}"
            git stash push -m "Auto-stash before fresh sync $(date +%Y%m%d_%H%M%S)"
            echo -e "${GREEN}✅ Mudanças salvas no stash${NC}"
            echo -e "${YELLOW}   Para recuperar: git stash pop${NC}"
            ;;
        4)
            echo -e "${RED}❌ Operação cancelada${NC}"
            exit 0
            ;;
        *)
            echo -e "${RED}❌ Opção inválida${NC}"
            exit 1
            ;;
    esac
fi
echo ""

# Passo 2: Fazer pull do GitHub
echo -e "${BLUE}📥 Passo 2/6: Sincronizando com GitHub...${NC}"
git fetch origin
git pull origin $(git branch --show-current)
echo -e "${GREEN}✅ Código sincronizado com GitHub${NC}"
echo ""

# Passo 3: Resetar banco de dados ANTES de limpar cache
echo -e "${BLUE}🗑️  Passo 3/6: Resetando banco de dados...${NC}"
echo -e "${YELLOW}   Apagando todas as tabelas e dados...${NC}"
php artisan migrate:fresh --force
echo -e "${GREEN}✅ Banco de dados resetado e migrations executadas${NC}"
echo ""

# Passo 4: Limpar cache do Laravel (DEPOIS do banco estar pronto)
echo -e "${BLUE}🧹 Passo 4/6: Limpando cache do Laravel...${NC}"
php artisan config:clear 2>/dev/null || echo -e "${YELLOW}⚠️  config:clear falhou (ignorado)${NC}"
php artisan cache:clear 2>/dev/null || echo -e "${YELLOW}⚠️  cache:clear falhou (ignorado)${NC}"
php artisan route:clear 2>/dev/null || echo -e "${YELLOW}⚠️  route:clear falhou (ignorado)${NC}"
php artisan view:clear 2>/dev/null || echo -e "${YELLOW}⚠️  view:clear falhou (ignorado)${NC}"
echo -e "${GREEN}✅ Cache limpo${NC}"
echo ""

# Passo 5: Executar seeders (se existirem)
echo -e "${BLUE}🌱 Passo 5/6: Verificando seeders...${NC}"
if [ -f "database/seeders/DatabaseSeeder.php" ]; then
    # Verificar se há seeders configurados
    if grep -q "public function run" database/seeders/DatabaseSeeder.php; then
        read -p "Deseja executar os seeders? (s/n): " seed_choice
        
        if [ "$seed_choice" = "s" ] || [ "$seed_choice" = "S" ]; then
            php artisan db:seed --force
            echo -e "${GREEN}✅ Seeders executados${NC}"
        else
            echo -e "${YELLOW}⏭️  Seeders ignorados${NC}"
        fi
    else
        echo -e "${YELLOW}⏭️  Nenhum seeder configurado${NC}"
    fi
else
    echo -e "${YELLOW}⏭️  Arquivo de seeder não encontrado${NC}"
fi
echo ""

# Passo 6: Verificar status
echo -e "${BLUE}🔍 Passo 6/6: Verificando status das migrations...${NC}"
php artisan migrate:status
echo ""

# Conclusão
echo -e "${GREEN}==================================================${NC}"
echo -e "${GREEN}✅ Sincronização completa concluída com sucesso!${NC}"
echo -e "${GREEN}==================================================${NC}"
echo ""
echo -e "${BLUE}Próximos passos:${NC}"
echo -e "  1. Verifique se todas as migrations foram executadas"
echo -e "  2. Teste a aplicação para garantir que tudo funciona"
echo -e "  3. Se necessário, crie dados de teste manualmente"
echo ""

if [ "$backup_choice" = "s" ] || [ "$backup_choice" = "S" ]; then
    if [ -f "$BACKUP_FILE" ] && [ -s "$BACKUP_FILE" ]; then
        echo -e "${BLUE}💾 Backup disponível em: $BACKUP_FILE${NC}"
        echo -e "   Para restaurar: mysql -u[user] -p[pass] [database] < $BACKUP_FILE"
        echo ""
    fi
fi

echo -e "${GREEN}Seu ambiente agora está 100% sincronizado com o GitHub! 🎉${NC}"

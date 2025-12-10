#!/bin/bash

###############################################################################
# Script de Validação do Ambiente
# 
# Este script verifica se o ambiente está pronto para executar o reset
# do banco de dados.
#
# Uso: bash validate_environment.sh
###############################################################################

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

ERRORS=0
WARNINGS=0

echo -e "${BLUE}==================================================${NC}"
echo -e "${BLUE}  Validação do Ambiente de Desenvolvimento${NC}"
echo -e "${BLUE}==================================================${NC}"
echo ""

# 1. Verificar se estamos no diretório correto
echo -e "${BLUE}[1/10] Verificando diretório do projeto...${NC}"
if [ -f "artisan" ]; then
    echo -e "${GREEN}✅ Arquivo artisan encontrado${NC}"
else
    echo -e "${RED}❌ Arquivo artisan não encontrado${NC}"
    echo -e "${RED}   Execute este script no diretório raiz do projeto Laravel${NC}"
    ERRORS=$((ERRORS + 1))
fi
echo ""

# 2. Verificar arquivo .env
echo -e "${BLUE}[2/10] Verificando arquivo .env...${NC}"
if [ -f ".env" ]; then
    echo -e "${GREEN}✅ Arquivo .env encontrado${NC}"
    
    # Verificar variáveis essenciais
    if grep -q "DB_CONNECTION=" .env; then
        DB_CONNECTION=$(grep DB_CONNECTION .env | cut -d '=' -f2)
        echo -e "${GREEN}   DB_CONNECTION: $DB_CONNECTION${NC}"
    else
        echo -e "${RED}❌ DB_CONNECTION não configurado no .env${NC}"
        ERRORS=$((ERRORS + 1))
    fi
    
    if grep -q "DB_DATABASE=" .env; then
        DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f2)
        echo -e "${GREEN}   DB_DATABASE: $DB_DATABASE${NC}"
    else
        echo -e "${RED}❌ DB_DATABASE não configurado no .env${NC}"
        ERRORS=$((ERRORS + 1))
    fi
else
    echo -e "${RED}❌ Arquivo .env não encontrado${NC}"
    echo -e "${YELLOW}   Copie o .env.example: cp .env.example .env${NC}"
    ERRORS=$((ERRORS + 1))
fi
echo ""

# 3. Verificar PHP
echo -e "${BLUE}[3/10] Verificando PHP...${NC}"
if command -v php &> /dev/null; then
    PHP_VERSION=$(php -v | head -n 1 | cut -d " " -f 2 | cut -d "." -f 1,2)
    echo -e "${GREEN}✅ PHP instalado: versão $PHP_VERSION${NC}"
    
    # Verificar versão mínima (PHP 8.1+)
    if (( $(echo "$PHP_VERSION >= 8.1" | bc -l) )); then
        echo -e "${GREEN}   Versão compatível com Laravel 11${NC}"
    else
        echo -e "${YELLOW}⚠️  Laravel 11 requer PHP 8.1 ou superior${NC}"
        WARNINGS=$((WARNINGS + 1))
    fi
else
    echo -e "${RED}❌ PHP não encontrado${NC}"
    ERRORS=$((ERRORS + 1))
fi
echo ""

# 4. Verificar Composer
echo -e "${BLUE}[4/10] Verificando Composer...${NC}"
if command -v composer &> /dev/null; then
    COMPOSER_VERSION=$(composer --version | cut -d " " -f 3)
    echo -e "${GREEN}✅ Composer instalado: versão $COMPOSER_VERSION${NC}"
else
    echo -e "${RED}❌ Composer não encontrado${NC}"
    ERRORS=$((ERRORS + 1))
fi
echo ""

# 5. Verificar dependências do Composer
echo -e "${BLUE}[5/10] Verificando dependências do Composer...${NC}"
if [ -d "vendor" ]; then
    echo -e "${GREEN}✅ Diretório vendor existe${NC}"
else
    echo -e "${YELLOW}⚠️  Diretório vendor não encontrado${NC}"
    echo -e "${YELLOW}   Execute: composer install${NC}"
    WARNINGS=$((WARNINGS + 1))
fi
echo ""

# 6. Verificar conexão com banco de dados
echo -e "${BLUE}[6/10] Verificando conexão com banco de dados...${NC}"
if [ -f "artisan" ] && [ -f ".env" ]; then
    if php artisan db:show &> /dev/null; then
        echo -e "${GREEN}✅ Conexão com banco de dados OK${NC}"
    else
        echo -e "${RED}❌ Não foi possível conectar ao banco de dados${NC}"
        echo -e "${RED}   Verifique as credenciais no .env${NC}"
        ERRORS=$((ERRORS + 1))
    fi
else
    echo -e "${YELLOW}⚠️  Não foi possível testar conexão${NC}"
    WARNINGS=$((WARNINGS + 1))
fi
echo ""

# 7. Verificar Git
echo -e "${BLUE}[7/10] Verificando Git...${NC}"
if command -v git &> /dev/null; then
    echo -e "${GREEN}✅ Git instalado${NC}"
    
    # Verificar se é um repositório Git
    if [ -d ".git" ]; then
        echo -e "${GREEN}✅ Repositório Git inicializado${NC}"
        
        # Verificar branch atual
        CURRENT_BRANCH=$(git branch --show-current)
        echo -e "${GREEN}   Branch atual: $CURRENT_BRANCH${NC}"
        
        # Verificar se há mudanças não commitadas
        if git diff-index --quiet HEAD --; then
            echo -e "${GREEN}   Sem mudanças não commitadas${NC}"
        else
            echo -e "${YELLOW}⚠️  Há mudanças não commitadas${NC}"
            echo -e "${YELLOW}   Considere fazer commit antes do reset${NC}"
            WARNINGS=$((WARNINGS + 1))
        fi
    else
        echo -e "${YELLOW}⚠️  Não é um repositório Git${NC}"
        WARNINGS=$((WARNINGS + 1))
    fi
else
    echo -e "${RED}❌ Git não encontrado${NC}"
    ERRORS=$((ERRORS + 1))
fi
echo ""

# 8. Verificar permissões de diretórios
echo -e "${BLUE}[8/10] Verificando permissões...${NC}"
PERMISSION_OK=true

if [ -d "storage" ]; then
    if [ -w "storage" ]; then
        echo -e "${GREEN}✅ Diretório storage é gravável${NC}"
    else
        echo -e "${RED}❌ Diretório storage não é gravável${NC}"
        echo -e "${RED}   Execute: chmod -R 775 storage${NC}"
        ERRORS=$((ERRORS + 1))
        PERMISSION_OK=false
    fi
else
    echo -e "${RED}❌ Diretório storage não encontrado${NC}"
    ERRORS=$((ERRORS + 1))
fi

if [ -d "bootstrap/cache" ]; then
    if [ -w "bootstrap/cache" ]; then
        echo -e "${GREEN}✅ Diretório bootstrap/cache é gravável${NC}"
    else
        echo -e "${RED}❌ Diretório bootstrap/cache não é gravável${NC}"
        echo -e "${RED}   Execute: chmod -R 775 bootstrap/cache${NC}"
        ERRORS=$((ERRORS + 1))
        PERMISSION_OK=false
    fi
else
    echo -e "${RED}❌ Diretório bootstrap/cache não encontrado${NC}"
    ERRORS=$((ERRORS + 1))
fi
echo ""

# 9. Verificar migrations
echo -e "${BLUE}[9/10] Verificando migrations...${NC}"
if [ -d "database/migrations" ]; then
    MIGRATION_COUNT=$(find database/migrations -name "*.php" | wc -l)
    echo -e "${GREEN}✅ Diretório migrations existe${NC}"
    echo -e "${GREEN}   Total de migrations: $MIGRATION_COUNT${NC}"
else
    echo -e "${RED}❌ Diretório database/migrations não encontrado${NC}"
    ERRORS=$((ERRORS + 1))
fi
echo ""

# 10. Verificar espaço em disco
echo -e "${BLUE}[10/10] Verificando espaço em disco...${NC}"
DISK_USAGE=$(df -h . | awk 'NR==2 {print $5}' | sed 's/%//')
if [ "$DISK_USAGE" -lt 90 ]; then
    echo -e "${GREEN}✅ Espaço em disco suficiente (${DISK_USAGE}% usado)${NC}"
else
    echo -e "${YELLOW}⚠️  Espaço em disco baixo (${DISK_USAGE}% usado)${NC}"
    WARNINGS=$((WARNINGS + 1))
fi
echo ""

# Resumo
echo -e "${BLUE}==================================================${NC}"
echo -e "${BLUE}  Resumo da Validação${NC}"
echo -e "${BLUE}==================================================${NC}"
echo ""

if [ $ERRORS -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    echo -e "${GREEN}✅ Ambiente validado com sucesso!${NC}"
    echo -e "${GREEN}   Você pode executar o reset do banco de dados.${NC}"
    echo ""
    echo -e "${BLUE}Próximo passo:${NC}"
    echo -e "  bash fresh_sync.sh"
    echo ""
    exit 0
elif [ $ERRORS -eq 0 ]; then
    echo -e "${YELLOW}⚠️  Validação concluída com $WARNINGS aviso(s)${NC}"
    echo -e "${YELLOW}   Você pode prosseguir, mas revise os avisos acima.${NC}"
    echo ""
    echo -e "${BLUE}Próximo passo:${NC}"
    echo -e "  bash fresh_sync.sh"
    echo ""
    exit 0
else
    echo -e "${RED}❌ Validação falhou com $ERRORS erro(s) e $WARNINGS aviso(s)${NC}"
    echo -e "${RED}   Corrija os erros antes de prosseguir.${NC}"
    echo ""
    exit 1
fi

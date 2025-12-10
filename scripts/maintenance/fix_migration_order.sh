#!/bin/bash

###############################################################################
# Script para Corrigir Ordem de Migrations
# 
# Este script renomeia migrations de 2024 (que alteram tabelas) para rodarem
# DEPOIS das migrations de 2025 (que criam as tabelas).
#
# Compatível com Linux e macOS
#
# Uso: bash fix_migration_order.sh
###############################################################################

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}==================================================${NC}"
echo -e "${BLUE}  Script de Correção de Ordem de Migrations${NC}"
echo -e "${BLUE}==================================================${NC}"
echo ""

# Verificar se estamos no diretório correto
if [ ! -f "artisan" ]; then
    echo -e "${RED}❌ ERRO: Este script deve ser executado no diretório raiz do projeto Laravel${NC}"
    exit 1
fi

# Diretório de migrations
MIGRATIONS_DIR="database/migrations"

# Encontrar a última migration de 2025
LAST_2025_MIGRATION=$(ls -1 ${MIGRATIONS_DIR}/2025_* 2>/dev/null | tail -1)

if [ -z "$LAST_2025_MIGRATION" ]; then
    echo -e "${RED}❌ ERRO: Nenhuma migration de 2025 encontrada${NC}"
    exit 1
fi

LAST_2025_BASENAME=$(basename "$LAST_2025_MIGRATION")

echo -e "${BLUE}📊 Última migration de 2025:${NC} $LAST_2025_BASENAME"

# Extrair o timestamp completo (YYYY_MM_DD_NNNNNN)
# Exemplo: 2025_12_10_005129 -> extrair 2025_12_10 e 005129
LAST_TIMESTAMP=$(echo "$LAST_2025_BASENAME" | sed -E 's/^([0-9]{4}_[0-9]{2}_[0-9]{2}_[0-9]{6}).*/\1/')
LAST_DATE=$(echo "$LAST_TIMESTAMP" | cut -d'_' -f1-3)
LAST_NUMBER=$(echo "$LAST_TIMESTAMP" | cut -d'_' -f4 | sed 's/^0*//')

if [ -z "$LAST_NUMBER" ]; then
    LAST_NUMBER=0
fi

echo -e "${BLUE}📊 Data da última migration:${NC} $LAST_DATE"
echo -e "${BLUE}📊 Último número sequencial:${NC} $LAST_NUMBER"
echo ""

# Encontrar todas as migrations de 2024
MIGRATIONS_2024=$(ls -1 ${MIGRATIONS_DIR}/2024_* 2>/dev/null)

if [ -z "$MIGRATIONS_2024" ]; then
    echo -e "${GREEN}✅ Nenhuma migration de 2024 encontrada. Nada a fazer!${NC}"
    exit 0
fi

# Contar migrations
COUNT=$(echo "$MIGRATIONS_2024" | wc -l | tr -d ' ')
echo -e "${YELLOW}⚠️  Encontradas $COUNT migrations de 2024 que precisam ser renomeadas${NC}"
echo ""

# Listar migrations que serão renomeadas
echo -e "${BLUE}Migrations que serão renomeadas:${NC}"
echo "$MIGRATIONS_2024" | while read migration; do
    if [ -n "$migration" ]; then
        echo "  • $(basename "$migration")"
    fi
done
echo ""

# Confirmação
echo -e "${YELLOW}⚠️  ATENÇÃO: As migrations de 2024 serão renomeadas para $LAST_DATE${NC}"
echo -e "${YELLOW}   para rodarem DEPOIS das migrations que criam as tabelas.${NC}"
echo ""
read -p "Deseja continuar? (digite 'SIM' em maiúsculas para confirmar): " confirm

if [ "$confirm" != "SIM" ]; then
    echo -e "${RED}❌ Operação cancelada pelo usuário${NC}"
    exit 0
fi

echo ""
echo -e "${BLUE}🔄 Renomeando migrations...${NC}"
echo ""

# Contador para novos números
COUNTER=$((LAST_NUMBER + 1))
RENAMED_COUNT=0

# Renomear cada migration
echo "$MIGRATIONS_2024" | while read old_path; do
    if [ -n "$old_path" ] && [ -f "$old_path" ]; then
        # Extrair nome do arquivo
        old_name=$(basename "$old_path")
        
        # Extrair a parte após o timestamp (nome descritivo)
        # Formato: 2024_12_08_NNNNNN_nome.php -> nome.php
        descriptive_name=$(echo "$old_name" | sed -E 's/^[0-9]{4}_[0-9]{2}_[0-9]{2}_[0-9]{6}_//')
        
        # Criar novo nome com número sequencial incrementado
        # Formato: YYYY_MM_DD_NNNNNN_nome.php
        new_number=$(printf "%06d" $COUNTER)
        new_name="${LAST_DATE}_${new_number}_${descriptive_name}"
        new_path="${MIGRATIONS_DIR}/${new_name}"
        
        # Renomear arquivo
        mv "$old_path" "$new_path"
        
        if [ $? -eq 0 ]; then
            echo -e "${GREEN}✅ Renomeado:${NC}"
            echo -e "   ${YELLOW}De:${NC} $old_name"
            echo -e "   ${GREEN}Para:${NC} $new_name"
            echo ""
            COUNTER=$((COUNTER + 1))
        else
            echo -e "${RED}❌ ERRO ao renomear: $old_name${NC}"
            echo ""
        fi
    fi
done

# Aguardar o subshell terminar
wait

# Verificar quantos foram realmente renomeados
AFTER_COUNT=$(ls -1 ${MIGRATIONS_DIR}/2024_* 2>/dev/null | wc -l | tr -d ' ')
RENAMED_COUNT=$((COUNT - AFTER_COUNT))

echo -e "${GREEN}==================================================${NC}"
echo -e "${GREEN}✅ Renomeação concluída!${NC}"
echo -e "${GREEN}==================================================${NC}"
echo ""
echo -e "${BLUE}📊 Estatísticas:${NC}"
echo -e "   Migrations encontradas: $COUNT"
echo -e "   Migrations renomeadas: $RENAMED_COUNT"
echo -e "   Migrations restantes de 2024: $AFTER_COUNT"
echo ""

if [ $AFTER_COUNT -gt 0 ]; then
    echo -e "${YELLOW}⚠️  Ainda há migrations de 2024. Execute o script novamente.${NC}"
    echo ""
fi

echo -e "${BLUE}📋 Últimas 15 migrations (ordem de execução):${NC}"
ls -1 ${MIGRATIONS_DIR}/*.php | tail -15 | while read file; do
    echo -e "   $(basename "$file")"
done
echo ""
echo -e "${GREEN}Agora você pode executar as migrations com:${NC}"
echo -e "   ${YELLOW}php artisan migrate${NC}"
echo ""

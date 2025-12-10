#!/bin/bash

###############################################################################
# Script de Backup Rápido do Banco de Dados
# 
# Este script cria um backup do banco de dados atual antes de fazer
# qualquer operação destrutiva.
#
# Uso: bash quick_backup.sh
###############################################################################

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}==================================================${NC}"
echo -e "${BLUE}  Backup Rápido do Banco de Dados${NC}"
echo -e "${BLUE}==================================================${NC}"
echo ""

# Verificar se estamos no diretório correto
if [ ! -f "artisan" ]; then
    echo -e "${RED}❌ ERRO: Este script deve ser executado no diretório raiz do projeto Laravel${NC}"
    exit 1
fi

# Verificar se o arquivo .env existe
if [ ! -f ".env" ]; then
    echo -e "${RED}❌ ERRO: Arquivo .env não encontrado${NC}"
    exit 1
fi

# Criar diretório de backups se não existir
mkdir -p storage/backups

# Nome do arquivo de backup com timestamp
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="storage/backups/backup_${TIMESTAMP}.sql"

echo -e "${BLUE}📁 Diretório de backup: storage/backups/${NC}"
echo -e "${BLUE}📄 Arquivo de backup: backup_${TIMESTAMP}.sql${NC}"
echo ""

# Extrair informações do .env
DB_CONNECTION=$(grep DB_CONNECTION .env | cut -d '=' -f2 | tr -d ' ')
DB_HOST=$(grep DB_HOST .env | cut -d '=' -f2 | tr -d ' ')
DB_PORT=$(grep DB_PORT .env | cut -d '=' -f2 | tr -d ' ')
DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f2 | tr -d ' ')
DB_USERNAME=$(grep DB_USERNAME .env | cut -d '=' -f2 | tr -d ' ')
DB_PASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2 | tr -d ' ')

echo -e "${BLUE}🔍 Informações do banco:${NC}"
echo -e "   Tipo: $DB_CONNECTION"
echo -e "   Host: $DB_HOST"
echo -e "   Porta: $DB_PORT"
echo -e "   Banco: $DB_DATABASE"
echo -e "   Usuário: $DB_USERNAME"
echo ""

# Fazer backup baseado no tipo de banco
if [ "$DB_CONNECTION" = "mysql" ] || [ "$DB_CONNECTION" = "mariadb" ]; then
    echo -e "${BLUE}🔄 Criando backup MySQL/MariaDB...${NC}"
    
    if command -v mysqldump &> /dev/null; then
        # Construir comando mysqldump
        if [ -z "$DB_PASSWORD" ]; then
            mysqldump -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" "$DB_DATABASE" > "$BACKUP_FILE" 2>/dev/null
        else
            mysqldump -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "$BACKUP_FILE" 2>/dev/null
        fi
        
        if [ $? -eq 0 ] && [ -f "$BACKUP_FILE" ] && [ -s "$BACKUP_FILE" ]; then
            FILE_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
            echo -e "${GREEN}✅ Backup criado com sucesso!${NC}"
            echo -e "${GREEN}   Arquivo: $BACKUP_FILE${NC}"
            echo -e "${GREEN}   Tamanho: $FILE_SIZE${NC}"
            echo ""
            echo -e "${BLUE}Para restaurar este backup:${NC}"
            echo -e "  mysql -h$DB_HOST -P$DB_PORT -u$DB_USERNAME -p$DB_PASSWORD $DB_DATABASE < $BACKUP_FILE"
            echo ""
        else
            echo -e "${RED}❌ Erro ao criar backup${NC}"
            echo -e "${RED}   Verifique as credenciais do banco de dados${NC}"
            rm -f "$BACKUP_FILE"
            exit 1
        fi
    else
        echo -e "${RED}❌ mysqldump não encontrado${NC}"
        echo -e "${YELLOW}   Instale o MySQL client: sudo apt-get install mysql-client${NC}"
        exit 1
    fi
    
elif [ "$DB_CONNECTION" = "pgsql" ] || [ "$DB_CONNECTION" = "postgresql" ]; then
    echo -e "${BLUE}🔄 Criando backup PostgreSQL...${NC}"
    
    if command -v pg_dump &> /dev/null; then
        export PGPASSWORD="$DB_PASSWORD"
        pg_dump -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" "$DB_DATABASE" > "$BACKUP_FILE" 2>/dev/null
        unset PGPASSWORD
        
        if [ $? -eq 0 ] && [ -f "$BACKUP_FILE" ] && [ -s "$BACKUP_FILE" ]; then
            FILE_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
            echo -e "${GREEN}✅ Backup criado com sucesso!${NC}"
            echo -e "${GREEN}   Arquivo: $BACKUP_FILE${NC}"
            echo -e "${GREEN}   Tamanho: $FILE_SIZE${NC}"
            echo ""
            echo -e "${BLUE}Para restaurar este backup:${NC}"
            echo -e "  psql -h $DB_HOST -p $DB_PORT -U $DB_USERNAME $DB_DATABASE < $BACKUP_FILE"
            echo ""
        else
            echo -e "${RED}❌ Erro ao criar backup${NC}"
            rm -f "$BACKUP_FILE"
            exit 1
        fi
    else
        echo -e "${RED}❌ pg_dump não encontrado${NC}"
        echo -e "${YELLOW}   Instale o PostgreSQL client: sudo apt-get install postgresql-client${NC}"
        exit 1
    fi
    
elif [ "$DB_CONNECTION" = "sqlite" ]; then
    echo -e "${BLUE}🔄 Criando backup SQLite...${NC}"
    
    SQLITE_DB="database/$DB_DATABASE"
    if [ ! -f "$SQLITE_DB" ]; then
        SQLITE_DB="$DB_DATABASE"
    fi
    
    if [ -f "$SQLITE_DB" ]; then
        cp "$SQLITE_DB" "${BACKUP_FILE%.sql}.sqlite"
        
        if [ $? -eq 0 ]; then
            FILE_SIZE=$(du -h "${BACKUP_FILE%.sql}.sqlite" | cut -f1)
            echo -e "${GREEN}✅ Backup criado com sucesso!${NC}"
            echo -e "${GREEN}   Arquivo: ${BACKUP_FILE%.sql}.sqlite${NC}"
            echo -e "${GREEN}   Tamanho: $FILE_SIZE${NC}"
            echo ""
            echo -e "${BLUE}Para restaurar este backup:${NC}"
            echo -e "  cp ${BACKUP_FILE%.sql}.sqlite $SQLITE_DB"
            echo ""
        else
            echo -e "${RED}❌ Erro ao criar backup${NC}"
            exit 1
        fi
    else
        echo -e "${RED}❌ Arquivo SQLite não encontrado: $SQLITE_DB${NC}"
        exit 1
    fi
    
else
    echo -e "${RED}❌ Tipo de banco não suportado: $DB_CONNECTION${NC}"
    echo -e "${YELLOW}   Tipos suportados: mysql, mariadb, pgsql, postgresql, sqlite${NC}"
    exit 1
fi

# Listar backups existentes
echo -e "${BLUE}📋 Backups existentes:${NC}"
ls -lh storage/backups/ | tail -n +2 | awk '{print "   " $9 " (" $5 ")"}'
echo ""

# Contar backups
BACKUP_COUNT=$(ls -1 storage/backups/ | wc -l)
echo -e "${BLUE}Total de backups: $BACKUP_COUNT${NC}"

# Avisar se houver muitos backups
if [ $BACKUP_COUNT -gt 10 ]; then
    echo -e "${YELLOW}⚠️  Você tem muitos backups. Considere limpar os antigos:${NC}"
    echo -e "${YELLOW}   ls -t storage/backups/ | tail -n +6 | xargs -I {} rm storage/backups/{}${NC}"
fi

echo ""
echo -e "${GREEN}==================================================${NC}"
echo -e "${GREEN}✅ Backup concluído com sucesso!${NC}"
echo -e "${GREEN}==================================================${NC}"

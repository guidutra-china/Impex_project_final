# 🚀 Quick Start: AI Document Import

## Instalação no Servidor (5 minutos)

### 1. **Pull do GitHub**

```bash
cd /var/www/filament-crm
git pull
```

### 2. **Instalar Dependências PHP**

```bash
composer require phpoffice/phpspreadsheet smalot/pdfparser
```

### 3. **Instalar Dependências do Sistema**

```bash
# Para extração de imagens de PDF
sudo apt-get update
sudo apt-get install -y poppler-utils

# Verificar instalação
which pdfimages
```

### 4. **Configurar DeepSeek API**

Edite o `.env`:
```bash
nano .env
```

Adicione (se ainda não tiver):
```env
DEEP_SEEK=your_deepseek_api_key_here
```

Salve (`Ctrl+O`, `Enter`, `Ctrl+X`)

### 5. **Rodar Migration**

```bash
php artisan migrate
```

### 6. **Limpar Cache**

```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
```

### 7. **Verificar Permissões**

```bash
# Criar diretórios necessários
mkdir -p storage/app/public/products/avatars
mkdir -p storage/app/public/products/import-temp

# Dar permissões
chmod -R 775 storage/app/public/products/
chown -R www-data:www-data storage/app/public/products/
```

---

## ✅ Teste Rápido

### 1. **Acesse o Sistema**

```
https://your-domain.com/admin
```

### 2. **Vá para Document Imports**

```
Menu → System → Document Imports → New Import
```

### 3. **Teste com Arquivo JGYAN**

1. Selecione **Import Type:** Products
2. Upload: `JGYAN-20251203(1).xlsx`
3. Aguarde análise automática (~10 segundos)
4. Revise os resultados da IA:
   - ✅ Tipo: Proforma Invoice
   - ✅ Fornecedor: JiongGong Fitness
   - ✅ 70 produtos detectados
   - ✅ 70 fotos encontradas
5. Clique em **Next** → **Create & Start Import**
6. Aguarde importação (~1-2 minutos)
7. Veja os resultados!

---

## 🐛 Troubleshooting

### Erro: "Class 'PhpOffice\PhpSpreadsheet\IOFactory' not found"

```bash
composer require phpoffice/phpspreadsheet
composer dump-autoload
```

### Erro: "DeepSeek API key not configured"

```bash
# Verifique se está no .env
grep DEEP_SEEK /var/www/filament-crm/.env

# Se não estiver, adicione
echo "DEEP_SEEK=your_key_here" >> /var/www/filament-crm/.env

# Limpe cache
php artisan config:clear
```

### Erro: "pdfimages command not available"

```bash
sudo apt-get install poppler-utils
```

### Imagens não aparecem

```bash
# Verificar storage link
php artisan storage:link

# Verificar permissões
ls -la storage/app/public/products/

# Recriar diretórios
mkdir -p storage/app/public/products/avatars
chmod -R 775 storage/app/public/products/
chown -R www-data:www-data storage/app/public/products/
```

### Importação trava

```bash
# Aumentar timeout do PHP
sudo nano /etc/php/8.1/fpm/php.ini

# Procure e altere:
max_execution_time = 300
memory_limit = 512M

# Reinicie PHP-FPM
sudo systemctl restart php8.1-fpm
```

---

## 📊 Verificar Logs

```bash
# Ver últimas 100 linhas do log
tail -100 /var/www/filament-crm/storage/logs/laravel.log

# Seguir log em tempo real
tail -f /var/www/filament-crm/storage/logs/laravel.log
```

---

## 💡 Dicas

1. **Comece pequeno:** Teste com 2-3 produtos primeiro
2. **Monitore logs:** Acompanhe `storage/logs/laravel.log`
3. **Backup:** Faça backup do banco antes de importações grandes
4. **Custo API:** DeepSeek é muito barato (~$0.001 por importação)
5. **Performance:** Importações grandes podem levar alguns minutos

---

## 📈 Próximos Testes

Depois do teste inicial, experimente:

1. **Importar outro arquivo Excel** com estrutura diferente
2. **Importar um PDF** (se tiver)
3. **Ver histórico** de importações
4. **Verificar produtos** criados
5. **Verificar fotos** importadas

---

## 🎯 Resultado Esperado

Após importação do JGYAN:

- ✅ 70 produtos criados no sistema
- ✅ 70 fotos importadas e vinculadas
- ✅ Fornecedor "JiongGong Fitness" criado/vinculado
- ✅ Tags "Fitness Equipment" aplicadas
- ✅ Todos os campos preenchidos (SKU, nome, preço, peso, etc.)
- ✅ Histórico salvo com estatísticas

---

## 📞 Suporte

Se encontrar problemas:

1. Verifique os logs
2. Teste com arquivo menor
3. Verifique permissões de storage
4. Verifique se a API key está correta
5. Me envie os logs para análise

---

**Boa sorte! 🚀**

# Guia de Reinicialização de Servidor para Laravel

## 🔴 Problema

O comando `valet restart` não funciona no seu ambiente. Isso ocorre porque `valet` é uma ferramenta específica para macOS.

---

## ✅ Soluções para Diferentes Ambientes

### 1. Artisan Serve (Ambiente de Desenvolvimento)

Se você está usando o servidor embutido do Laravel:

```bash
# 1. Pare o servidor (Ctrl+C)

# 2. Limpe o cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
composer dump-autoload

# 3. Reinicie o servidor
php artisan serve
```

### 2. Laravel Sail (Docker)

Se você está usando Laravel Sail:

```bash
# 1. Pare o Sail
./vendor/bin/sail down

# 2. Limpe o cache
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan view:clear
./vendor/bin/sail artisan route:clear

# 3. Reinicie o Sail
./vendor/bin/sail up -d
```

### 3. Nginx + PHP-FPM (Produção)

Se você está em um servidor de produção com Nginx:

```bash
# 1. Limpe o cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
composer dump-autoload

# 2. Reinicie o PHP-FPM
sudo systemctl restart php8.3-fpm  # ← Mude para sua versão do PHP

# 3. Reinicie o Nginx
sudo systemctl restart nginx
```

### 4. Apache (Produção)

Se você está em um servidor de produção com Apache:

```bash
# 1. Limpe o cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
composer dump-autoload

# 2. Reinicie o Apache
sudo systemctl restart apache2
```

### 5. Laravel Herd (macOS)

Se você está usando Laravel Herd:

```bash
# 1. Limpe o cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
composer dump-autoload

# 2. Reinicie o Herd
herd restart
```

---

## 📋 Checklist de Comandos

**Sempre execute estes comandos antes de reiniciar o servidor:**

```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
composer dump-autoload
```

---

## 💡 Qual ambiente você está usando?

Por favor, me diga qual ambiente você está usando para que eu possa fornecer os comandos corretos no futuro:

- [ ] Artisan Serve
- [ ] Laravel Sail
- [ ] Nginx + PHP-FPM
- [ ] Apache
- [ ] Laravel Herd
- [ ] Outro (especifique)

Isso me ajudará a fornecer instruções mais precisas! 100% precisas para o seu setup! 🚀

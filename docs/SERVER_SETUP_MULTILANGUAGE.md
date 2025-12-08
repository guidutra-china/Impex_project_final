# 🌍 Guia de Configuração do Servidor para Multi-Language

## 📋 **Índice**
1. [Pré-requisitos](#pré-requisitos)
2. [Passo 1: Configurar Laravel](#passo-1-configurar-laravel)
3. [Passo 2: Configurar Banco de Dados](#passo-2-configurar-banco-de-dados)
4. [Passo 3: Configurar Filament](#passo-3-configurar-filament)
5. [Passo 4: Criar Middleware](#passo-4-criar-middleware)
6. [Passo 5: Deploy dos Arquivos](#passo-5-deploy-dos-arquivos)
7. [Passo 6: Testes](#passo-6-testes)
8. [Troubleshooting](#troubleshooting)

---

## ✅ **Pré-requisitos**

- ✅ Servidor com PHP 8.2+
- ✅ MySQL/MariaDB configurado com UTF8MB4
- ✅ Acesso SSH ao servidor
- ✅ Git configurado
- ✅ Laravel 12 rodando
- ✅ Filament 4 instalado

> ⚠️ **IMPORTANTE:** Filament 4 tem suporte **NATIVO** a multi-language. **NÃO é necessário** instalar plugins externos como Spatie Translatable!

---

## ⚙️ **Passo 1: Configurar Laravel**

### **1.1 Conectar ao Servidor via SSH**

```bash
ssh usuario@seu-servidor.com
cd /caminho/para/impex_project
```

### **1.2 Atualizar `config/app.php`**

Edite o arquivo:
```bash
nano config/app.php
```

**Localize a seção de locale (linha ~81) e adicione:**

```php
'locale' => env('APP_LOCALE', 'en'),

'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

/*
|--------------------------------------------------------------------------
| Available Locales
|--------------------------------------------------------------------------
|
| List of available locales for the application. Used for multi-language
| support in the admin panel and documents.
|
*/

'available_locales' => [
    'en' => 'English',
    'zh_CN' => '简体中文',
],
```

**Salvar:** `Ctrl+O`, Enter, `Ctrl+X`

### **1.3 Atualizar `.env`**

```bash
nano .env
```

**Adicionar/Atualizar:**
```env
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
```

**Salvar e sair**

### **1.4 Limpar Cache de Configuração**

```bash
php artisan config:clear
php artisan config:cache
```

---

## 🗄️ **Passo 2: Configurar Banco de Dados**

### **2.1 Criar Migration para User Locale**

```bash
php artisan make:migration add_locale_to_users_table
```

### **2.2 Editar Migration**

```bash
nano database/migrations/*_add_locale_to_users_table.php
```

**Conteúdo:**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 10)->default('en')->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};
```

**Salvar e sair**

### **2.3 Rodar Migration**

```bash
php artisan migrate
```

**Saída esperada:**
```
Running migrations.
2025_12_07_120000_add_locale_to_users_table .................... DONE
```

### **2.4 Atualizar User Model**

```bash
nano app/Models/User.php
```

**Adicionar `locale` ao `$fillable`:**

```php
protected $fillable = [
    'name',
    'email',
    'password',
    'locale',  // ← ADICIONAR ESTA LINHA
];
```

**Salvar e sair**

### **2.5 Verificar Charset do Banco (IMPORTANTE para Chinês)**

```bash
mysql -u root -p
```

```sql
-- Verificar charset atual
SHOW VARIABLES LIKE 'character_set%';

-- Se não for utf8mb4, atualizar:
ALTER DATABASE seu_banco_de_dados CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Atualizar tabela users
ALTER TABLE users CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

exit;
```

---

## 🎨 **Passo 3: Configurar Filament**

### **3.1 Editar AdminPanelProvider**

```bash
nano app/Providers/Filament/AdminPanelProvider.php
```

**Localizar o método `panel()` e adicionar configuração de locales:**

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        ->default()
        ->id('admin')
        ->path('admin')
        ->login()
        ->colors([
            'primary' => Color::Blue,
        ])
        // ↓↓↓ ADICIONAR ESTAS LINHAS ↓↓↓
        ->locales([
            'en' => 'English',
            'zh_CN' => '简体中文',
        ])
        ->defaultLocale('en')
        // ↑↑↑ ATÉ AQUI ↑↑↑
        ->sidebarCollapsibleOnDesktop()
        ->brandName('Impex System')
        // ... resto da configuração
}
```

**Salvar e sair**

> ✅ **Isso é tudo que você precisa no Filament 4!** O locale switcher aparecerá automaticamente no canto superior direito.

---

## 🔧 **Passo 4: Criar Middleware**

### **4.1 Criar Middleware SetLocale**

```bash
php artisan make:middleware SetLocale
```

### **4.2 Editar Middleware**

```bash
nano app/Http/Middleware/SetLocale.php
```

**Substituir conteúdo por:**

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Priority:
        // 1. User preference (from database)
        // 2. Session
        // 3. Browser language
        // 4. Default locale
        
        $locale = null;
        
        // 1. User preference
        if (auth()->check() && auth()->user()->locale) {
            $locale = auth()->user()->locale;
        }
        
        // 2. Session
        if (!$locale && Session::has('locale')) {
            $locale = Session::get('locale');
        }
        
        // 3. Browser language
        if (!$locale) {
            $availableLocales = array_keys(config('app.available_locales', ['en']));
            $locale = $request->getPreferredLanguage($availableLocales);
        }
        
        // 4. Default
        if (!$locale) {
            $locale = config('app.locale', 'en');
        }
        
        // Validate locale
        if (!in_array($locale, array_keys(config('app.available_locales', ['en'])))) {
            $locale = config('app.locale', 'en');
        }
        
        App::setLocale($locale);
        Session::put('locale', $locale);
        
        return $next($request);
    }
}
```

**Salvar e sair**

### **4.3 Registrar Middleware**

```bash
nano bootstrap/app.php
```

**Localizar a seção de middleware e adicionar:**

```php
use App\Http\Middleware\SetLocale;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // ↓↓↓ ADICIONAR ESTA LINHA ↓↓↓
        $middleware->web(append: [
            SetLocale::class,
        ]);
        // ↑↑↑ ATÉ AQUI ↑↑↑
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

**Salvar e sair**

---

## 📁 **Passo 5: Deploy dos Arquivos**

### **5.1 Pull do GitHub**

```bash
git pull origin main
```

**Isso vai trazer:**
- Estrutura `lang/en/` e `lang/zh_CN/`
- Arquivos de tradução
- Configurações atualizadas

### **5.2 Verificar Estrutura de Arquivos**

```bash
ls -la lang/
```

**Deve mostrar:**
```
drwxr-xr-x  en/
drwxr-xr-x  zh_CN/
```

```bash
ls -la lang/en/
```

**Deve mostrar:**
```
-rw-r--r--  common.php
-rw-r--r--  fields.php
-rw-r--r--  resources.php
-rw-r--r--  navigation.php
-rw-r--r--  actions.php
-rw-r--r--  notifications.php
-rw-r--r--  documents.php
-rw-r--r--  validation.php
```

### **5.3 Verificar Permissões**

```bash
chmod -R 755 lang/
chown -R www-data:www-data lang/
```

---

## 🧪 **Passo 6: Testes**

### **6.1 Limpar Todos os Caches**

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
```

### **6.2 Recompilar Autoload**

```bash
composer dump-autoload
```

### **6.3 Reiniciar Serviços**

**Para Nginx + PHP-FPM:**
```bash
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
```

**Para Apache:**
```bash
sudo systemctl restart apache2
```

**Para Laravel Octane:**
```bash
php artisan octane:reload
```

### **6.4 Testar no Browser**

1. **Acessar admin panel:** `https://seu-dominio.com/admin`
2. **Verificar locale switcher** no canto superior direito (ícone de globo 🌐)
3. **Trocar para Chinês (简体中文)**
4. **Verificar se interface muda**

### **6.5 Testar via Artisan Tinker**

```bash
php artisan tinker
```

```php
// Testar locale
App::setLocale('en');
__('common.yes');  // Deve retornar "Yes"

App::setLocale('zh_CN');
__('common.yes');  // Deve retornar "是"

// Testar user locale
$user = User::first();
$user->locale = 'zh_CN';
$user->save();

exit
```

---

## 🐛 **Troubleshooting**

### **Problema 1: Locale Switcher não aparece**

**Causa:** Cache não foi limpo ou configuração incorreta

**Solução:**
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
composer dump-autoload
sudo systemctl restart php8.2-fpm nginx
```

### **Problema 2: Traduções não aparecem (mostra a key)**

**Exemplo:** Mostra `common.yes` em vez de "Yes"

**Verificar:**

1. Arquivos `lang/` existem?
   ```bash
   ls -la lang/en/common.php
   ```

2. Permissões corretas?
   ```bash
   chmod -R 755 lang/
   ```

3. Sintaxe PHP correta?
   ```bash
   php -l lang/en/common.php
   ```

4. Arquivo retorna array?
   ```bash
   php -r "var_dump(require 'lang/en/common.php');"
   ```

### **Problema 3: Erro "Class SetLocale not found"**

**Solução:**
```bash
composer dump-autoload
php artisan config:clear
```

### **Problema 4: Migration falha (coluna já existe)**

**Verificar se coluna já existe:**
```bash
php artisan tinker
```
```php
Schema::hasColumn('users', 'locale');
exit
```

**Se retornar `true`, a coluna já existe. Pule a migration ou use:**
```bash
php artisan migrate:rollback --step=1
php artisan migrate
```

### **Problema 5: Caracteres chineses aparecem como "???"**

**Causa:** Charset do banco não é UTF8MB4

**Solução:**
```bash
mysql -u root -p
```
```sql
-- Verificar charset
SHOW VARIABLES LIKE 'character_set%';

-- Atualizar banco
ALTER DATABASE seu_banco CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Atualizar todas as tabelas
ALTER TABLE users CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE customers CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE products CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ... etc

exit;
```

### **Problema 6: Locale não persiste após refresh**

**Causa:** Session não está salvando ou middleware não está registrado

**Verificar:**

1. Middleware registrado?
   ```bash
   grep -n "SetLocale" bootstrap/app.php
   ```

2. Session funcionando?
   ```bash
   php artisan tinker
   ```
   ```php
   Session::put('test', 'value');
   Session::get('test');  // Deve retornar "value"
   exit
   ```

---

## 📊 **Checklist de Configuração**

### **Laravel**
- [ ] `config/app.php` atualizado com `available_locales`
- [ ] `.env` com `APP_LOCALE=en`
- [ ] Cache de config limpo

### **Banco de Dados**
- [ ] Migration `add_locale_to_users_table` criada
- [ ] Migration executada com sucesso
- [ ] User model atualizado com `locale` no `$fillable`
- [ ] Charset do banco é `utf8mb4`
- [ ] Todas as tabelas convertidas para `utf8mb4`

### **Filament**
- [ ] `AdminPanelProvider.php` configurado com `locales()`
- [ ] Locale switcher visível no admin (ícone 🌐)
- [ ] **NÃO instalou plugins externos** (não é necessário!)

### **Middleware**
- [ ] `SetLocale.php` criado
- [ ] Middleware registrado em `bootstrap/app.php`
- [ ] Autoload atualizado (`composer dump-autoload`)

### **Arquivos de Tradução**
- [ ] Estrutura `lang/en/` e `lang/zh_CN/` existe
- [ ] Arquivos de tradução presentes (common, fields, etc)
- [ ] Permissões corretas (755)
- [ ] Owner correto (www-data)

### **Testes**
- [ ] Locale switcher funciona
- [ ] Traduções aparecem corretamente
- [ ] User locale salva no banco
- [ ] Caracteres chineses exibem corretamente
- [ ] Locale persiste após refresh

---

## 🚀 **Comandos Rápidos (Resumo)**

```bash
# 1. Configurar Laravel
nano config/app.php  # Adicionar available_locales
nano .env            # APP_LOCALE=en

# 2. Criar migration
php artisan make:migration add_locale_to_users_table
nano database/migrations/*_add_locale_to_users_table.php
php artisan migrate

# 3. Atualizar User model
nano app/Models/User.php  # Adicionar 'locale' ao $fillable

# 4. Configurar Filament
nano app/Providers/Filament/AdminPanelProvider.php
# Adicionar ->locales() e ->defaultLocale()

# 5. Criar middleware
php artisan make:middleware SetLocale
nano app/Http/Middleware/SetLocale.php
nano bootstrap/app.php  # Registrar middleware

# 6. Pull do GitHub
git pull origin main

# 7. Limpar caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
composer dump-autoload

# 8. Reiniciar serviços
sudo systemctl restart php8.2-fpm nginx
# ou
sudo systemctl restart apache2
# ou
php artisan octane:reload

# 9. Verificar
php artisan tinker
>>> App::setLocale('zh_CN');
>>> __('common.yes');
=> "是"
```

---

## ✅ **Configuração Completa!**

Após seguir todos os passos, seu servidor estará configurado para multi-language com:

✅ **Inglês (EN)** - Idioma padrão  
✅ **Chinês Simplificado (ZH_CN)** - Idioma secundário  
✅ **Locale switcher nativo do Filament 4** (ícone 🌐)  
✅ **Preferência do usuário** salva no banco  
✅ **Detecção automática** de idioma do browser  
✅ **Fallback inteligente** para inglês  
✅ **Sem plugins externos** (100% nativo)  

---

## 📞 **Suporte**

Se encontrar problemas:

1. **Verificar logs do Laravel:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Verificar logs do servidor:**
   ```bash
   tail -f /var/log/nginx/error.log
   # ou
   tail -f /var/log/apache2/error.log
   ```

3. **Modo debug (temporário):**
   ```bash
   # .env
   APP_DEBUG=true
   ```
   ⚠️ **Lembre-se de desativar em produção!**

4. **Verificar versões:**
   ```bash
   php --version  # Deve ser 8.2+
   php artisan --version  # Deve ser Laravel 12.x
   ```

---

**Documento atualizado em:** 07/12/2025  
**Versão:** 2.0 (Corrigido - SEM plugin Spatie)  
**Para:** Servidor de Produção Impex Project  
**Filament:** 4.x (suporte nativo a multi-language)

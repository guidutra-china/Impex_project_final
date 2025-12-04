# Solução para Erro 404 - WidgetSelectorPage

## 🔴 Problema

Ao tentar acessar "Personalizar Dashboard" ou `/panel/widget-selector`, você recebe um erro **404 Not Found**.

---

## ✅ Soluções

### Solução 1: Limpar Cache do Filament (Recomendado)

Execute os seguintes comandos no seu servidor:

```bash
# 1. Limpar todos os caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# 2. Recompilar autoloader
composer dump-autoload

# 3. Reiniciar servidor
# Se usar Herd/Valet:
valet restart

# Se usar artisan serve:
# Ctrl+C para parar
# php artisan serve
```

### Solução 2: Verificar se a Página está Registrada

Execute no seu servidor:

```bash
php artisan route:list | grep -i widget
```

Você deve ver algo como:
```
panel/widget-selector ............. GET|HEAD ................... App\Filament\Pages\WidgetSelectorPage
```

Se não aparecer, execute a Solução 1.

### Solução 3: Limpar Cache do Navegador

1. Abra o navegador
2. Pressione **Ctrl+Shift+Delete** (Windows/Linux) ou **Cmd+Shift+Delete** (Mac)
3. Selecione **Limpar dados de navegação**
4. Marque:
   - ☑️ Cookies e outros dados de site
   - ☑️ Imagens e arquivos em cache
5. Clique em **Limpar dados**
6. Faça um **Hard Refresh**: **Ctrl+Shift+R** (Windows/Linux) ou **Cmd+Shift+R** (Mac)

### Solução 4: Verificar Permissões

Certifique-se de que você tem permissão para acessar a página:

```bash
# Verifique se o usuário tem a permissão correta
# No banco de dados, execute:
SELECT * FROM roles WHERE name = 'admin';
SELECT * FROM permissions WHERE name LIKE '%widget%';
```

Se não houver permissão, adicione:

```sql
INSERT INTO permissions (name, guard_name, created_at, updated_at) 
VALUES ('view_widget_selector', 'web', NOW(), NOW());

-- Depois associe ao role admin
INSERT INTO role_has_permissions (permission_id, role_id) 
VALUES ((SELECT id FROM permissions WHERE name = 'view_widget_selector'), 
        (SELECT id FROM roles WHERE name = 'admin'));
```

### Solução 5: Verificar Arquivo de Configuração

Certifique-se de que o `AdminPanelProvider.php` está correto:

```php
// app/Providers/Filament/AdminPanelProvider.php

use App\Filament\Pages\WidgetSelectorPage;

// ...

->pages([
    Dashboard::class,
    WidgetSelectorPage::class,  // ← Deve estar aqui
])
```

---

## 🔍 Checklist de Verificação

- [ ] Executei `php artisan cache:clear`
- [ ] Executei `php artisan config:clear`
- [ ] Executei `php artisan view:clear`
- [ ] Executei `composer dump-autoload`
- [ ] Reiniciei o servidor (valet restart ou php artisan serve)
- [ ] Limpei o cache do navegador
- [ ] Fiz um hard refresh (Ctrl+Shift+R)
- [ ] Verifiquei se WidgetSelectorPage está em `AdminPanelProvider.php`
- [ ] Verifiquei se tenho permissão para acessar a página

---

## 📋 Estrutura de Arquivos

Certifique-se de que todos esses arquivos existem:

```
app/
├── Filament/
│   ├── Pages/
│   │   ├── Dashboard.php
│   │   ├── WidgetSelectorPage.php  ← Deve existir
│   │   └── EditProfile.php
│   └── Widgets/
│       ├── CalendarWidget.php
│       ├── FinancialOverviewWidget.php
│       └── ...
├── Models/
│   ├── AvailableWidget.php
│   ├── DashboardConfiguration.php
│   └── ...
├── Services/
│   ├── DashboardConfigurationService.php
│   ├── WidgetRegistryService.php
│   └── ...
└── Providers/
    └── Filament/
        └── AdminPanelProvider.php

resources/
└── views/
    └── filament/
        └── pages/
            └── widget-selector-page.blade.php  ← Deve existir
```

---

## 🚨 Se Nada Funcionar

Se você já tentou todas as soluções acima e o erro persiste:

1. **Verifique os logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Verifique se há erros de PHP:**
   ```bash
   php -l app/Filament/Pages/WidgetSelectorPage.php
   php -l app/Providers/Filament/AdminPanelProvider.php
   ```

3. **Verifique o arquivo de configuração:**
   ```bash
   php artisan config:show filament
   ```

4. **Tente acessar a rota diretamente:**
   ```bash
   php artisan route:list | grep "panel"
   ```

---

## 💡 Alternativa: Acessar Widgets via Banco de Dados

Se você não conseguir acessar a página via interface, pode gerenciar widgets diretamente no banco de dados:

```sql
-- Ver widgets disponíveis
SELECT * FROM available_widgets;

-- Desabilitar um widget
UPDATE available_widgets SET is_available = false WHERE widget_id = 'calendar';

-- Habilitar um widget
UPDATE available_widgets SET is_available = true WHERE widget_id = 'calendar';

-- Ver configuração do usuário
SELECT * FROM dashboard_configurations WHERE user_id = 1;

-- Resetar configuração do usuário
DELETE FROM dashboard_configurations WHERE user_id = 1;
```

---

## 📞 Precisa de Ajuda?

Se o problema persistir, forneça as seguintes informações:

1. Versão do Laravel: `php artisan --version`
2. Versão do Filament: `composer show filament/filament`
3. Saída de: `php artisan route:list | grep widget`
4. Conteúdo de `storage/logs/laravel.log` (últimas linhas)
5. Saída de: `php -l app/Filament/Pages/WidgetSelectorPage.php`

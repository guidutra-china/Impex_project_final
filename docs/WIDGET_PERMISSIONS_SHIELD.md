# 🔐 Widget Permissions - Usando Shield Nativo

## ✅ Solução Correta

O Filament Shield tem um **sistema nativo** para gerenciar permissões de widgets. Não precisamos criar permissões manualmente!

---

## 🚀 Instalação Correta (2 Comandos)

### **1. Pull do GitHub**
```bash
git pull origin main
```

### **2. Gerar Permissões com Shield**
```bash
php artisan shield:generate --all
```

**O que esse comando faz:**
- ✅ Descobre automaticamente todos os widgets
- ✅ Cria permissões com o padrão correto: `view_rfq_stats_widget`
- ✅ Atualiza as roles existentes
- ✅ Registra no Shield para aparecer na interface

**Resultado esperado:**
```
Generating permissions...
✅ Permissions generated successfully!
```

### **3. Limpar Cache**
```bash
php artisan permission:cache-reset
php artisan optimize:clear
```

---

## 🎯 Como Funciona

### **Padrão de Permissões do Shield**

O Shield usa o prefixo `view_` para widgets (configurado em `config/filament-shield.php`):

```php
'widgets' => [
    'subject' => 'class',
    'prefix' => 'view',  // ← Prefixo automático
    'exclude' => [
        \Filament\Widgets\AccountWidget::class,
        \Filament\Widgets\FilamentInfoWidget::class,
    ],
],
```

**Permissões geradas:**
- `view_rfq_stats_widget`
- `view_purchase_order_stats_widget`
- `view_financial_overview_widget`

---

## 🔧 Atualizar os Widgets

Precisamos mudar o método `canView()` para usar o padrão do Shield:

### **Antes (errado):**
```php
public static function canView(): bool
{
    return auth()->user()->can('widget_RfqStatsWidget');
}
```

### **Depois (correto):**
```php
public static function canView(): bool
{
    return auth()->check() && auth()->user()->can('view_rfq_stats_widget');
}
```

---

## 📝 Passo a Passo Completo

### **1. Atualizar Código**
```bash
git pull origin main
```

### **2. Gerar Permissões**
```bash
php artisan shield:generate --all
```

### **3. Limpar Cache**
```bash
php artisan permission:cache-reset
php artisan optimize:clear
```

### **4. Verificar no Painel**
1. Acesse: `http://seu-dominio.com/panel/shield/roles`
2. Edite qualquer role
3. Vá na aba **"Widgets"**
4. Você verá os 3 widgets com nomes corretos

### **5. Testar**
1. Desmarque um widget
2. Salve
3. **Faça logout e login novamente** ← IMPORTANTE!
4. O widget deve sumir
5. Volte e marque novamente
6. Salve
7. **Faça logout e login novamente**
8. O widget deve aparecer

---

## 🐛 Por Que Não Aparece Depois de Marcar?

### **Problema: Cache de Permissões**

O Spatie Permission faz **cache das permissões do usuário** na sessão. Quando você muda as permissões de uma role, o cache não é atualizado automaticamente.

### **Soluções:**

#### **Solução 1: Logout/Login (Recomendado)**
```
1. Mude as permissões
2. Salve
3. Faça LOGOUT
4. Faça LOGIN novamente
5. As mudanças aparecem
```

#### **Solução 2: Limpar Cache (Temporário)**
```bash
php artisan permission:cache-reset
```

Mas ainda precisa fazer **logout/login** para a sessão atualizar.

#### **Solução 3: Limpar Cache + Recarregar Página**
```bash
php artisan permission:cache-reset
```

Depois, **force refresh** no navegador (Ctrl+Shift+R).

---

## 🎨 Interface no Shield

Depois de executar `php artisan shield:generate --all`, você verá:

```
┌─────────────────────────────────────────────┐
│  Edit Role: panel_user                      │
├─────────────────────────────────────────────┤
│  Tabs: Resources | Pages | Widgets          │
├─────────────────────────────────────────────┤
│  Widgets Tab:                               │
│                                             │
│  ☑️ Rfq Stats Widget                        │
│  ☑️ Purchase Order Stats Widget             │
│  ☑️ Financial Overview Widget               │
│                                             │
│  [Save changes]  [Cancel]                   │
└─────────────────────────────────────────────┘
```

---

## 🧪 Teste Completo

### **Teste 1: Remover Widget**
```bash
# 1. Edite role panel_user
# 2. Desmarque "Financial Overview Widget"
# 3. Salve
# 4. Limpe cache
php artisan permission:cache-reset

# 5. Faça LOGOUT
# 6. Faça LOGIN
# 7. Widget financeiro deve ter sumido
```

### **Teste 2: Adicionar Widget de Volta**
```bash
# 1. Edite role panel_user
# 2. Marque "Financial Overview Widget"
# 3. Salve
# 4. Limpe cache
php artisan permission:cache-reset

# 5. Faça LOGOUT
# 6. Faça LOGIN
# 7. Widget financeiro deve aparecer novamente
```

---

## 📊 Comandos Úteis

### **Gerar Todas as Permissões**
```bash
php artisan shield:generate --all
```

### **Gerar Apenas Widgets**
```bash
php artisan shield:generate --widgets
```

### **Limpar Cache de Permissões**
```bash
php artisan permission:cache-reset
```

### **Ver Permissões de uma Role**
```bash
php artisan tinker
```

```php
$role = Spatie\Permission\Models\Role::where('name', 'panel_user')->first();
$permissions = $role->permissions()->where('name', 'LIKE', '%widget%')->pluck('name');
foreach ($permissions as $perm) {
    echo "✅ {$perm}\n";
}
exit
```

### **Ver Permissões de um Usuário**
```php
$user = App\Models\User::find(1);
$permissions = $user->getAllPermissions()->where('name', 'LIKE', '%widget%')->pluck('name');
foreach ($permissions as $perm) {
    echo "✅ {$perm}\n";
}
exit
```

---

## 🔍 Troubleshooting

### **Problema: Widgets não aparecem na aba "Widgets"**

**Solução:**
```bash
php artisan shield:generate --all
php artisan optimize:clear
```

---

### **Problema: Mudanças não aparecem**

**Causa:** Cache de permissões

**Solução:**
```bash
php artisan permission:cache-reset
```

Depois, **LOGOUT e LOGIN novamente**.

---

### **Problema: Erro "Call to a member function can() on null"**

**Causa:** Usuário não autenticado

**Solução:** Já corrigido nos widgets com:
```php
if (!auth()->check()) {
    return false;
}
```

---

### **Problema: Super Admin não vê widgets**

**Causa:** Super Admin não tem permissões atribuídas

**Solução:**
```bash
php artisan tinker
```

```php
$role = Spatie\Permission\Models\Role::where('name', 'super_admin')->first();
$role->givePermissionTo([
    'view_rfq_stats_widget',
    'view_purchase_order_stats_widget',
    'view_financial_overview_widget',
]);
exit
```

Ou simplesmente:
```bash
php artisan shield:generate --all
```

---

## 📚 Configuração do Shield

Em `config/filament-shield.php`:

```php
'widgets' => [
    'subject' => 'class',           // Usa nome da classe
    'prefix' => 'view',             // Prefixo: view_
    'exclude' => [                  // Widgets excluídos
        \Filament\Widgets\AccountWidget::class,
        \Filament\Widgets\FilamentInfoWidget::class,
    ],
],
```

**Resultado:**
- `App\Filament\Widgets\RfqStatsWidget` → `view_rfq_stats_widget`
- `App\Filament\Widgets\PurchaseOrderStatsWidget` → `view_purchase_order_stats_widget`
- `App\Filament\Widgets\FinancialOverviewWidget` → `view_financial_overview_widget`

---

## ✅ Checklist Final

- [ ] Pull do GitHub executado
- [ ] `php artisan shield:generate --all` executado
- [ ] `php artisan permission:cache-reset` executado
- [ ] `php artisan optimize:clear` executado
- [ ] Widgets aparecem na aba "Widgets" do Shield
- [ ] Desmarcar widget → Logout/Login → Widget some
- [ ] Marcar widget → Logout/Login → Widget aparece

---

## 🎉 Resumo

**O problema era:**
- ❌ Estávamos criando permissões manualmente
- ❌ Usando padrão errado (`widget_*` em vez de `view_*`)
- ❌ Não usando o sistema nativo do Shield

**A solução é:**
- ✅ Usar `php artisan shield:generate --all`
- ✅ Deixar o Shield criar as permissões automaticamente
- ✅ Usar o padrão correto (`view_*`)
- ✅ Limpar cache e fazer logout/login após mudanças

---

**Criado em:** 01/12/2025  
**Versão:** 2.0 (Corrigido)  
**Status:** ✅ Solução Definitiva

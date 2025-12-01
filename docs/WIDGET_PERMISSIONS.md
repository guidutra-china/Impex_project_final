# 🔐 Widget Permissions - Controle de Acesso

## 📋 Visão Geral

Os widgets do dashboard agora possuem **controle de acesso via permissões**. Você pode configurar quais roles podem ver cada widget através do Filament Shield.

---

## 🎯 Widgets Disponíveis

### **1. RFQ Stats Widget**
- **Permissão:** `widget_RfqStatsWidget`
- **Nome no Shield:** `filament-shield::filament-shield.rfq_stats_widget`
- **Descrição:** Métricas de RFQs (ativas, cotações, conversão, trend)

### **2. Purchase Order Stats Widget**
- **Permissão:** `widget_PurchaseOrderStatsWidget`
- **Nome no Shield:** `filament-shield::filament-shield.purchase_order_stats_widget`
- **Descrição:** Métricas de POs (pendentes, ativas, atrasadas, valor)

### **3. Financial Overview Widget**
- **Permissão:** `widget_FinancialOverviewWidget`
- **Nome no Shield:** `filament-shield::filament-shield.financial_overview_widget`
- **Descrição:** Métricas financeiras (contas a receber/pagar, fluxo de caixa)

---

## 🚀 Instalação (Passo a Passo)

### **1. Fazer Pull do GitHub**

```bash
git pull origin main
```

### **2. Executar o Seeder**

```bash
php artisan db:seed --class=WidgetPermissionsSeeder
```

**Resultado esperado:**
```
✅ Widget permissions granted to super_admin
✅ Widget permissions granted to panel_user
✅ Widget permissions created successfully!
```

### **3. Limpar Cache**

```bash
php artisan optimize:clear
```

### **4. Verificar no Painel**

1. Acesse: `http://seu-dominio.com/panel/shield/roles`
2. Edite uma role (ex: `panel_user`)
3. Vá na aba **"Widgets"**
4. Você verá os 3 widgets disponíveis para marcar/desmarcar

---

## 🎨 Como Usar

### **Cenário 1: Mostrar Apenas Widget de RFQs para Vendedores**

1. Vá em **Shield → Roles**
2. Edite a role `sales_rep`
3. Vá na aba **Widgets**
4. Marque apenas: `filament-shield::filament-shield.rfq_stats_widget`
5. Desmarque os outros 2 widgets
6. Salve

**Resultado:** Vendedores verão apenas o widget de RFQs no dashboard.

---

### **Cenário 2: Esconder Widget Financeiro de Usuários Regulares**

1. Vá em **Shield → Roles**
2. Edite a role `panel_user`
3. Vá na aba **Widgets**
4. Desmarque: `filament-shield::filament-shield.financial_overview_widget`
5. Salve

**Resultado:** Usuários regulares não verão o widget financeiro.

---

### **Cenário 3: Super Admin Vê Tudo**

Por padrão, o `super_admin` já tem todas as permissões de widgets.

Se precisar adicionar manualmente:

```bash
php artisan tinker
```

```php
$role = Spatie\Permission\Models\Role::where('name', 'super_admin')->first();
$role->givePermissionTo([
    'widget_RfqStatsWidget',
    'widget_PurchaseOrderStatsWidget',
    'widget_FinancialOverviewWidget',
]);
exit
```

---

## 🔧 Gerenciamento Manual (Via Tinker)

### **Dar Permissão de Widget para uma Role**

```bash
php artisan tinker
```

```php
$role = Spatie\Permission\Models\Role::where('name', 'manager')->first();
$role->givePermissionTo('widget_FinancialOverviewWidget');
echo "✅ Permissão concedida!";
exit
```

### **Remover Permissão de Widget**

```php
$role = Spatie\Permission\Models\Role::where('name', 'sales_rep')->first();
$role->revokePermissionTo('widget_FinancialOverviewWidget');
echo "✅ Permissão removida!";
exit
```

### **Ver Permissões de uma Role**

```php
$role = Spatie\Permission\Models\Role::where('name', 'panel_user')->first();
$permissions = $role->permissions()->where('name', 'LIKE', 'widget_%')->pluck('name');
foreach ($permissions as $perm) {
    echo "- {$perm}\n";
}
exit
```

### **Ver Quais Roles Têm Acesso a um Widget**

```php
$permission = Spatie\Permission\Models\Permission::where('name', 'widget_FinancialOverviewWidget')->first();
$roles = $permission->roles()->pluck('name');
echo "Roles com acesso ao Financial Widget:\n";
foreach ($roles as $role) {
    echo "- {$role}\n";
}
exit
```

---

## 🧪 Testando

### **Teste 1: Super Admin**

1. Login como `super_admin`
2. Acesse o dashboard
3. Você deve ver **todos os 3 widgets**

### **Teste 2: Usuário Sem Permissão**

1. Crie uma role sem permissões de widget:
   ```bash
   php artisan tinker
   ```

   ```php
   $role = Spatie\Permission\Models\Role::create(['name' => 'viewer']);
   // Não dar nenhuma permissão de widget
   exit
   ```

2. Atribua a role a um usuário
3. Login com esse usuário
4. Dashboard deve estar **vazio** (sem widgets)

### **Teste 3: Permissão Parcial**

1. Dê apenas permissão de RFQ widget:
   ```php
   $role = Spatie\Permission\Models\Role::where('name', 'viewer')->first();
   $role->givePermissionTo('widget_RfqStatsWidget');
   exit
   ```

2. Login com usuário dessa role
3. Dashboard deve mostrar **apenas o RFQ Stats Widget**

---

## 🐛 Troubleshooting

### **Problema: Widgets não aparecem na aba "Widgets" do Shield**

**Causa:** Seeder não foi executado ou permissões não foram criadas.

**Solução:**
```bash
php artisan db:seed --class=WidgetPermissionsSeeder
php artisan optimize:clear
```

---

### **Problema: Erro "Call to a member function can() on null"**

**Causa:** Usuário não está autenticado.

**Solução:** Certifique-se de estar logado. Se o erro persistir, adicione verificação:

```php
public static function canView(): bool
{
    return auth()->check() && auth()->user()->can('widget_RfqStatsWidget');
}
```

---

### **Problema: Super Admin não vê widgets**

**Causa:** Permissões não foram atribuídas ao super_admin.

**Solução:**
```bash
php artisan tinker
```

```php
$role = Spatie\Permission\Models\Role::where('name', 'super_admin')->first();
$role->givePermissionTo([
    'widget_RfqStatsWidget',
    'widget_PurchaseOrderStatsWidget',
    'widget_FinancialOverviewWidget',
]);
exit
```

Depois:
```bash
php artisan optimize:clear
```

---

### **Problema: Mudanças não aparecem**

**Solução:** Sempre limpar cache após mudar permissões:
```bash
php artisan optimize:clear
```

E fazer **logout/login** novamente.

---

## 📊 Estrutura Técnica

### **Código nos Widgets**

Cada widget tem o método `canView()`:

```php
public static function canView(): bool
{
    return auth()->user()->can('widget_RfqStatsWidget');
}
```

### **Permissões no Banco**

As permissões são criadas na tabela `permissions`:

```sql
SELECT * FROM permissions WHERE name LIKE 'widget_%';
```

Resultado:
```
| id | name                              | guard_name |
|----|-----------------------------------|------------|
| 1  | widget_RfqStatsWidget             | web        |
| 2  | widget_PurchaseOrderStatsWidget   | web        |
| 3  | widget_FinancialOverviewWidget    | web        |
```

### **Relacionamento Role-Permission**

Tabela `role_has_permissions`:

```sql
SELECT r.name as role, p.name as permission
FROM role_has_permissions rhp
JOIN roles r ON rhp.role_id = r.id
JOIN permissions p ON rhp.permission_id = p.id
WHERE p.name LIKE 'widget_%';
```

---

## 🎯 Casos de Uso Comuns

### **1. Gerente de Vendas**
- ✅ RFQ Stats Widget
- ✅ Purchase Order Stats Widget
- ❌ Financial Overview Widget

### **2. Gerente Financeiro**
- ❌ RFQ Stats Widget
- ❌ Purchase Order Stats Widget
- ✅ Financial Overview Widget

### **3. Diretor / Super Admin**
- ✅ RFQ Stats Widget
- ✅ Purchase Order Stats Widget
- ✅ Financial Overview Widget

### **4. Vendedor**
- ✅ RFQ Stats Widget
- ❌ Purchase Order Stats Widget
- ❌ Financial Overview Widget

---

## 📚 Referências

- [Filament Widgets Documentation](https://filamentphp.com/docs/3.x/widgets/overview)
- [Filament Shield Documentation](https://filamentphp.com/plugins/bezhansalleh-shield)
- [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission)

---

## ✅ Checklist

- [ ] Pull do GitHub executado
- [ ] Seeder executado (`php artisan db:seed --class=WidgetPermissionsSeeder`)
- [ ] Cache limpo (`php artisan optimize:clear`)
- [ ] Widgets aparecem na aba "Widgets" do Shield
- [ ] Permissões funcionando corretamente
- [ ] Super Admin vê todos os widgets
- [ ] Usuários regulares veem apenas widgets permitidos

---

## 🎉 Pronto!

Agora você tem **controle total** sobre quais widgets cada role pode ver no dashboard!

**Benefícios:**
- ✅ Controle granular de acesso
- ✅ Interface visual no Shield
- ✅ Fácil de gerenciar
- ✅ Seguro e escalável

---

**Criado em:** 01/12/2025  
**Versão:** 1.0  
**Status:** ✅ Implementado

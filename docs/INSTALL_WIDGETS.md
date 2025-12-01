# 📦 Instalação dos Dashboard Widgets

## ✅ O Que Foi Criado

Os widgets já foram criados e estão no repositório GitHub. Você só precisa fazer o pull e eles estarão funcionando automaticamente!

### **Arquivos Criados:**
```
app/Filament/Widgets/
├── RfqStatsWidget.php              ← Widget de RFQs
├── PurchaseOrderStatsWidget.php    ← Widget de Purchase Orders
└── FinancialOverviewWidget.php     ← Widget Financeiro

app/Providers/Filament/
└── AdminPanelProvider.php          ← Atualizado (widgets registrados)

docs/
├── DASHBOARD_WIDGETS.md            ← Documentação completa
├── IMPROVEMENTS_ROADMAP.md         ← Roadmap de melhorias
└── INSTALL_WIDGETS.md              ← Este arquivo
```

---

## 🚀 Instalação (Passo a Passo)

### **1. Fazer Pull do Repositório**

No seu ambiente local (onde está rodando o Laravel):

```bash
cd /caminho/para/seu/projeto
git pull origin main
```

**Resultado esperado:**
```
remote: Enumerating objects: 22, done.
remote: Counting objects: 100% (22/22), done.
...
From https://github.com/guidutra-china/Impex_project_final
   4578ba0..660319f  main -> main
Updating 4578ba0..660319f
Fast-forward
 app/Filament/Widgets/FinancialOverviewWidget.php     | 158 +++++++++++++++
 app/Filament/Widgets/PurchaseOrderStatsWidget.php    | 115 +++++++++++
 app/Filament/Widgets/RfqStatsWidget.php              |  62 +++---
 app/Providers/Filament/AdminPanelProvider.php        |   4 +-
 docs/DASHBOARD_WIDGETS.md                            | 678 +++++++++++++++++++
 docs/IMPROVEMENTS_ROADMAP.md                         | 516 ++++++++++++++
 6 files changed, 1194 insertions(+), 25 deletions(-)
```

---

### **2. Limpar Cache do Laravel**

```bash
php artisan optimize:clear
```

Ou individualmente:

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

### **3. Verificar se os Widgets Estão Registrados**

Abra o arquivo `app/Providers/Filament/AdminPanelProvider.php` e confirme que está assim:

```php
use App\Filament\Widgets\RfqStatsWidget;
use App\Filament\Widgets\PurchaseOrderStatsWidget;
use App\Filament\Widgets\FinancialOverviewWidget;

// ...

->widgets([
    AccountWidget::class,
    FilamentInfoWidget::class,
    RfqStatsWidget::class,
    PurchaseOrderStatsWidget::class,
    FinancialOverviewWidget::class,
])
```

✅ **Se estiver assim, está correto!**

---

### **4. Acessar o Dashboard**

1. Acesse seu painel Filament:
   ```
   http://seu-dominio.com/panel
   ```

2. Faça login com suas credenciais

3. Você verá o **Dashboard** com os 3 novos widgets:
   - 🔵 **RFQ Stats Widget** (4 cards)
   - 🟣 **Purchase Order Stats Widget** (6 cards)
   - 🟢 **Financial Overview Widget** (6 cards)

---

## 🎯 O Que Você Verá

### **Layout do Dashboard:**

```
┌─────────────────────────────────────────────────────────────┐
│  Account Widget         Filament Info Widget                │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  📊 RFQ STATS WIDGET                                        │
│  ┌──────────┬──────────┬──────────┬──────────┐            │
│  │ RFQs     │ Cotações │ Taxa de  │ RFQs     │            │
│  │ Ativas   │ Recebidas│ Conversão│ Este Mês │            │
│  │   15     │    8     │   32%    │   12     │            │
│  │ [chart]  │          │          │ +20% ↑   │            │
│  └──────────┴──────────┴──────────┴──────────┘            │
│                                                              │
│  🛒 PURCHASE ORDER STATS WIDGET                             │
│  ┌──────────┬──────────┬──────────┬──────────┬──────────┬─┐│
│  │ POs      │ POs      │ Em       │ POs      │ Valor em │P││
│  │ Pendentes│ Ativas   │ Produção │ Atrasadas│ Aberto   │O││
│  │    5     │   12     │    3     │    2     │ R$ 50k   │s││
│  │          │ [chart]  │          │          │          │ ││
│  └──────────┴──────────┴──────────┴──────────┴──────────┴─┘│
│                                                              │
│  💰 FINANCIAL OVERVIEW WIDGET                               │
│  ┌──────────┬──────────┬──────────┬──────────┬──────────┬─┐│
│  │ Contas a │ Contas a │ Fluxo de │ Invoices │ Vencem   │V││
│  │ Receber  │ Pagar    │ Caixa    │ Vencidas │ em 30d   │e││
│  │ R$ 80k   │ R$ 50k   │ R$ 30k   │    3     │    5     │n││
│  │ [chart]  │          │          │          │          │d││
│  └──────────┴──────────┴──────────┴──────────┴──────────┴─┘│
└─────────────────────────────────────────────────────────────┘
```

---

## 🔐 Testando o Ownership

### **Teste 1: Como Super Admin**

1. Login como super_admin
2. Você deve ver **TODAS** as RFQs, POs e Invoices
3. Os widgets mostrarão: "Todas as RFQs" / "Todos os clientes"

### **Teste 2: Como Usuário Regular**

1. Crie um usuário de teste:
   ```bash
   php artisan tinker
   ```

   ```php
   $user = User::create([
       'name' => 'João Vendedor',
       'email' => 'joao@teste.com',
       'password' => bcrypt('password'),
   ]);
   
   // Atribuir role panel_user (can_see_all = false)
   $role = Spatie\Permission\Models\Role::where('name', 'panel_user')->first();
   $user->assignRole($role);
   
   exit
   ```

2. Atribuir um cliente para este usuário:
   - Vá em **Clients** no painel
   - Edite um cliente
   - Selecione "João Vendedor" no campo **Responsible User**
   - Salve

3. Logout e login como `joao@teste.com`

4. Você deve ver **APENAS** os dados do cliente atribuído
   - RFQs daquele cliente
   - POs daquele cliente
   - Invoices daquele cliente

---

## 🐛 Troubleshooting

### **Problema: Widgets não aparecem**

**Solução 1:** Limpar cache
```bash
php artisan optimize:clear
```

**Solução 2:** Verificar registro no AdminPanelProvider
```bash
grep -A 5 "->widgets" app/Providers/Filament/AdminPanelProvider.php
```

**Solução 3:** Verificar se arquivos existem
```bash
ls -la app/Filament/Widgets/
```

---

### **Problema: Erro "Class not found"**

**Solução:** Regenerar autoload
```bash
composer dump-autoload
```

---

### **Problema: Valores zerados**

**Causa:** Banco de dados vazio ou sem dados de teste

**Solução:** Criar dados de teste
```bash
php artisan tinker
```

```php
// Criar cliente de teste
$client = App\Models\Client::create([
    'name' => 'Cliente Teste',
    'code' => 'TST',
    'email' => 'teste@cliente.com',
    'user_id' => 1, // Atribuir ao super admin
]);

// Criar RFQ de teste
$order = App\Models\Order::create([
    'customer_id' => $client->id,
    'status' => 'draft',
    'customer_nr_rfq' => 'RFQ-001',
]);

// Criar PO de teste
$po = App\Models\PurchaseOrder::create([
    'order_id' => $order->id,
    'po_number' => 'PO-001',
    'status' => 'draft',
    'po_date' => now(),
]);

// Criar Invoice de teste
$invoice = App\Models\SalesInvoice::create([
    'client_id' => $client->id,
    'invoice_number' => 'INV-001',
    'status' => 'draft',
    'invoice_date' => now(),
    'due_date' => now()->addDays(30),
]);

exit
```

---

### **Problema: Erro de SQL**

**Causa:** Campos não existem no banco

**Solução:** Verificar se migrations foram executadas
```bash
php artisan migrate:status
```

Se houver migrations pendentes:
```bash
php artisan migrate
```

---

## 📊 Verificando os Dados

### **Ver quantas RFQs existem:**
```bash
php artisan tinker
```

```php
echo "Total RFQs: " . App\Models\Order::count();
echo "\nRFQs Ativas: " . App\Models\Order::whereIn('status', ['draft', 'pending', 'sent', 'quoted'])->count();
exit
```

### **Ver quantas POs existem:**
```php
echo "Total POs: " . App\Models\PurchaseOrder::count();
echo "\nPOs Ativas: " . App\Models\PurchaseOrder::whereIn('status', ['approved', 'sent', 'confirmed'])->count();
exit
```

### **Ver quantas Invoices existem:**
```php
echo "Total Invoices: " . App\Models\SalesInvoice::count();
echo "\nInvoices Pendentes: " . App\Models\SalesInvoice::whereIn('status', ['draft', 'sent'])->count();
exit
```

---

## ✅ Checklist de Instalação

- [ ] Git pull executado
- [ ] Cache limpo (`php artisan optimize:clear`)
- [ ] Widgets aparecem no dashboard
- [ ] Dados sendo exibidos corretamente
- [ ] Ownership funcionando (usuários veem apenas seus clientes)
- [ ] Gráficos de tendência aparecendo
- [ ] Valores monetários formatados (R$)
- [ ] Cores e ícones corretos

---

## 🎉 Pronto!

Se tudo estiver funcionando, você verá:

✅ **3 widgets** no dashboard  
✅ **Métricas em tempo real**  
✅ **Gráficos de tendência**  
✅ **Ownership automático**  
✅ **Cores e ícones bonitos**  

---

## 📞 Precisa de Ajuda?

Se encontrar algum problema:

1. Verifique os logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. Ative debug mode (temporariamente):
   ```env
   APP_DEBUG=true
   ```

3. Compartilhe o erro completo para análise

---

**Boa sorte! 🚀**

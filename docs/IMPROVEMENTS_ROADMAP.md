# 🚀 IMPEX System Improvements Roadmap

## ✅ Sistema Atual (Completado)

### **Sistema de Ownership de Clientes**
- ✅ Campo `user_id` na tabela `clients` para atribuir usuários responsáveis
- ✅ Campo `can_see_all` na tabela `roles` para controle flexível de acesso
- ✅ Trait `HasClientOwnership` para verificação de permissões
- ✅ Scope `ClientOwnershipScope` para filtragem automática
- ✅ Filtragem automática em Orders, PurchaseOrders, SalesInvoices, SupplierQuotes
- ✅ Interface de gerenciamento de usuários e atribuição de clientes
- ✅ Documentação completa em `docs/ROLE_ACCESS_MANAGEMENT.md`

### **Estrutura do Sistema**
- ✅ Laravel + Filament v3
- ✅ Spatie Permission + Filament Shield
- ✅ Navegação organizada em grupos lógicos
- ✅ Modelos completos: Clients, Orders, Products, Suppliers, Warehouses, etc.

---

## 🎯 Melhorias Prioritárias

### **1. Dashboard com KPIs** 📊 (ALTA PRIORIDADE)

**Objetivo:** Criar um dashboard executivo com métricas-chave do negócio

**Widgets a Implementar:**
- **Vendas do Mês**
  - Total de vendas (valor)
  - Número de pedidos
  - Ticket médio
  - Comparação com mês anterior

- **RFQs (Cotações)**
  - Total de RFQs ativas
  - RFQs pendentes de resposta
  - RFQs expirando (próximos 7 dias)
  - Taxa de conversão (RFQ → Order)

- **Purchase Orders**
  - POs pendentes
  - POs em produção
  - POs atrasadas
  - Valor total em aberto

- **Estoque**
  - Produtos com estoque baixo
  - Valor total em estoque
  - Produtos sem movimentação (30 dias)

- **Financeiro**
  - Contas a receber (próximos 30 dias)
  - Contas a pagar (próximos 30 dias)
  - Fluxo de caixa projetado
  - Invoices vencidas

- **Top 5 Clientes** (por valor de vendas)
- **Top 5 Produtos** (por quantidade vendida)
- **Gráfico de Vendas** (últimos 12 meses)
- **Gráfico de Compras** (últimos 12 meses)

**Considerações:**
- Respeitar ownership: usuários veem apenas KPIs dos seus clientes
- Super Admin e roles com `can_see_all=true` veem métricas globais
- Widgets devem ser responsivos e com cores consistentes
- Usar Filament Widgets nativos

**Arquivos a Criar:**
- `app/Filament/Widgets/SalesStatsWidget.php`
- `app/Filament/Widgets/RfqStatsWidget.php`
- `app/Filament/Widgets/PurchaseOrderStatsWidget.php`
- `app/Filament/Widgets/StockAlertsWidget.php`
- `app/Filament/Widgets/FinancialOverviewWidget.php`
- `app/Filament/Widgets/TopClientsWidget.php`
- `app/Filament/Widgets/TopProductsWidget.php`
- `app/Filament/Widgets/SalesChartWidget.php`

---

### **2. Sistema de Notificações** 🔔 (ALTA PRIORIDADE)

**Objetivo:** Alertar usuários sobre eventos importantes

**Notificações a Implementar:**
- **RFQs Expirando**
  - Alerta 7 dias antes do vencimento
  - Alerta 1 dia antes do vencimento
  - Alerta no dia do vencimento

- **Purchase Orders**
  - PO criada (notificar comprador)
  - PO aprovada (notificar fornecedor via email)
  - PO atrasada (data de entrega passou)
  - PO recebida (notificar vendedor)

- **Pagamentos**
  - Invoice vencendo (7 dias antes)
  - Invoice vencida
  - Pagamento recebido

- **Estoque**
  - Produto atingiu estoque mínimo
  - Produto sem estoque

- **Quality Inspections**
  - Inspeção pendente
  - Inspeção reprovada

**Tecnologias:**
- Filament Notifications (in-app)
- Laravel Notifications (email)
- Laravel Scheduler para verificações periódicas

**Arquivos a Criar:**
- `app/Notifications/RfqExpiringNotification.php`
- `app/Notifications/PurchaseOrderStatusNotification.php`
- `app/Notifications/InvoiceDueNotification.php`
- `app/Notifications/LowStockNotification.php`
- `app/Console/Commands/SendDailyNotifications.php`

---

### **3. Geração de Relatórios** 📄 (MÉDIA PRIORIDADE)

**Objetivo:** Exportar dados em PDF e Excel

**Relatórios a Implementar:**
- **Relatório de Vendas**
  - Período selecionável
  - Filtro por cliente
  - Filtro por produto
  - Totais e subtotais
  - Gráficos

- **Relatório de Compras**
  - Período selecionável
  - Filtro por fornecedor
  - Filtro por produto
  - Status das POs

- **Relatório Financeiro**
  - Contas a receber
  - Contas a pagar
  - Fluxo de caixa
  - Balanço

- **Relatório de Estoque**
  - Posição atual
  - Movimentações
  - Produtos críticos

- **Relatório de Performance de Fornecedores**
  - On-time delivery rate
  - Quality rate
  - Issues registradas

**Tecnologias:**
- Laravel Excel (Maatwebsite/Laravel-Excel) para XLSX
- Barryvdh/Laravel-DomPDF para PDF
- Filament Actions para botões de exportação

**Arquivos a Criar:**
- `app/Filament/Actions/ExportSalesReportAction.php`
- `app/Exports/SalesReportExport.php`
- `app/Services/PdfReportGenerator.php`
- `resources/views/reports/sales-report.blade.php`

---

### **4. Sistema de Anexos de Documentos** 📎 (MÉDIA PRIORIDADE)

**Objetivo:** Permitir upload de documentos relacionados a entidades

**Funcionalidades:**
- Upload de múltiplos arquivos
- Tipos de documentos: PDF, DOCX, XLSX, JPG, PNG
- Categorização de documentos (Contrato, Invoice, Certificado, etc.)
- Versionamento de documentos
- Preview de arquivos
- Download individual ou em lote

**Entidades com Anexos:**
- Clients (contratos, certificados)
- Orders (RFQs, especificações)
- PurchaseOrders (POs, confirmações)
- SalesInvoices (invoices, comprovantes)
- Suppliers (certificados, contratos)
- Products (fichas técnicas, imagens)
- Shipments (BL, packing lists)

**Tecnologia:**
- Filament FileUpload field
- Laravel Storage (local ou S3)
- Spatie Media Library (opcional, para features avançadas)

**Arquivos a Criar:**
- `app/Models/Attachment.php`
- `database/migrations/XXXX_create_attachments_table.php`
- Adicionar FileUpload fields nos Resources existentes

---

### **5. Log de Atividades (Audit Trail)** 📋 (BAIXA PRIORIDADE)

**Objetivo:** Rastrear todas as ações dos usuários

**Funcionalidades:**
- Log automático de criação, edição, exclusão
- Registro de quem, quando, o que mudou
- Timeline de atividades por entidade
- Filtros por usuário, data, tipo de ação
- Exportação de logs

**Tecnologia:**
- Spatie Laravel Activitylog
- Filament Relation Manager para exibir timeline

**Entidades a Auditar:**
- Clients
- Orders
- PurchaseOrders
- SalesInvoices
- Products
- Suppliers

**Arquivos a Criar:**
- Instalar: `composer require spatie/laravel-activitylog`
- Configurar traits nos models
- `app/Filament/Resources/ActivityLogResource.php`
- Relation Managers para timeline

---

### **6. Sistema de Aprovação (Workflow)** ✅ (BAIXA PRIORIDADE)

**Objetivo:** Implementar fluxo de aprovação para documentos

**Funcionalidades:**
- Purchase Orders acima de X valor precisam aprovação
- Sales Invoices precisam aprovação antes de envio
- Múltiplos níveis de aprovação
- Notificações para aprovadores
- Histórico de aprovações

**Fluxos:**
1. **Purchase Order**
   - Draft → Pending Approval → Approved → Sent to Supplier
   - Rejeição retorna para Draft

2. **Sales Invoice**
   - Draft → Pending Approval → Approved → Sent to Client

3. **Supplier Quote**
   - Received → Under Review → Approved/Rejected

**Tecnologia:**
- Estado na própria tabela (status field)
- Filament Actions para aprovar/rejeitar
- Notifications para alertar aprovadores

**Arquivos a Criar:**
- `app/Models/Approval.php`
- `database/migrations/XXXX_create_approvals_table.php`
- `app/Filament/Actions/ApproveAction.php`
- `app/Filament/Actions/RejectAction.php`

---

### **7. Melhorias de UX** 🎨 (CONTÍNUO)

**Funcionalidades:**
- Breadcrumbs para navegação
- Atalhos de teclado
- Busca global (search bar)
- Favoritos/Bookmarks
- Temas personalizados
- Modo escuro
- Tradução completa para PT-BR

---

## 📅 Cronograma Sugerido

### **Sprint 1: Dashboard e Notificações** (Semana 1-2)
- ✅ Dashboard com KPIs principais
- ✅ Sistema de notificações básico
- ✅ Scheduler para alertas automáticos

### **Sprint 2: Relatórios** (Semana 3)
- ✅ Relatório de vendas (PDF + Excel)
- ✅ Relatório de compras (PDF + Excel)
- ✅ Relatório financeiro básico

### **Sprint 3: Anexos e Logs** (Semana 4)
- ✅ Sistema de anexos
- ✅ Log de atividades
- ✅ Timeline de mudanças

### **Sprint 4: Workflow** (Semana 5)
- ✅ Sistema de aprovação para POs
- ✅ Sistema de aprovação para Invoices
- ✅ Notificações de aprovação

---

## 🎯 Prioridade Imediata

**Começar por:**
1. **Dashboard com KPIs** - Valor imediato para gestão
2. **Notificações** - Evita perda de prazos
3. **Relatórios** - Necessário para análise

**Depois:**
4. Anexos de documentos
5. Log de atividades
6. Sistema de aprovação

---

## 💡 Decisões Técnicas

### **Dashboard**
- Usar Filament Widgets nativos
- Charts com Filament Charts (baseado em Chart.js)
- Queries otimizadas com cache quando necessário

### **Notificações**
- In-app: Filament Notifications
- Email: Laravel Mail + Queues
- Scheduler: Laravel Task Scheduling

### **Relatórios**
- PDF: DomPDF (simples) ou Snappy/wkhtmltopdf (avançado)
- Excel: Maatwebsite/Laravel-Excel
- Templates: Blade views

### **Anexos**
- Storage: Laravel Storage (filesystem configurável)
- Organização: `/storage/app/attachments/{model}/{id}/{filename}`
- Validação: max 10MB, tipos permitidos

### **Logs**
- Spatie Activity Log (padrão de mercado)
- Retenção: 1 ano
- Limpeza automática via scheduler

---

## ❓ Perguntas para o Cliente

1. **Dashboard**: Quais KPIs são mais importantes para você?
2. **Notificações**: Prefere receber por email ou apenas in-app?
3. **Relatórios**: Quais relatórios você mais precisa no dia-a-dia?
4. **Anexos**: Qual o tamanho máximo de arquivo aceitável?
5. **Aprovações**: Quais documentos precisam de aprovação?

---

## 🚀 Próximo Passo

**Vamos começar pelo Dashboard?** É a melhoria com maior impacto imediato!

Posso criar:
- Widget de vendas do mês
- Widget de RFQs ativas
- Widget de POs pendentes
- Widget de top clientes
- Gráfico de vendas

**Confirma para eu começar?** 🎯

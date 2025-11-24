# 🧪 Guia de Testes - Módulo Financeiro

**Data:** 24 de Novembro de 2025  
**Status:** Pronto para Testes

---

## ✅ PRÉ-REQUISITOS

Antes de começar os testes, certifique-se de que:

1. ✅ Migrations rodadas: `php artisan migrate`
2. ✅ Seeder executado: `php artisan db:seed --class=FinancialCategoriesSeeder`
3. ✅ Código atualizado: `git pull origin main`
4. ✅ Cache limpo: `php artisan filament:cache-components`

---

## 🎯 TESTE 1: Categorias Financeiras

### Objetivo
Verificar se as 27 categorias foram criadas corretamente

### Passos
1. Acesse **Financeiro > Categorias**
2. Verifique se existem 27 categorias
3. Filtre por tipo: Despesa, Receita, Variação Cambial
4. Tente editar uma categoria do sistema (deve ter toggle desabilitado)
5. Tente deletar uma categoria do sistema (deve falhar)
6. Crie uma nova categoria customizada
7. Delete a categoria customizada (deve funcionar)

### Resultado Esperado
- ✅ 27 categorias listadas
- ✅ Hierarquia visível (pai > filho)
- ✅ Badges coloridos por tipo
- ✅ Categorias do sistema protegidas
- ✅ Categorias customizadas podem ser criadas/deletadas

---

## 🎯 TESTE 2: Automação de Purchase Order

### Objetivo
Verificar se ao aprovar uma PO, cria conta a pagar automaticamente

### Passos
1. Acesse **Compras > Purchase Orders**
2. Crie uma nova PO:
   - Fornecedor: Qualquer
   - Moeda: EUR
   - Valor total: €10.000
   - Payment Term: 30 dias (ou deixe vazio)
3. **Aprove a PO** (mude status para 'approved')
4. Acesse **Financeiro > Contas a Pagar/Receber**
5. Verifique se foi criada uma transação

### Resultado Esperado
- ✅ Transação criada automaticamente
- ✅ Número: `FT-PAY-2025-0001` (ou próximo)
- ✅ Tipo: A Pagar (badge vermelho)
- ✅ Valor: €10.000
- ✅ Categoria: "Compras de Matéria-Prima"
- ✅ Status: Pendente
- ✅ Vencimento: 30 dias a partir de hoje
- ✅ Fornecedor: O mesmo da PO

---

## 🎯 TESTE 3: Automação de Sales Invoice (SEM Parcelas)

### Objetivo
Verificar se ao enviar uma SI, cria conta a receber

### Passos
1. Acesse **Vendas > Sales Invoices**
2. Crie uma nova SI:
   - Cliente: Qualquer
   - Moeda: USD
   - Valor total: $50.000
   - **SEM** Payment Term
3. **Envie a SI** (mude status para 'sent')
4. Acesse **Financeiro > Contas a Pagar/Receber**
5. Verifique se foi criada uma transação

### Resultado Esperado
- ✅ **1 transação** criada
- ✅ Número: `FT-REC-2025-0001`
- ✅ Tipo: A Receber (badge verde)
- ✅ Valor: $50.000
- ✅ Categoria: "Receita de Vendas"
- ✅ Status: Pendente
- ✅ Cliente: O mesmo da SI

---

## 🎯 TESTE 4: Automação de Sales Invoice (COM Parcelas)

### Objetivo
Verificar se ao enviar uma SI com Payment Term, cria múltiplas contas a receber

### Passos
1. Acesse **Configurações > Payment Terms**
2. Crie um Payment Term:
   - Nome: "3 Parcelas"
   - Stages:
     - Stage 1: 33.33%, 30 dias
     - Stage 2: 33.33%, 60 dias
     - Stage 3: 33.34%, 90 dias
3. Acesse **Vendas > Sales Invoices**
4. Crie uma nova SI:
   - Cliente: Qualquer
   - Moeda: USD
   - Valor total: $90.000
   - Payment Term: "3 Parcelas"
5. **Envie a SI**
6. Acesse **Financeiro > Contas a Pagar/Receber**

### Resultado Esperado
- ✅ **3 transações** criadas
- ✅ FT-REC-2025-0002: $30.000 (vence em 30 dias)
- ✅ FT-REC-2025-0003: $30.000 (vence em 60 dias)
- ✅ FT-REC-2025-0004: $30.000 (vence em 90 dias)
- ✅ Todas com status: Pendente
- ✅ Descrição: "Sales Invoice XXX - Parcela 1/3", etc.

---

## 🎯 TESTE 5: Criação Manual de Transação

### Objetivo
Criar uma conta a pagar manualmente (ex: aluguel)

### Passos
1. Acesse **Financeiro > Contas a Pagar/Receber**
2. Clique em **Criar**
3. Preencha:
   - Descrição: "Aluguel Escritório - Dezembro/2025"
   - Tipo: Conta a Pagar
   - Categoria: "Aluguel"
   - Valor: 5000 (R$5.000)
   - Moeda: BRL
   - Data da Transação: Hoje
   - Vencimento: 01/12/2025
4. Salve

### Resultado Esperado
- ✅ Transação criada
- ✅ Número: FT-PAY-2025-XXXX
- ✅ Taxa de câmbio: 1.0 (BRL é moeda base)
- ✅ Valor na moeda base: R$5.000
- ✅ Status: Pendente

---

## 🎯 TESTE 6: Transação Recorrente

### Objetivo
Criar e gerar uma transação recorrente

### Passos
1. Acesse **Financeiro > Transações Recorrentes**
2. Clique em **Criar**
3. Preencha:
   - Nome: "Aluguel Mensal"
   - Descrição: "Aluguel do escritório"
   - Tipo: Conta a Pagar
   - Categoria: "Aluguel"
   - Valor: 5000
   - Moeda: BRL
   - Frequência: Mensal
   - Intervalo: 1
   - Dia do Mês: 1
   - Data de Início: Hoje
   - Próxima Data: Hoje
   - Ativa: Sim
   - Gerar Automaticamente: Sim
4. Salve
5. **Clique na transação criada** para visualizar
6. Verifique a seção "Próximas Ocorrências"
7. Clique em **Gerar Transação Agora** no header

### Resultado Esperado
- ✅ Recorrência criada
- ✅ Preview mostra 12 próximas ocorrências
- ✅ Ao clicar "Gerar Agora":
  - Notificação de sucesso
  - Transação FT-PAY-XXXX criada
  - `next_due_date` atualizado para próximo mês
8. Verifique em **Contas a Pagar/Receber**:
  - ✅ Nova transação criada
  - ✅ Descrição: "Aluguel Mensal"

---

## 🎯 TESTE 7: Pagamento Simples

### Objetivo
Criar um pagamento (sem alocação por enquanto)

### Passos
1. Acesse **Financeiro > Pagamentos/Recebimentos**
2. Clique em **Criar**
3. Preencha:
   - Descrição: "Pagamento Fornecedor X"
   - Tipo: Saída (Pagamento)
   - Conta Bancária: Qualquer
   - Método de Pagamento: Transferência
   - Data: Hoje
   - Valor: 10000 (€10.000)
   - Moeda: EUR
   - Taxas: 50 (€50)
4. Salve

### Resultado Esperado
- ✅ Pagamento criado
- ✅ Número: FP-OUT-2025-0001
- ✅ Tipo: Saída (badge vermelho)
- ✅ Valor: €10.000
- ✅ Taxa de câmbio: Preenchida automaticamente
- ✅ Alocado: €0
- ✅ Não Alocado: €10.000 (badge amarelo)
- ✅ Status: Pendente

---

## 🎯 TESTE 8: Filtros e Busca

### Objetivo
Testar filtros nas listagens

### Passos - Transações
1. Acesse **Financeiro > Contas a Pagar/Receber**
2. Teste filtros:
   - Tipo: A Pagar / A Receber
   - Status: Pendente / Pago
   - Categoria: Selecione uma
   - Apenas Vencidas: Ative
3. Teste busca por número ou descrição

### Passos - Pagamentos
1. Acesse **Financeiro > Pagamentos/Recebimentos**
2. Teste filtros:
   - Tipo: Saída / Entrada
   - Conta Bancária: Selecione uma
   - Status: Pendente / Concluído

### Resultado Esperado
- ✅ Filtros funcionam corretamente
- ✅ Busca retorna resultados relevantes
- ✅ Combinação de filtros funciona

---

## 🎯 TESTE 9: Badges e Cores

### Objetivo
Verificar indicadores visuais

### Passos
1. Acesse **Financeiro > Contas a Pagar/Receber**
2. Verifique cores dos badges:
   - **Tipo:**
     - A Pagar = Vermelho
     - A Receber = Verde
   - **Status:**
     - Pendente = Cinza
     - Parcial = Amarelo
     - Pago = Verde
     - Vencido = Vermelho
3. Verifique coluna "Dias":
   - Positivo (a vencer) = Verde
   - < 7 dias = Amarelo
   - Negativo (vencido) = Vermelho

### Resultado Esperado
- ✅ Cores corretas em todos os badges
- ✅ Indicadores visuais claros
- ✅ Tooltips informativos

---

## 🎯 TESTE 10: Command de Recorrências

### Objetivo
Testar geração automática via command

### Passos
1. Certifique-se de ter uma recorrência ativa com `next_due_date` = hoje
2. Abra terminal e rode:
   ```bash
   php artisan finance:generate-recurring --dry-run
   ```
3. Verifique o output
4. Rode sem --dry-run:
   ```bash
   php artisan finance:generate-recurring
   ```
5. Verifique em **Contas a Pagar/Receber**

### Resultado Esperado
- ✅ Dry-run mostra o que seria gerado
- ✅ Comando real gera transações
- ✅ `next_due_date` atualizado
- ✅ Transações criadas corretamente

---

## 🎯 TESTE 11: Edição e Exclusão

### Objetivo
Testar regras de edição e exclusão

### Passos - Transações
1. Tente editar transação com status "pending": ✅ Deve permitir
2. Marque uma transação como "paid" (via banco de dados)
3. Tente editar: ❌ Botão não deve aparecer
4. Tente deletar transação sem pagamentos: ✅ Deve permitir

### Passos - Categorias
1. Tente deletar categoria do sistema: ❌ Deve falhar
2. Tente deletar categoria com transações: ❌ Deve falhar
3. Tente deletar categoria customizada sem uso: ✅ Deve permitir

### Resultado Esperado
- ✅ Proteções funcionando
- ✅ Mensagens de erro claras
- ✅ Exclusões permitidas apenas quando seguro

---

## 🎯 TESTE 12: Múltiplas Moedas

### Objetivo
Verificar conversão automática para moeda base

### Passos
1. Crie transação em EUR
2. Verifique se "Taxa de Câmbio" foi preenchida automaticamente
3. Verifique se "Valor na Moeda Base" foi calculado
4. Crie transação em USD
5. Verifique conversões
6. Crie transação em BRL (moeda base)
7. Verifique taxa = 1.0

### Resultado Esperado
- ✅ Taxa buscada automaticamente ao selecionar moeda
- ✅ Valor base calculado corretamente
- ✅ BRL tem taxa 1.0
- ✅ Conversões corretas

---

## 📊 CHECKLIST FINAL

| Teste | Status | Observações |
|-------|--------|-------------|
| 1. Categorias | ⏳ | |
| 2. PO → Conta a Pagar | ⏳ | |
| 3. SI → Conta a Receber (simples) | ⏳ | |
| 4. SI → Contas a Receber (parcelas) | ⏳ | |
| 5. Transação Manual | ⏳ | |
| 6. Recorrência | ⏳ | |
| 7. Pagamento | ⏳ | |
| 8. Filtros | ⏳ | |
| 9. Badges | ⏳ | |
| 10. Command | ⏳ | |
| 11. Edição/Exclusão | ⏳ | |
| 12. Múltiplas Moedas | ⏳ | |

---

## 🐛 REPORTANDO BUGS

Se encontrar algum problema:

1. Anote o teste que falhou
2. Descreva o comportamento esperado vs real
3. Tire screenshot se possível
4. Verifique logs: `storage/logs/laravel.log`
5. Reporte com detalhes

---

## ✅ PRÓXIMOS PASSOS APÓS TESTES

Após todos os testes passarem:

1. ⏳ Implementar alocação de pagamentos (M-para-N)
2. ⏳ Criar widgets (FinancialOverview, CashFlowChart)
3. ⏳ Criar relatórios (DRE, Fluxo de Caixa)
4. ⏳ Adicionar permissions/policies
5. ⏳ Criar testes automatizados

---

**Boa sorte nos testes!** 🚀

Qualquer dúvida, consulte a documentação em `/docs/`.

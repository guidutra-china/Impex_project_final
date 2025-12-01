# 🚀 Quick Start - Dashboard Widgets

## Instalação Rápida (3 passos)

### 1️⃣ Pull do GitHub
```bash
git pull origin main
```

### 2️⃣ Limpar Cache
```bash
php artisan optimize:clear
```

### 3️⃣ Acessar Dashboard
```
http://seu-dominio.com/panel
```

---

## ✅ O Que Você Verá

### **3 Widgets Criados:**

1. **RFQ Stats Widget** 📝
   - RFQs Ativas
   - Cotações Recebidas
   - Taxa de Conversão
   - RFQs Este Mês

2. **Purchase Order Stats Widget** 🛒
   - POs Pendentes
   - POs Ativas
   - Em Produção
   - POs Atrasadas
   - Valor em Aberto
   - POs Este Mês

3. **Financial Overview Widget** 💰
   - Contas a Receber
   - Contas a Pagar
   - Fluxo de Caixa
   - Invoices Vencidas
   - Vencem em 30 Dias
   - Vendas Este Mês

---

## 🔐 Ownership Automático

✅ **Usuários regulares**: Veem apenas seus clientes  
✅ **Super Admin**: Vê todos os clientes  
✅ **Roles com can_see_all=true**: Veem tudo  

---

## 📚 Documentação Completa

- `docs/INSTALL_WIDGETS.md` - Guia completo de instalação
- `docs/DASHBOARD_WIDGETS.md` - Documentação técnica
- `docs/IMPROVEMENTS_ROADMAP.md` - Roadmap de melhorias

---

## 🐛 Problema?

```bash
# Limpar cache
php artisan optimize:clear

# Ver logs
tail -f storage/logs/laravel.log

# Regenerar autoload
composer dump-autoload
```

---

**Pronto! 🎉**

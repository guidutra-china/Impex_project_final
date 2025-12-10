# 🤖 AI-Powered Document Import System

## Overview

Sistema de importação inteligente que usa **DeepSeek AI** para analisar e importar documentos Excel e PDF automaticamente, com detecção de estrutura, mapeamento de campos e extração de imagens.

---

## 🎯 Features

### ✅ Suporte Universal de Arquivos
- **Excel** (.xlsx, .xls) - Tabelas estruturadas + fotos embutidas
- **PDF** (.pdf) - Tabelas, texto e imagens

### ✅ Análise Inteligente com IA
- Detecção automática do tipo de documento (Proforma Invoice, Catálogo, etc.)
- Identificação de fornecedor (nome, email, país)
- Sugestão automática de mapeamento de colunas
- Detecção de moeda e tags relevantes
- Extração de metadados

### ✅ Importação Flexível
- **Produtos** (implementado)
- **Fornecedores** (futuro)
- **Clientes** (futuro)
- **Cotações** (futuro)

### ✅ Gestão de Imagens
- Extração de fotos embutidas em Excel
- Extração de imagens de PDF
- Associação automática de imagens aos produtos
- Suporte para URLs de imagens

### ✅ Histórico Completo
- Registro de todas as importações
- Estatísticas detalhadas (sucessos, erros, avisos)
- Visualização de resultados
- Rastreamento de usuário e timestamp

---

## 📁 Arquitetura

```
app/
├── Models/
│   └── ImportHistory.php                    # Model para histórico
│
├── Services/
│   └── AI/
│       ├── DeepSeekService.php              # Cliente API DeepSeek
│       ├── AIFileAnalyzerService.php        # Orquestrador principal
│       ├── DynamicProductImporter.php       # Importador universal
│       └── Parsers/
│           ├── ExcelParser.php              # Parser Excel + imagens
│           └── PDFParser.php                # Parser PDF + imagens
│
└── Filament/
    └── Resources/
        └── DocumentImports/
            ├── DocumentImportResource.php
            ├── Pages/
            │   ├── ListDocumentImports.php
            │   ├── ViewDocumentImport.php
            │   └── CreateDocumentImport.php
            └── Tables/
                └── DocumentImportTable.php
```

---

## 🚀 Como Usar

### 1. **Acesse o Menu**

No Filament Admin:
```
System → Document Imports → New Import
```

### 2. **Upload do Arquivo**

- Selecione o tipo de importação (Products, Suppliers, etc.)
- Faça upload do arquivo Excel ou PDF (máx 20MB)
- A IA analisa automaticamente após o upload

### 3. **Revise a Análise**

A IA mostra:
- ✅ Tipo de documento detectado
- ✅ Número de produtos encontrados
- ✅ Fornecedor identificado
- ✅ Imagens encontradas
- ✅ Mapeamento de colunas sugerido
- ✅ Tags sugeridas

### 4. **Confirme e Importe**

- Revise o resumo
- Clique em "Create & Start Import"
- Acompanhe o progresso
- Veja os resultados detalhados

---

## 📊 Exemplo: Importar Arquivo JGYAN

### Arquivo de Entrada
```
JGYAN-20251203(1).xlsx
- 70 produtos de fitness equipment
- 70 fotos embutidas
- Fornecedor: JiongGong Fitness Equipment Co.,Ltd.
```

### Análise da IA
```json
{
  "document_type": "Proforma Invoice",
  "confidence": 0.95,
  "supplier": {
    "name": "JiongGong Fitness Equipment Co.,Ltd.",
    "country": "China",
    "email": "yan@jinggongfitness.com"
  },
  "products_count": 70,
  "has_images": true,
  "currency": "USD",
  "column_mapping": {
    "A": {"field": "sku", "label": "Model NO"},
    "B": {"field": "name", "label": "PRODUCT"},
    "C": {"field": "photo", "label": "PIC"},
    "D": {"field": "description", "label": "DESCRIPTION"},
    "F": {"field": "price", "label": "UNIT U$"},
    "J": {"field": "gross_weight", "label": "weight(kg)"}
  },
  "suggested_tags": ["Fitness Equipment", "Gym Equipment"]
}
```

### Resultado
```
✅ 70 produtos criados
📷 70 fotos importadas
🏭 Vinculados ao fornecedor: JiongGong Fitness
🏷️ Tags aplicadas: Fitness Equipment
⏱️ Tempo: ~2 minutos
💰 Custo API: ~$0.001
```

---

## ⚙️ Configuração

### 1. **Variáveis de Ambiente**

Adicione no `.env`:
```env
# DeepSeek API
DEEP_SEEK=your_deepseek_api_key_here
# ou
DEEP_SEEK_2=your_deepseek_api_key_here

# Opcional: Customizar endpoint
DEEPSEEK_BASE_URL=https://api.deepseek.com/v1
DEEPSEEK_MODEL=deepseek-chat
```

### 2. **Dependências PHP**

```bash
composer require phpoffice/phpspreadsheet
composer require smalot/pdfparser
```

### 3. **Dependências do Sistema (para PDF)**

```bash
# Para extração de imagens de PDF
sudo apt-get install poppler-utils

# Para OCR (PDF escaneado) - opcional
sudo apt-get install tesseract-ocr tesseract-ocr-eng
```

### 4. **Migração**

```bash
php artisan migrate
```

---

## 🔧 Mapeamento de Campos

### Campos Suportados

| Campo Excel | Campo Produto | Tipo | Descrição |
|-------------|---------------|------|-----------|
| Model NO | `sku` | string | Código do produto |
| PRODUCT | `name` | string | Nome do produto |
| PIC | `avatar` | image | Foto do produto |
| DESCRIPTION | `description` | text | Descrição |
| UNIT U$ | `price` | decimal | Preço (convertido para centavos) |
| weight(kg) | `gross_weight` | decimal | Peso bruto |
| QTY | `moq` | integer | Quantidade mínima |
| HS CODE | `hs_code` | string | Código HS |
| BRAND | `brand` | string | Marca |
| CERTIFICATIONS | `certifications` | text | Certificações |

### Campos Automáticos

- `status` → 'active' (padrão)
- `currency_id` → Detectado pela IA
- `supplier_id` → Criado/vinculado automaticamente
- `tags` → Sugeridos pela IA

---

## 📈 Performance

### Tempos Estimados

| Operação | Tempo |
|----------|-------|
| Upload | 1-2s |
| Análise IA (Excel 70 produtos) | 5-10s |
| Análise IA (PDF 50 produtos) | 10-20s |
| Extração de 70 imagens | 30-60s |
| Importação de 70 produtos | 10-30s |
| **Total (Excel)** | **~1-2 min** |
| **Total (PDF)** | **~2-4 min** |

### Custos DeepSeek API

| Tipo de Arquivo | Tokens | Custo |
|-----------------|--------|-------|
| Excel (70 produtos) | ~5,000 | $0.001 |
| PDF Texto (50 produtos) | ~8,000 | $0.002 |
| PDF Escaneado (30 produtos) | ~15,000 | $0.004 |

**Extremamente barato comparado a OpenAI!** 💸

---

## 🎨 Interface

### Lista de Importações
![List View](docs/images/import-list.png)

- Filtros por tipo, status, data
- Badges coloridos para status
- Estatísticas resumidas
- Ações rápidas

### Wizard de Importação
![Wizard](docs/images/import-wizard.png)

**Passo 1: Upload**
- Seleção de tipo
- Upload de arquivo
- Validação automática

**Passo 2: Análise IA**
- Tipo de documento
- Fornecedor detectado
- Imagens encontradas
- Mapeamento de campos
- Tags sugeridas

**Passo 3: Confirmação**
- Resumo final
- Botão de importação

### Visualização de Resultados
![Results](docs/images/import-results.png)

- Estatísticas completas
- Lista de erros
- Lista de avisos
- Mensagem de resultado

---

## 🔍 Troubleshooting

### Erro: "DeepSeek API key not configured"

**Solução:**
```bash
# Verifique se a chave está no .env
grep DEEP_SEEK .env

# Se não estiver, adicione:
echo "DEEP_SEEK=your_key_here" >> .env

# Limpe o cache
php artisan config:clear
```

### Erro: "pdfimages command not available"

**Solução:**
```bash
# Instale poppler-utils
sudo apt-get update
sudo apt-get install poppler-utils

# Verifique instalação
which pdfimages
```

### Imagens não são importadas

**Causas possíveis:**
1. Permissões de storage
2. Disco cheio
3. Formato de imagem não suportado

**Solução:**
```bash
# Verificar permissões
ls -la storage/app/public/products/

# Criar diretórios se necessário
mkdir -p storage/app/public/products/avatars
mkdir -p storage/app/public/products/import-temp

# Dar permissões
chmod -R 775 storage/app/public/products/
```

### Importação muito lenta

**Otimizações:**
1. Desabilite logs desnecessários
2. Use queue para importações grandes
3. Aumente timeout do PHP
4. Use chunks para processar em lotes

---

## 🚀 Próximos Passos

### Funcionalidades Futuras

1. **Importação de Fornecedores**
   - Análise de catálogos de fornecedores
   - Criação automática de contatos

2. **Importação de Clientes**
   - Listas de clientes
   - Histórico de pedidos

3. **Importação de Cotações**
   - Comparação automática de preços
   - Análise de melhores ofertas

4. **Wizard Avançado**
   - Ajuste manual de mapeamento
   - Preview de dados antes de importar
   - Validação customizada

5. **Queue Processing**
   - Importações assíncronas
   - Notificações por email
   - Progresso em tempo real

6. **Templates Salvos**
   - Salvar mapeamentos para reutilização
   - Importações recorrentes automatizadas

---

## 📚 Referências

- [DeepSeek API Documentation](https://platform.deepseek.com/docs)
- [PhpSpreadsheet Documentation](https://phpspreadsheet.readthedocs.io/)
- [Smalot PDF Parser](https://github.com/smalot/pdfparser)
- [Filament Documentation](https://filamentphp.com/docs)

---

## 💡 Dicas

1. **Comece Pequeno:** Teste com 2-3 produtos primeiro
2. **Use URLs Públicas:** Para fotos via URL, use links acessíveis
3. **Verifique Fornecedores:** Certifique-se que nomes correspondem exatamente
4. **Backup Primeiro:** Sempre faça backup antes de importações grandes
5. **Revise Avisos:** Avisos não param a importação, mas indicam problemas
6. **Monitore Custos:** DeepSeek é barato, mas monitore uso em produção
7. **Logs São Seus Amigos:** Verifique `storage/logs/laravel.log` em caso de problemas

---

## 🤝 Contribuindo

Este sistema foi projetado para ser extensível. Para adicionar novos tipos de importação:

1. Crie um novo Importer em `app/Services/AI/`
2. Adicione o tipo em `CreateDocumentImport.php`
3. Atualize o `DynamicProductImporter` se necessário
4. Documente o novo tipo

---

## 📝 Changelog

### v1.0.0 (2025-12-10)
- ✅ Implementação inicial
- ✅ Suporte para Excel e PDF
- ✅ Integração com DeepSeek AI
- ✅ Importação de produtos
- ✅ Extração de imagens
- ✅ Histórico completo
- ✅ Interface Filament

---

**Desenvolvido com ❤️ usando DeepSeek AI**

# Database Seeders and Factories

Este documento descreve a estrutura de factories e seeders criados para popular o banco de dados com dados de teste realistas.

## Estrutura Criada

### Factories

As seguintes factories foram criadas em `database/factories/`:

1. **ClientFactory** - Gera clientes com:
   - Código único de 2 letras (extraído do nome)
   - Nome da empresa
   - Dados de contato (email, telefone)
   - Endereço completo
   - Status ativo/inativo

2. **SupplierFactory** - Gera fornecedores com:
   - Código único de 3 letras (extraído do nome)
   - Nome da empresa
   - Dados de contato
   - Endereço completo
   - Moeda (USD, EUR, GBP, CNY, JPY, BRL)
   - Termos de pagamento
   - Status ativo/inativo

3. **CategoryFactory** - Gera categorias de produtos com:
   - Nome único
   - Descrição
   - Status ativo/inativo

4. **TagFactory** - Gera tags para classificação com:
   - Nome único
   - Cor (hex)

5. **ComponentFactory** - Gera componentes com:
   - Nome
   - Descrição
   - SKU único
   - Preço em centavos

6. **ProductFactory** - Gera produtos com:
   - Nome
   - Descrição
   - SKU único
   - Preço em centavos
   - Categoria (relacionamento)
   - Status ativo/inativo

7. **CategoryFeatureFactory** - Gera características de categoria com:
   - Nome
   - Tipo (text, number, select, boolean)
   - Opções (para tipo select)
   - Campo obrigatório ou não
   - Categoria (relacionamento)

8. **ProductFeatureFactory** - Gera valores de características para produtos

### DatabaseSeeder

O seeder principal (`database/seeders/DatabaseSeeder.php`) cria:

- **1 usuário admin** (email: admin@impex.com)
- **6 moedas** (USD, EUR, GBP, CNY, JPY, BRL)
- **8 categorias** de produtos
- **10 tags** para classificação
- **15 clientes** com códigos únicos
- **20 fornecedores** com:
  - 1-4 tags aleatórias cada
  - 1-3 categorias aleatórias cada
- **30 componentes**
- **Características de categoria** (2-5 por categoria)
- **50 produtos** com:
  - Características baseadas na categoria
  - 2-8 componentes cada (com quantidades)

## Como Usar

### 1. Resetar e Popular o Banco de Dados

```bash
php artisan migrate:fresh --seed
```

Este comando irá:
- Dropar todas as tabelas
- Executar todas as migrations
- Executar o DatabaseSeeder

### 2. Apenas Executar os Seeders (sem resetar)

```bash
php artisan db:seed
```

### 3. Executar um Seeder Específico

```bash
php artisan db:seed --class=DatabaseSeeder
```

## Relacionamentos Criados

### Clientes (Clients)
- Cada cliente tem um código único de 2 letras
- Usado para gerar números de RFQ no formato: `[2 letras][ano][número]` (ex: AM250004)

### Fornecedores (Suppliers)
- Cada fornecedor tem um código único de 3 letras
- Relacionado com múltiplas tags (many-to-many)
- Relacionado com múltiplas categorias (many-to-many)
- Cada fornecedor tem uma moeda padrão
- Usado para gerar números de cotação no formato: `[3 letras][ano 2 dígitos][sequencial]_Rev[N]` (ex: FRE250004_Rev1)

### Produtos (Products)
- Cada produto pertence a uma categoria
- Cada produto tem múltiplos componentes (many-to-many com quantidade)
- Cada produto tem características baseadas na categoria
- SKU único para cada produto

### Características (Features)
- CategoryFeatures definem quais características uma categoria pode ter
- ProductFeatures armazenam os valores das características para cada produto
- Tipos suportados: text, number, select, boolean

### Componentes (Components)
- Relacionados com produtos através de tabela pivot
- Cada relacionamento armazena a quantidade necessária
- Possuem SKU e preço próprios

## Dados Gerados

### Moedas Disponíveis
- USD (US Dollar) - $
- EUR (Euro) - €
- GBP (British Pound) - £
- CNY (Chinese Yuan) - ¥
- JPY (Japanese Yen) - ¥
- BRL (Brazilian Real) - R$

### Exemplos de Dados

**Cliente:**
```
Nome: Acme Corporation
Código: AC
Email: contact@acme.com
Telefone: +1 555-0123
```

**Fornecedor:**
```
Nome: Transportadora Express
Código: TRA
Email: sales@transportadora.com
Moeda: BRL
Termos: Net 30
```

**Produto:**
```
Nome: Widget Premium
SKU: WID-12345
Categoria: Electronics
Componentes: 5 (com quantidades)
Características: Voltage: 220V, Color: Blue
```

## Testando Quote Comparison

Após executar o seeder, você terá:

1. **Múltiplos fornecedores** com diferentes moedas
2. **Produtos variados** com componentes e características
3. **Categorias** para filtrar produtos em Order Items

Para testar a funcionalidade de Quote Comparison:

1. Crie um RFQ (Order) selecionando um cliente
2. Adicione items ao RFQ selecionando produtos
3. Crie Supplier Quotes para diferentes fornecedores
4. Adicione Quote Items vinculando aos Order Items
5. Acesse a página de Quote Comparison para ver a comparação

## Notas Importantes

### Códigos Únicos
- O seeder garante que códigos de clientes sejam únicos
- Se houver conflito, adiciona um número ao final (ex: AC1, AC2)

### Preços
- Todos os preços são armazenados em **centavos** (integer)
- Exemplo: $10.50 = 1050 centavos
- A conversão para exibição é feita automaticamente

### Soft Deletes
- Alguns models usam soft deletes
- Os registros não são removidos fisicamente do banco

### Performance
- O seeder cria 50 produtos com relacionamentos
- Pode levar alguns segundos para completar
- Para mais dados, ajuste os números no DatabaseSeeder.php

## Customização

Para ajustar a quantidade de registros, edite `database/seeders/DatabaseSeeder.php`:

```php
// Altere os números conforme necessário
$categories = Category::factory(8)->create();  // 8 categorias
$tags = Tag::factory(10)->create();            // 10 tags
// ... etc
```

## Troubleshooting

### Erro de Unique Constraint
Se encontrar erros de constraint única:
```bash
php artisan migrate:fresh --seed
```

### Erro de Foreign Key
Certifique-se de que as migrations estão na ordem correta:
1. Tabelas base (users, currencies, categories, etc.)
2. Tabelas com foreign keys

### Dados Não Aparecem
Verifique se o seeder foi executado:
```bash
php artisan db:seed --class=DatabaseSeeder
```

## Próximos Passos

Após popular o banco com dados de teste, você pode:

1. **Testar criação de RFQs** com os clientes criados
2. **Testar criação de Supplier Quotes** com os fornecedores criados
3. **Testar Quote Comparison** com múltiplas cotações
4. **Verificar conversão de moedas** com fornecedores de diferentes países
5. **Testar filtros de categoria** ao adicionar items em Orders

## Estrutura de Arquivos

```
database/
├── factories/
│   ├── CategoryFactory.php
│   ├── CategoryFeatureFactory.php
│   ├── ClientFactory.php
│   ├── ComponentFactory.php
│   ├── ProductFactory.php
│   ├── SupplierFactory.php
│   ├── TagFactory.php
│   └── UserFactory.php
└── seeders/
    ├── DatabaseSeeder.php
    └── README.md (este arquivo)
```

## Contato

Para dúvidas ou problemas, consulte a documentação do Laravel sobre [Database Seeding](https://laravel.com/docs/seeding).

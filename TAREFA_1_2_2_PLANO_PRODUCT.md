# Tarefa 1.2.2: Refatoração do Model Product

## Objetivo
Reduzir a complexidade do model `Product` (519 linhas) através da extração de lógica de negócio em classes especializadas, mantendo a interface pública do model intacta.

## Análise Atual

### Responsabilidades Identificadas

1. **Cálculos de Custos e Preços** (linhas 246-286)
   - `calculateBomMaterialCost()`
   - `calculateManufacturingCost()`
   - `calculateAndUpdateCosts()`
   - Conversão para dólares (atributos acessores)

2. **Duplicação de Produtos** (linhas 299-399)
   - `duplicate()`
   - `duplicateAvatar()`
   - Lógica complexa com transações

3. **Cálculos de Dimensões e Volume** (linhas 485-516)
   - `getProductCbmAttribute()`
   - `getInnerBoxCbmAttribute()`
   - `booted()` - Cálculo automático de CBM

4. **Formatação de Valores** (linhas 474-480)
   - `getFormattedPriceAttribute()`
   - Conversão de centavos para dólares

## Plano de Refatoração

### 1. ProductCostCalculator
**Responsabilidade:** Encapsular toda a lógica de cálculo de custos e preços

**Métodos:**
- `calculateBomMaterialCost(Product $product): int`
- `calculateManufacturingCost(Product $product): void`
- `calculateSellingPrice(Product $product): int`
- `getBomMaterialCostDollars(Product $product): float`
- `getDirectLaborCostDollars(Product $product): float`
- `getDirectOverheadCostDollars(Product $product): float`
- `getTotalManufacturingCostDollars(Product $product): float`
- `getCalculatedSellingPriceDollars(Product $product): float`

**Benefícios:**
- Lógica de cálculo isolada e testável
- Reutilizável em múltiplos contextos
- Fácil de manter e estender

### 2. ProductDuplicator
**Responsabilidade:** Encapsular a lógica de duplicação de produtos

**Métodos:**
- `duplicate(Product $product, array $options = []): Product`
- `duplicateAvatar(Product $product): ?string`
- `duplicateBomItems(Product $newProduct, Product $original): void`
- `duplicateFeatures(Product $newProduct, Product $original): void`
- `duplicateTags(Product $newProduct, Product $original): void`

**Benefícios:**
- Lógica de duplicação isolada
- Fácil de testar cada parte
- Transações gerenciadas corretamente

### 3. ProductDimensionCalculator
**Responsabilidade:** Encapsular cálculos de dimensões e volume

**Métodos:**
- `getProductCbm(Product $product): ?float`
- `getInnerBoxCbm(Product $product): ?float`
- `calculateCartonCbm(Product $product): ?float`

**Benefícios:**
- Lógica de cálculo de volume isolada
- Reutilizável em relatórios e cálculos
- Testável isoladamente

### 4. ProductFormatter
**Responsabilidade:** Encapsular formatação de valores do produto

**Métodos:**
- `formatPrice(int $price): string`
- `formatWeight(float $weight): string`
- `formatDimensions(float $length, float $width, float $height): string`

**Benefícios:**
- Formatação centralizada
- Fácil de manter padrões de formatação
- Reutilizável em múltiplos contextos

### 5. HasProductCosts Trait
**Responsabilidade:** Encapsular métodos relacionados a custos

**Métodos:**
- `calculateManufacturingCost(): void` (delegando para ProductCostCalculator)
- `calculateAndUpdateCosts(): void`
- Acessores de dólares

**Benefícios:**
- Mantém interface pública do model
- Delega para classes especializadas
- Fácil de usar em models relacionados

### 6. HasProductDimensions Trait
**Responsabilidade:** Encapsular métodos relacionados a dimensões

**Métodos:**
- Acessores de CBM
- `booted()` - Cálculo automático

**Benefícios:**
- Dimensões isoladas
- Reutilizável em outros models

## Impacto Esperado

| Métrica | Antes | Depois | Melhoria |
|---------|-------|--------|----------|
| Linhas do Model | 519 | ~200 | 61% redução |
| Responsabilidades | 4+ | 1 | Separação clara |
| Testabilidade | Baixa | Alta | Classes isoladas |
| Reutilização | Baixa | Alta | Classes especializadas |

## Implementação

### Fase 1: Criar Classes Especializadas
1. ProductCostCalculator
2. ProductDuplicator
3. ProductDimensionCalculator
4. ProductFormatter

### Fase 2: Criar Traits
1. HasProductCosts
2. HasProductDimensions

### Fase 3: Refatorar Model Product
1. Adicionar traits
2. Atualizar métodos para delegar
3. Manter compatibilidade retroativa

### Fase 4: Testes
1. Testes unitários para cada classe
2. Testes de integração para Product
3. Verificar que nada quebrou

## Tempo Estimado
- Implementação: 3-4 horas
- Testes: 2-3 horas
- Total: 5-7 horas

## Prioridade
**Alta** - Product é um dos models mais complexos e frequentemente usado

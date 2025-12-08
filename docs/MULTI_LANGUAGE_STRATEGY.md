# 🌍 Estratégia Multi-Language para Impex Project

## 📋 **Índice**
1. [Visão Geral](#visão-geral)
2. [Arquitetura Proposta](#arquitetura-proposta)
3. [Fluxo de Implementação](#fluxo-de-implementação)
4. [Estrutura de Arquivos](#estrutura-de-arquivos)
5. [Idiomas Suportados](#idiomas-suportados)
6. [Áreas de Tradução](#áreas-de-tradução)
7. [Implementação Técnica](#implementação-técnica)
8. [Roadmap de Implementação](#roadmap-de-implementação)
9. [Manutenção e Boas Práticas](#manutenção-e-boas-práticas)

---

## 🎯 **Visão Geral**

### **Objetivo**
Transformar o sistema Impex em uma aplicação multi-idioma, permitindo que usuários de diferentes países utilizem o sistema em sua língua nativa.

### **Tecnologias**
- **Laravel 12** - Sistema de tradução nativo
- **Filament 4** - Suporte nativo a multi-language
- **Spatie Laravel Translatable** (opcional) - Para conteúdo dinâmico no banco

### **Idiomas Prioritários**
1. 🇧🇷 **Português (pt_BR)** - Idioma padrão atual
2. 🇺🇸 **Inglês (en)** - Internacional
3. 🇨🇳 **Chinês Simplificado (zh_CN)** - Fornecedores
4. 🇪🇸 **Espanhol (es)** - América Latina

---

## 🏗️ **Arquitetura Proposta**

### **1. Camadas de Tradução**

```
┌─────────────────────────────────────────────────────┐
│                  USER INTERFACE                     │
│  (Filament Admin Panel + PDFs + Excel + Emails)    │
└─────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────┐
│              TRANSLATION LAYER                      │
│  • Laravel Translation Files (lang/)                │
│  • Filament Language Files                          │
│  • Custom Translation Helpers                       │
└─────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────┐
│              DATABASE CONTENT                       │
│  • Static: Translation files                        │
│  • Dynamic: Translatable columns (optional)         │
└─────────────────────────────────────────────────────┘
```

### **2. Tipos de Conteúdo**

| Tipo | Exemplos | Estratégia |
|------|----------|------------|
| **Interface (UI)** | Botões, labels, menus | Arquivos de tradução |
| **Validações** | Mensagens de erro | Laravel validation.php |
| **Notificações** | Alerts, toasts | Filament notifications |
| **Documentos** | PDFs, Excel | Templates com traduções |
| **Emails** | Notificações por email | Blade templates traduzidos |
| **Conteúdo Dinâmico** | Produtos, descrições | Spatie Translatable (opcional) |

---

## 🔄 **Fluxo de Implementação**

### **Fase 1: Preparação (1-2 dias)**
```
1. Instalar pacotes necessários
   ├─ composer require filament/spatie-laravel-translatable-plugin
   └─ composer require spatie/laravel-translatable (opcional)

2. Configurar idiomas no Laravel
   ├─ config/app.php (locale, fallback_locale)
   └─ Criar estrutura lang/

3. Configurar Filament
   ├─ app/Providers/Filament/AdminPanelProvider.php
   └─ Adicionar locale switcher
```

### **Fase 2: Extração de Strings (2-3 dias)**
```
1. Identificar todas as strings hard-coded
   ├─ Filament Resources
   ├─ Schemas (Forms)
   ├─ Tables
   ├─ Actions
   └─ Notifications

2. Substituir por translation keys
   └─ 'Name' → __('fields.name')
```

### **Fase 3: Criação de Arquivos de Tradução (3-5 dias)**
```
1. Criar estrutura de arquivos
   lang/
   ├─ en/
   │  ├─ common.php
   │  ├─ fields.php
   │  ├─ resources.php
   │  ├─ navigation.php
   │  ├─ actions.php
   │  ├─ notifications.php
   │  └─ documents.php
   ├─ pt_BR/
   │  └─ (mesma estrutura)
   ├─ zh_CN/
   │  └─ (mesma estrutura)
   └─ es/
      └─ (mesma estrutura)

2. Traduzir conteúdo
   └─ Contratar tradutor ou usar serviço profissional
```

### **Fase 4: Documentos (PDFs/Excel) (2-3 dias)**
```
1. Adaptar templates de PDF
   ├─ Usar __() nos Blade templates
   └─ Criar versões traduzidas

2. Adaptar Excel services
   ├─ Usar translation keys nos headers
   └─ Adaptar formatação por locale
```

### **Fase 5: Conteúdo Dinâmico (3-5 dias) - OPCIONAL**
```
1. Adicionar Spatie Translatable aos models
   ├─ Product (name, description)
   ├─ Customer (notes)
   └─ CompanySetting (company_name, address)

2. Migrar dados existentes
   └─ Script de migração para formato translatable
```

### **Fase 6: Testes e Ajustes (2-3 dias)**
```
1. Testar todas as telas em todos os idiomas
2. Verificar PDFs e Excel
3. Testar emails e notificações
4. Ajustar formatação (datas, números, moedas)
```

---

## 📁 **Estrutura de Arquivos**

### **Estrutura Proposta**

```
lang/
├── en/                          # Inglês
│   ├── common.php              # Termos comuns (Yes, No, Save, Cancel)
│   ├── fields.php              # Labels de campos (Name, Email, Address)
│   ├── resources.php           # Nomes de recursos
│   │   ├── 'customer' => 'Customer'
│   │   ├── 'product' => 'Product'
│   │   └── 'shipment' => 'Shipment'
│   ├── navigation.php          # Menu de navegação
│   ├── actions.php             # Ações (Create, Edit, Delete)
│   ├── notifications.php       # Mensagens de sucesso/erro
│   ├── validation.php          # Mensagens de validação
│   ├── documents.php           # Termos de documentos (Invoice, Packing List)
│   └── auth.php               # Autenticação
│
├── pt_BR/                      # Português Brasil
│   └── (mesma estrutura)
│
├── zh_CN/                      # Chinês Simplificado
│   └── (mesma estrutura)
│
└── es/                         # Espanhol
    └── (mesma estrutura)
```

### **Exemplo: `lang/en/fields.php`**

```php
<?php

return [
    // Common fields
    'name' => 'Name',
    'email' => 'Email',
    'phone' => 'Phone',
    'address' => 'Address',
    'city' => 'City',
    'state' => 'State',
    'country' => 'Country',
    'zip' => 'ZIP Code',
    
    // Customer fields
    'customer_name' => 'Customer Name',
    'customer_code' => 'Customer Code',
    'tax_id' => 'Tax ID',
    
    // Product fields
    'product_name' => 'Product Name',
    'product_code' => 'Product Code',
    'supplier_code' => 'Supplier Code',
    'hs_code' => 'HS Code',
    'net_weight' => 'Net Weight',
    'gross_weight' => 'Gross Weight',
    'volume' => 'Volume',
    'pcs_per_carton' => 'Pcs per Carton',
    
    // Shipment fields
    'shipment_number' => 'Shipment Number',
    'origin_port' => 'Port of Loading',
    'destination_port' => 'Port of Discharge',
    'final_destination' => 'Final Destination',
    'bl_number' => 'B/L Number',
    'container_numbers' => 'Container Numbers',
    
    // Invoice fields
    'invoice_number' => 'Invoice Number',
    'invoice_date' => 'Invoice Date',
    'payment_terms' => 'Payment Terms',
    'bank_information' => 'Bank Information',
    
    // Packing List fields
    'packing_list_number' => 'Packing List Number',
    'packing_date' => 'Packing Date',
    'cartons' => 'Cartons',
    'qty_carton' => 'Qty/Carton',
    
    // Common
    'quantity' => 'Quantity',
    'price' => 'Price',
    'total' => 'Total',
    'notes' => 'Notes',
    'status' => 'Status',
    'created_at' => 'Created At',
    'updated_at' => 'Updated At',
];
```

### **Exemplo: `lang/pt_BR/fields.php`**

```php
<?php

return [
    // Common fields
    'name' => 'Nome',
    'email' => 'E-mail',
    'phone' => 'Telefone',
    'address' => 'Endereço',
    'city' => 'Cidade',
    'state' => 'Estado',
    'country' => 'País',
    'zip' => 'CEP',
    
    // Customer fields
    'customer_name' => 'Nome do Cliente',
    'customer_code' => 'Código do Cliente',
    'tax_id' => 'CNPJ/CPF',
    
    // Product fields
    'product_name' => 'Nome do Produto',
    'product_code' => 'Código do Produto',
    'supplier_code' => 'Código do Fornecedor',
    'hs_code' => 'Código NCM',
    'net_weight' => 'Peso Líquido',
    'gross_weight' => 'Peso Bruto',
    'volume' => 'Volume',
    'pcs_per_carton' => 'Pçs por Caixa',
    
    // ... resto das traduções
];
```

---

## 🌐 **Idiomas Suportados**

### **Prioridade 1 (Essenciais)**

| Idioma | Código | Motivo | Complexidade |
|--------|--------|--------|--------------|
| 🇧🇷 Português BR | `pt_BR` | Idioma atual do sistema | ⭐ Baixa (já existe) |
| 🇺🇸 Inglês | `en` | Internacional, padrão global | ⭐⭐ Média |
| 🇨🇳 Chinês Simplificado | `zh_CN` | Fornecedores, fabricantes | ⭐⭐⭐ Alta |

### **Prioridade 2 (Expansão)**

| Idioma | Código | Motivo | Complexidade |
|--------|--------|--------|--------------|
| 🇪🇸 Espanhol | `es` | América Latina | ⭐⭐ Média |
| 🇫🇷 Francês | `fr` | Europa, África | ⭐⭐ Média |
| 🇩🇪 Alemão | `de` | Europa | ⭐⭐ Média |

---

## 📦 **Áreas de Tradução**

### **1. Filament Admin Panel**

#### **Navigation (Menu)**
```php
// Antes
->label('Customers')

// Depois
->label(__('navigation.customers'))
```

#### **Form Fields**
```php
// Antes
TextInput::make('name')
    ->label('Name')
    ->placeholder('Enter customer name')

// Depois
TextInput::make('name')
    ->label(__('fields.name'))
    ->placeholder(__('placeholders.enter_customer_name'))
```

#### **Table Columns**
```php
// Antes
TextColumn::make('name')
    ->label('Name')

// Depois
TextColumn::make('name')
    ->label(__('fields.name'))
```

#### **Actions**
```php
// Antes
->label('Create Customer')

// Depois
->label(__('actions.create_customer'))
```

#### **Notifications**
```php
// Antes
Notification::make()
    ->title('Customer created successfully')
    ->success()

// Depois
Notification::make()
    ->title(__('notifications.customer_created'))
    ->success()
```

### **2. PDFs e Excel**

#### **PDF Templates (Blade)**
```blade
{{-- Antes --}}
<h1>COMMERCIAL INVOICE</h1>

{{-- Depois --}}
<h1>{{ __('documents.commercial_invoice') }}</h1>
```

#### **Excel Services**
```php
// Antes
$headers = ['No.', 'Product Description', 'Qty', 'Price'];

// Depois
$headers = [
    __('documents.no'),
    __('documents.product_description'),
    __('documents.qty'),
    __('documents.price'),
];
```

### **3. Emails**

```blade
{{-- resources/views/emails/shipment-notification.blade.php --}}
<h1>{{ __('emails.shipment_notification') }}</h1>
<p>{{ __('emails.shipment_ready', ['number' => $shipment->shipment_number]) }}</p>
```

---

## 🔧 **Implementação Técnica**

### **1. Configuração do Laravel**

#### **config/app.php**
```php
'locale' => 'pt_BR',
'fallback_locale' => 'en',
'available_locales' => ['en', 'pt_BR', 'zh_CN', 'es'],
```

### **2. Configuração do Filament**

#### **app/Providers/Filament/AdminPanelProvider.php**
```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        ->default()
        ->id('admin')
        ->path('admin')
        ->login()
        ->colors([
            'primary' => Color::Blue,
        ])
        // Multi-language configuration
        ->locales([
            'en' => 'English',
            'pt_BR' => 'Português (Brasil)',
            'zh_CN' => '简体中文',
            'es' => 'Español',
        ])
        ->defaultLocale('pt_BR')
        ->sidebarCollapsibleOnDesktop()
        ->brandName('Impex System');
}
```

### **3. Middleware de Locale**

#### **app/Http/Middleware/SetLocale.php**
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        // Priority:
        // 1. User preference (from database)
        // 2. Session
        // 3. Browser language
        // 4. Default locale
        
        $locale = null;
        
        // 1. User preference
        if (auth()->check() && auth()->user()->locale) {
            $locale = auth()->user()->locale;
        }
        
        // 2. Session
        if (!$locale && Session::has('locale')) {
            $locale = Session::get('locale');
        }
        
        // 3. Browser language
        if (!$locale) {
            $locale = $request->getPreferredLanguage(config('app.available_locales'));
        }
        
        // 4. Default
        if (!$locale) {
            $locale = config('app.locale');
        }
        
        App::setLocale($locale);
        
        return $next($request);
    }
}
```

#### **Registrar no Kernel**
```php
// app/Http/Kernel.php
protected $middlewareGroups = [
    'web' => [
        // ...
        \App\Http\Middleware\SetLocale::class,
    ],
];
```

### **4. Adicionar Locale ao User Model**

#### **Migration**
```php
Schema::table('users', function (Blueprint $table) {
    $table->string('locale', 10)->default('pt_BR')->after('email');
});
```

#### **User Model**
```php
protected $fillable = [
    'name',
    'email',
    'password',
    'locale',
];
```

### **5. Locale Switcher no Filament**

#### **app/Filament/Pages/Settings.php**
```php
use Filament\Forms\Components\Select;

Select::make('locale')
    ->label(__('fields.language'))
    ->options([
        'en' => 'English',
        'pt_BR' => 'Português (Brasil)',
        'zh_CN' => '简体中文',
        'es' => 'Español',
    ])
    ->default(auth()->user()->locale ?? 'pt_BR')
    ->afterStateUpdated(function ($state) {
        auth()->user()->update(['locale' => $state]);
        session()->put('locale', $state);
    })
```

### **6. Helper Functions**

#### **app/Helpers/TranslationHelper.php**
```php
<?php

namespace App\Helpers;

use Illuminate\Support\Facades\App;

class TranslationHelper
{
    /**
     * Get translated document title
     */
    public static function documentTitle(string $type, string $locale = null): string
    {
        $locale = $locale ?? App::getLocale();
        
        return match($type) {
            'commercial_invoice' => __('documents.commercial_invoice', [], $locale),
            'packing_list' => __('documents.packing_list', [], $locale),
            'proforma_invoice' => __('documents.proforma_invoice', [], $locale),
            default => $type,
        };
    }
    
    /**
     * Format date according to locale
     */
    public static function formatDate($date, string $locale = null): string
    {
        $locale = $locale ?? App::getLocale();
        
        return match($locale) {
            'en' => $date->format('m/d/Y'),
            'pt_BR' => $date->format('d/m/Y'),
            'zh_CN' => $date->format('Y年m月d日'),
            'es' => $date->format('d/m/Y'),
            default => $date->format('Y-m-d'),
        };
    }
    
    /**
     * Format number according to locale
     */
    public static function formatNumber(float $number, int $decimals = 2, string $locale = null): string
    {
        $locale = $locale ?? App::getLocale();
        
        return match($locale) {
            'en' => number_format($number, $decimals, '.', ','),
            'pt_BR' => number_format($number, $decimals, ',', '.'),
            'zh_CN' => number_format($number, $decimals, '.', ','),
            'es' => number_format($number, $decimals, ',', '.'),
            default => number_format($number, $decimals),
        };
    }
}
```

### **7. Conteúdo Dinâmico (OPCIONAL)**

#### **Instalar Spatie Translatable**
```bash
composer require spatie/laravel-translatable
```

#### **Configurar Model**
```php
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use HasTranslations;
    
    public $translatable = ['name', 'description'];
    
    protected $fillable = [
        'name', // JSON: {"en": "LED Light", "pt_BR": "Luz LED", "zh_CN": "LED灯"}
        'description',
        'code',
        // ...
    ];
}
```

#### **Usar no Filament**
```php
use Filament\Forms\Components\Tabs;

Tabs::make('Translations')
    ->tabs([
        Tabs\Tab::make('English')
            ->schema([
                TextInput::make('name.en')->label('Name (EN)'),
                Textarea::make('description.en')->label('Description (EN)'),
            ]),
        Tabs\Tab::make('Português')
            ->schema([
                TextInput::make('name.pt_BR')->label('Nome (PT)'),
                Textarea::make('description.pt_BR')->label('Descrição (PT)'),
            ]),
        Tabs\Tab::make('中文')
            ->schema([
                TextInput::make('name.zh_CN')->label('名称 (ZH)'),
                Textarea::make('description.zh_CN')->label('描述 (ZH)'),
            ]),
    ])
```

---

## 📅 **Roadmap de Implementação**

### **Sprint 1: Fundação (1 semana)**
- [ ] Instalar pacotes necessários
- [ ] Configurar Laravel e Filament para multi-language
- [ ] Criar estrutura de diretórios `lang/`
- [ ] Adicionar campo `locale` ao User model
- [ ] Implementar middleware SetLocale
- [ ] Criar locale switcher no Filament

### **Sprint 2: Interface Admin (2 semanas)**
- [ ] Extrair strings de Navigation
- [ ] Extrair strings de Resources
- [ ] Extrair strings de Forms (Schemas)
- [ ] Extrair strings de Tables
- [ ] Extrair strings de Actions
- [ ] Extrair strings de Notifications
- [ ] Criar arquivos de tradução para EN

### **Sprint 3: Documentos (1-2 semanas)**
- [ ] Adaptar templates de Commercial Invoice (PDF)
- [ ] Adaptar templates de Packing List (PDF)
- [ ] Adaptar Excel services
- [ ] Criar helpers de formatação (datas, números)
- [ ] Testar geração de documentos em múltiplos idiomas

### **Sprint 4: Traduções (2-3 semanas)**
- [ ] Traduzir para Português (revisar/completar)
- [ ] Traduzir para Chinês (contratar tradutor)
- [ ] Traduzir para Espanhol (contratar tradutor)
- [ ] Revisar todas as traduções
- [ ] Testar em todos os idiomas

### **Sprint 5: Conteúdo Dinâmico (1-2 semanas) - OPCIONAL**
- [ ] Implementar Spatie Translatable
- [ ] Migrar models (Product, Customer, etc.)
- [ ] Criar interfaces de tradução no Filament
- [ ] Migrar dados existentes

### **Sprint 6: Testes e Refinamento (1 semana)**
- [ ] Testes de interface em todos os idiomas
- [ ] Testes de documentos (PDFs/Excel)
- [ ] Testes de emails e notificações
- [ ] Ajustes de UX
- [ ] Documentação final

**TOTAL: 8-11 semanas (2-3 meses)**

---

## 🎯 **Manutenção e Boas Práticas**

### **1. Convenções de Nomenclatura**

```php
// ✅ BOM - Específico e organizado
__('fields.customer_name')
__('actions.create_customer')
__('notifications.customer_created_success')

// ❌ RUIM - Genérico e confuso
__('name')
__('create')
__('success')
```

### **2. Organização de Arquivos**

```
lang/
├── en/
│   ├── common.php          # Termos usados em todo o sistema
│   ├── fields.php          # Labels de campos
│   ├── resources/          # Por recurso
│   │   ├── customer.php
│   │   ├── product.php
│   │   └── shipment.php
│   └── documents/          # Por tipo de documento
│       ├── commercial_invoice.php
│       └── packing_list.php
```

### **3. Fallback Inteligente**

```php
// Se tradução não existir, mostrar key legível
__('fields.customer_name') // → "Customer Name" (se não traduzido)

// Usar fallback_locale
config('app.fallback_locale' => 'en')
```

### **4. Testes Automatizados**

```php
// tests/Feature/TranslationTest.php
public function test_all_translation_keys_exist()
{
    $locales = ['en', 'pt_BR', 'zh_CN'];
    
    foreach ($locales as $locale) {
        App::setLocale($locale);
        
        // Verificar se todas as keys existem
        $this->assertNotEquals(
            'fields.customer_name',
            __('fields.customer_name')
        );
    }
}
```

### **5. Documentação para Desenvolvedores**

```php
/**
 * SEMPRE use translation keys para strings visíveis ao usuário
 * 
 * ✅ CORRETO:
 * ->label(__('fields.name'))
 * 
 * ❌ ERRADO:
 * ->label('Name')
 */
```

### **6. Ferramentas Úteis**

| Ferramenta | Uso | Link |
|------------|-----|------|
| **Laravel Lang** | Traduções prontas do Laravel | https://github.com/Laravel-Lang/lang |
| **Filament Translations** | Traduções do Filament | https://github.com/filamentphp/filament |
| **Poedit** | Editor de traduções | https://poedit.net/ |
| **DeepL API** | Tradução automática de qualidade | https://www.deepl.com/pro-api |
| **Google Translate API** | Tradução automática | https://cloud.google.com/translate |

---

## 📊 **Estimativa de Esforço**

### **Por Área**

| Área | Strings Estimadas | Tempo (dias) |
|------|-------------------|--------------|
| Navigation | ~20 | 0.5 |
| Fields | ~200 | 2 |
| Resources | ~100 | 1.5 |
| Actions | ~50 | 1 |
| Notifications | ~80 | 1.5 |
| Validations | ~100 | 1.5 |
| Documents (PDF/Excel) | ~150 | 3 |
| Emails | ~30 | 1 |
| **TOTAL** | **~730 strings** | **12 dias** |

### **Por Idioma**

| Idioma | Custo Estimado | Tempo |
|--------|----------------|-------|
| Português (revisar) | R$ 500 | 2 dias |
| Inglês | R$ 1.500 | 3 dias |
| Chinês | R$ 3.000 | 5 dias |
| Espanhol | R$ 1.500 | 3 dias |
| **TOTAL** | **R$ 6.500** | **13 dias** |

---

## 🚀 **Início Rápido**

### **Passo 1: Instalar Pacotes**
```bash
composer require filament/spatie-laravel-translatable-plugin
```

### **Passo 2: Criar Estrutura**
```bash
mkdir -p lang/en lang/pt_BR lang/zh_CN lang/es
touch lang/en/{common,fields,resources,navigation,actions,notifications,documents}.php
touch lang/pt_BR/{common,fields,resources,navigation,actions,notifications,documents}.php
```

### **Passo 3: Configurar Filament**
```php
// app/Providers/Filament/AdminPanelProvider.php
->locales([
    'en' => 'English',
    'pt_BR' => 'Português (Brasil)',
    'zh_CN' => '简体中文',
    'es' => 'Español',
])
```

### **Passo 4: Começar a Traduzir**
```php
// Exemplo: CustomerResource.php
public static function getNavigationLabel(): string
{
    return __('navigation.customers');
}
```

---

## 📚 **Recursos Adicionais**

- [Laravel Localization Docs](https://laravel.com/docs/localization)
- [Filament Multi-Language](https://filamentphp.com/docs/panels/configuration#localization)
- [Spatie Translatable](https://github.com/spatie/laravel-translatable)
- [Laravel Lang Community](https://github.com/Laravel-Lang/lang)

---

## ✅ **Checklist Final**

- [ ] Todos os textos visíveis usam `__()`
- [ ] Arquivos de tradução criados para todos os idiomas
- [ ] PDFs e Excel traduzidos
- [ ] Emails traduzidos
- [ ] Formatação de datas/números por locale
- [ ] Locale switcher funcional
- [ ] Testes em todos os idiomas
- [ ] Documentação atualizada
- [ ] Treinamento da equipe

---

**Documento criado em:** 07/12/2025  
**Versão:** 1.0  
**Autor:** Manus AI Assistant

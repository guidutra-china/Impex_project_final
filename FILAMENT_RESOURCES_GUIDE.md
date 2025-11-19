# 🎨 GUIA COMPLETO: FILAMENT RESOURCES

Este guia contém o código completo para criar os Filament Resources principais do sistema.

---

## 📋 COMANDOS PARA CRIAR RESOURCES

Execute estes comandos no seu servidor:

```bash
# Purchase Order
php artisan make:filament-resource PurchaseOrder --generate

# Banking & Payments
php artisan make:filament-resource BankAccount --generate
php artisan make:filament-resource PaymentMethod --generate
php artisan make:filament-resource SupplierPayment --generate
php artisan make:filament-resource CustomerReceipt --generate

# Shipping
php artisan make:filament-resource Shipment --generate

# Warehouse
php artisan make:filament-resource Warehouse --generate
php artisan make:filament-resource WarehouseStock --generate
php artisan make:filament-resource WarehouseTransfer --generate

# Quality Control
php artisan make:filament-resource QualityInspection --generate
php artisan make:filament-resource QualityCheckpoint --generate

# Supplier Performance
php artisan make:filament-resource SupplierPerformanceMetric --generate
php artisan make:filament-resource SupplierIssue --generate

# Documents
php artisan make:filament-resource Document --generate
```

---

## 🎯 PURCHASE ORDER RESOURCE (EXEMPLO COMPLETO)

Após gerar o Resource, edite `app/Filament/Resources/PurchaseOrderResource.php`:

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'Purchasing';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('po_number')
                            ->label('PO Number')
                            ->disabled()
                            ->dehydrated(false),
                        
                        Forms\Components\Select::make('supplier_id')
                            ->relationship('supplier', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        
                        Forms\Components\Select::make('order_id')
                            ->label('Related RFQ')
                            ->relationship('order', 'order_number')
                            ->searchable()
                            ->preload(),
                        
                        Forms\Components\DatePicker::make('po_date')
                            ->label('PO Date')
                            ->required()
                            ->default(now()),
                        
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'pending_approval' => 'Pending Approval',
                                'approved' => 'Approved',
                                'sent' => 'Sent',
                                'confirmed' => 'Confirmed',
                                'partially_received' => 'Partially Received',
                                'received' => 'Received',
                                'cancelled' => 'Cancelled',
                                'closed' => 'Closed',
                            ])
                            ->required()
                            ->default('draft'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Currency & Pricing')
                    ->schema([
                        Forms\Components\Select::make('currency_id')
                            ->relationship('currency', 'code')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive(),
                        
                        Forms\Components\TextInput::make('exchange_rate')
                            ->numeric()
                            ->required()
                            ->default(1)
                            ->step(0.000001),
                        
                        Forms\Components\TextInput::make('subtotal')
                            ->numeric()
                            ->prefix('$')
                            ->disabled()
                            ->dehydrated(false),
                        
                        Forms\Components\TextInput::make('shipping_cost')
                            ->numeric()
                            ->prefix('$')
                            ->default(0),
                        
                        Forms\Components\TextInput::make('insurance_cost')
                            ->numeric()
                            ->prefix('$')
                            ->default(0),
                        
                        Forms\Components\TextInput::make('other_costs')
                            ->numeric()
                            ->prefix('$')
                            ->default(0),
                        
                        Forms\Components\TextInput::make('discount')
                            ->numeric()
                            ->prefix('$')
                            ->default(0),
                        
                        Forms\Components\TextInput::make('tax')
                            ->numeric()
                            ->prefix('$')
                            ->default(0),
                        
                        Forms\Components\TextInput::make('total')
                            ->numeric()
                            ->prefix('$')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('INCOTERMS')
                    ->schema([
                        Forms\Components\Select::make('incoterm')
                            ->options([
                                'EXW' => 'EXW - Ex Works',
                                'FCA' => 'FCA - Free Carrier',
                                'CPT' => 'CPT - Carriage Paid To',
                                'CIP' => 'CIP - Carriage and Insurance Paid To',
                                'DAP' => 'DAP - Delivered at Place',
                                'DPU' => 'DPU - Delivered at Place Unloaded',
                                'DDP' => 'DDP - Delivered Duty Paid',
                                'FAS' => 'FAS - Free Alongside Ship',
                                'FOB' => 'FOB - Free on Board',
                                'CFR' => 'CFR - Cost and Freight',
                                'CIF' => 'CIF - Cost, Insurance and Freight',
                            ])
                            ->searchable(),
                        
                        Forms\Components\TextInput::make('incoterm_location')
                            ->label('Incoterm Location')
                            ->placeholder('e.g., Shanghai Port'),
                        
                        Forms\Components\Toggle::make('shipping_included_in_price')
                            ->label('Shipping Included in Price'),
                        
                        Forms\Components\Toggle::make('insurance_included_in_price')
                            ->label('Insurance Included in Price'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Payment Terms')
                    ->schema([
                        Forms\Components\Select::make('payment_term_id')
                            ->relationship('paymentTerm', 'name')
                            ->searchable()
                            ->preload(),
                        
                        Forms\Components\Textarea::make('payment_terms_text')
                            ->rows(2),
                    ]),

                Forms\Components\Section::make('Delivery')
                    ->schema([
                        Forms\Components\Textarea::make('delivery_address')
                            ->rows(3),
                        
                        Forms\Components\DatePicker::make('expected_delivery_date'),
                        
                        Forms\Components\DatePicker::make('actual_delivery_date')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(3),
                        
                        Forms\Components\Textarea::make('terms_and_conditions')
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('po_number')
                    ->label('PO Number')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('supplier.name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('po_date')
                    ->date()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('total')
                    ->money(fn($record) => $record->currency->code ?? 'USD')
                    ->sortable(),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'pending_approval',
                        'primary' => 'approved',
                        'info' => 'sent',
                        'success' => ['confirmed', 'received', 'closed'],
                        'danger' => 'cancelled',
                    ]),
                
                Tables\Columns\TextColumn::make('expected_delivery_date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'approved' => 'Approved',
                        'sent' => 'Sent',
                        'confirmed' => 'Confirmed',
                    ]),
                
                Tables\Filters\SelectFilter::make('supplier')
                    ->relationship('supplier', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn($record) => $record->status === 'draft')
                    ->action(function($record) {
                        app(PurchaseOrderService::class)->approve($record);
                        Notification::make()
                            ->success()
                            ->title('PO Approved')
                            ->send();
                    }),
                
                Tables\Actions\Action::make('send')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn($record) => $record->status === 'approved')
                    ->action(function($record) {
                        app(PurchaseOrderService::class)->send($record);
                        Notification::make()
                            ->success()
                            ->title('PO Sent to Supplier')
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
```

---

## 📊 CUSTOMIZAÇÕES IMPORTANTES

### 1. Adicionar Repeater para Items

No form, adicione após a seção de Pricing:

```php
Forms\Components\Section::make('Items')
    ->schema([
        Forms\Components\Repeater::make('items')
            ->relationship()
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                
                Forms\Components\TextInput::make('quantity')
                    ->numeric()
                    ->required()
                    ->default(1),
                
                Forms\Components\TextInput::make('unit_cost')
                    ->numeric()
                    ->required()
                    ->prefix('$'),
                
                Forms\Components\TextInput::make('total_cost')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(false)
                    ->prefix('$'),
            ])
            ->columns(4)
            ->defaultItems(1),
    ]),
```

### 2. Adicionar Relation Manager para Items

Crie `app/Filament/Resources/PurchaseOrderResource/RelationManagers/ItemsRelationManager.php`:

```php
<?php

namespace App\Filament\Resources\PurchaseOrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required()
                    ->searchable(),
                
                Forms\Components\TextInput::make('quantity')
                    ->numeric()
                    ->required(),
                
                Forms\Components\TextInput::make('unit_cost')
                    ->numeric()
                    ->required()
                    ->prefix('$'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name'),
                Tables\Columns\TextColumn::make('quantity'),
                Tables\Columns\TextColumn::make('unit_cost')->money('USD'),
                Tables\Columns\TextColumn::make('total_cost')->money('USD'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
```

---

## 🎯 OUTROS RESOURCES (ESTRUTURA SIMILAR)

Todos os outros Resources seguem estrutura similar:

1. **BankAccountResource** - Gerenciar contas bancárias
2. **SupplierPaymentResource** - Pagamentos a fornecedores
3. **CustomerReceiptResource** - Recebimentos de clientes
4. **ShipmentResource** - Rastreamento de envios
5. **WarehouseResource** - Gestão de armazéns
6. **QualityInspectionResource** - Inspeções de qualidade
7. **SupplierIssueResource** - Problemas com fornecedores

---

## 📝 PRÓXIMOS PASSOS

1. **Gerar todos os Resources** com os comandos acima
2. **Customizar forms** seguindo o exemplo do PurchaseOrder
3. **Adicionar Actions** específicas de cada módulo
4. **Criar Relation Managers** para relacionamentos
5. **Configurar Policies** para controle de acesso

---

## 💡 DICAS

### Formatação de Moeda
```php
Tables\Columns\TextColumn::make('total')
    ->money(fn($record) => $record->currency->code ?? 'USD')
```

### Badge com Cores
```php
Tables\Columns\BadgeColumn::make('status')
    ->colors([
        'success' => 'completed',
        'warning' => 'pending',
        'danger' => 'failed',
    ])
```

### Actions Customizadas
```php
Tables\Actions\Action::make('custom_action')
    ->icon('heroicon-o-check')
    ->color('success')
    ->requiresConfirmation()
    ->action(function($record) {
        // Sua lógica aqui
    })
```

---

## 🎉 CONCLUSÃO

Com este guia você tem:
- ✅ Estrutura completa de Resources
- ✅ Exemplo completo de PurchaseOrder
- ✅ Comandos para gerar todos os Resources
- ✅ Customizações importantes
- ✅ Dicas de implementação

**Próximo passo:** Gerar os Resources e customizar conforme necessário!

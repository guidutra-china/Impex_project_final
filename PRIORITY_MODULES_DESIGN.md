# 🎯 Módulos Prioritários - Design Completo

**Data:** 2025-11-19  
**Versão:** 6.0 - Priority Modules Edition

---

## 📋 MÓDULOS SELECIONADOS

### ✅ Implementar Agora:
1. Document Management
2. Shipping & Logistics
3. Advanced Dashboard
4. Email Automation
5. Warehouse Management
6. Quality Control
7. Supplier Performance

### ❌ Não Implementar:
- Inventory Tracking (muito complexo)
- Customs Valuation (% simples é suficiente)
- FX Gains/Losses (não necessário)

---

# 1. DOCUMENT MANAGEMENT 📄

## 1.1 Estrutura de Dados

```sql
CREATE TABLE documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- === TIPO ===
    document_type ENUM(
        'commercial_invoice',      -- Fatura comercial
        'proforma_invoice',        -- Proforma
        'packing_list',            -- Lista de embalagem
        'bill_of_lading',          -- Conhecimento de embarque
        'airway_bill',             -- Conhecimento aéreo
        'certificate_of_origin',   -- Certificado de origem
        'customs_declaration',     -- Declaração aduaneira
        'insurance_certificate',   -- Certificado de seguro
        'quality_certificate',     -- Certificado de qualidade
        'inspection_report',       -- Relatório de inspeção
        'contract',                -- Contrato
        'purchase_order',          -- PO do cliente
        'other'                    -- Outro
    ) NOT NULL,
    
    -- === RELACIONAMENTO ===
    related_type VARCHAR(100) NULL COMMENT 'PurchaseOrder, SalesOrder, Shipment, Supplier, Customer',
    related_id BIGINT UNSIGNED NULL,
    
    -- === IDENTIFICAÇÃO ===
    document_number VARCHAR(100) NULL COMMENT 'Número do documento',
    title VARCHAR(255) NOT NULL COMMENT 'Título/Nome do documento',
    description TEXT NULL,
    
    -- === ARQUIVO ===
    file_path VARCHAR(500) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_size BIGINT NOT NULL COMMENT 'Tamanho em bytes',
    mime_type VARCHAR(100) NOT NULL,
    
    -- === DATAS ===
    issue_date DATE NULL COMMENT 'Data de emissão',
    expiry_date DATE NULL COMMENT 'Data de validade',
    
    -- === STATUS ===
    status ENUM('draft', 'issued', 'valid', 'expired', 'cancelled') DEFAULT 'draft',
    
    -- === VISIBILIDADE ===
    is_public BOOLEAN DEFAULT FALSE COMMENT 'Visível para cliente/fornecedor',
    
    -- === NOTAS ===
    notes TEXT NULL,
    
    -- === AUDITORIA ===
    uploaded_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    INDEX idx_type (document_type),
    INDEX idx_related (related_type, related_id),
    INDEX idx_status (status),
    INDEX idx_expiry (expiry_date),
    INDEX idx_number (document_number),
    
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de versões de documentos
CREATE TABLE document_versions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    document_id BIGINT UNSIGNED NOT NULL,
    version_number INT NOT NULL DEFAULT 1,
    
    file_path VARCHAR(500) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_size BIGINT NOT NULL,
    
    change_notes TEXT NULL,
    
    uploaded_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    
    INDEX idx_document (document_id),
    
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 1.2 Service: DocumentService

```php
<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DocumentService
{
    /**
     * Upload de documento
     */
    public function uploadDocument(array $data, UploadedFile $file): Document
    {
        // Gerar nome único
        $filename = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('documents/' . $data['document_type'], $filename);
        
        // Criar documento
        $document = Document::create([
            'document_type' => $data['document_type'],
            'related_type' => $data['related_type'] ?? null,
            'related_id' => $data['related_id'] ?? null,
            'document_number' => $data['document_number'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'issue_date' => $data['issue_date'] ?? null,
            'expiry_date' => $data['expiry_date'] ?? null,
            'status' => $data['status'] ?? 'draft',
            'is_public' => $data['is_public'] ?? false,
            'notes' => $data['notes'] ?? null,
            'uploaded_by' => auth()->id(),
        ]);
        
        return $document;
    }
    
    /**
     * Criar nova versão de documento
     */
    public function createVersion(Document $document, UploadedFile $file, string $changeNotes = null): void
    {
        // Salvar versão atual
        $currentVersion = $document->versions()->count() + 1;
        
        $document->versions()->create([
            'version_number' => $currentVersion - 1,
            'file_path' => $document->file_path,
            'file_name' => $document->file_name,
            'file_size' => $document->file_size,
            'change_notes' => 'Original version',
            'uploaded_by' => $document->uploaded_by,
        ]);
        
        // Upload novo arquivo
        $filename = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('documents/' . $document->document_type, $filename);
        
        // Atualizar documento
        $document->update([
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);
        
        // Criar registro de versão
        $document->versions()->create([
            'version_number' => $currentVersion,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'change_notes' => $changeNotes,
            'uploaded_by' => auth()->id(),
        ]);
    }
    
    /**
     * Buscar documentos por relacionamento
     */
    public function getDocumentsByRelation(string $relatedType, int $relatedId): Collection
    {
        return Document::where('related_type', $relatedType)
            ->where('related_id', $relatedId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
    
    /**
     * Verificar documentos expirados
     */
    public function getExpiringDocuments(int $days = 30): Collection
    {
        return Document::where('status', 'valid')
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays($days)])
            ->get();
    }
    
    /**
     * Download de documento
     */
    public function downloadDocument(Document $document)
    {
        return Storage::download($document->file_path, $document->file_name);
    }
}
```

## 1.3 Filament: DocumentResource

```php
<?php

namespace App\Filament\Resources;

use App\Models\Document;
use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Documents';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Document Information')
                    ->schema([
                        Forms\Components\Select::make('document_type')
                            ->options([
                                'commercial_invoice' => 'Commercial Invoice',
                                'proforma_invoice' => 'Proforma Invoice',
                                'packing_list' => 'Packing List',
                                'bill_of_lading' => 'Bill of Lading',
                                'airway_bill' => 'Airway Bill',
                                'certificate_of_origin' => 'Certificate of Origin',
                                'customs_declaration' => 'Customs Declaration',
                                'insurance_certificate' => 'Insurance Certificate',
                                'quality_certificate' => 'Quality Certificate',
                                'inspection_report' => 'Inspection Report',
                                'contract' => 'Contract',
                                'purchase_order' => 'Purchase Order',
                                'other' => 'Other',
                            ])
                            ->required(),
                            
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->placeholder('Document title'),
                            
                        Forms\Components\TextInput::make('document_number')
                            ->placeholder('Document number (if applicable)'),
                            
                        Forms\Components\Textarea::make('description')
                            ->rows(3),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Related To')
                    ->schema([
                        Forms\Components\Select::make('related_type')
                            ->options([
                                'PurchaseOrder' => 'Purchase Order',
                                'SalesOrder' => 'Sales Order',
                                'Shipment' => 'Shipment',
                                'Supplier' => 'Supplier',
                                'Customer' => 'Customer',
                            ])
                            ->reactive(),
                            
                        Forms\Components\Select::make('related_id')
                            ->label('Related Record')
                            ->options(function (callable $get) {
                                $type = $get('related_type');
                                if (!$type) return [];
                                
                                $model = "App\\Models\\{$type}";
                                return $model::pluck('name', 'id')->toArray();
                            })
                            ->searchable(),
                    ])->columns(2),
                    
                Forms\Components\Section::make('File Upload')
                    ->schema([
                        Forms\Components\FileUpload::make('file')
                            ->required()
                            ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                            ->maxSize(10240) // 10MB
                            ->helperText('Accepted: PDF, Images, Word. Max 10MB'),
                    ]),
                    
                Forms\Components\Section::make('Dates')
                    ->schema([
                        Forms\Components\DatePicker::make('issue_date')
                            ->label('Issue Date'),
                            
                        Forms\Components\DatePicker::make('expiry_date')
                            ->label('Expiry Date')
                            ->helperText('Leave empty if document does not expire'),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'issued' => 'Issued',
                                'valid' => 'Valid',
                                'expired' => 'Expired',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->default('draft'),
                            
                        Forms\Components\Toggle::make('is_public')
                            ->label('Public')
                            ->helperText('Visible to customers/suppliers'),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('document_type')
                    ->label('Type')
                    ->badge()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('document_number')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('related_type')
                    ->label('Related To'),
                    
                Tables\Columns\TextColumn::make('issue_date')
                    ->date()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('expiry_date')
                    ->date()
                    ->sortable()
                    ->color(fn($record) => 
                        $record->expiry_date && $record->expiry_date < now() 
                        ? 'danger' 
                        : 'success'
                    ),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'draft',
                        'primary' => 'issued',
                        'success' => 'valid',
                        'danger' => 'expired',
                        'warning' => 'cancelled',
                    ]),
                    
                Tables\Columns\IconColumn::make('is_public')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('document_type'),
                Tables\Filters\SelectFilter::make('status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn(Document $record) => 
                        app(DocumentService::class)->downloadDocument($record)
                    ),
                    
                Tables\Actions\Action::make('new_version')
                    ->icon('heroicon-o-arrow-path')
                    ->form([
                        Forms\Components\FileUpload::make('file')
                            ->required(),
                        Forms\Components\Textarea::make('change_notes')
                            ->label('Change Notes'),
                    ])
                    ->action(function (Document $record, array $data) {
                        app(DocumentService::class)->createVersion(
                            $record,
                            $data['file'],
                            $data['change_notes'] ?? null
                        );
                    }),
            ]);
    }
}
```

---

# 2. SHIPPING & LOGISTICS 🚢

## 2.1 Estrutura de Dados

```sql
CREATE TABLE shipments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- === IDENTIFICAÇÃO ===
    shipment_number VARCHAR(50) NOT NULL UNIQUE COMMENT 'Ex: SHP-2025-0001',
    
    -- === RELACIONAMENTO ===
    sales_order_id BIGINT UNSIGNED NOT NULL,
    
    -- === CARRIER ===
    carrier VARCHAR(100) NULL COMMENT 'Nome da transportadora',
    tracking_number VARCHAR(100) NULL COMMENT 'Número de rastreamento',
    service_type VARCHAR(100) NULL COMMENT 'Express, Standard, Economy',
    
    -- === DATAS ===
    shipment_date DATE NULL COMMENT 'Data de envio',
    estimated_delivery_date DATE NULL,
    actual_delivery_date DATE NULL,
    
    -- === STATUS ===
    status ENUM(
        'pending',              -- Pendente
        'preparing',            -- Preparando
        'ready_to_ship',        -- Pronto para envio
        'picked_up',            -- Coletado
        'in_transit',           -- Em trânsito
        'customs_clearance',    -- Liberação alfandegária
        'out_for_delivery',     -- Saiu para entrega
        'delivered',            -- Entregue
        'delayed',              -- Atrasado
        'returned',             -- Devolvido
        'cancelled'             -- Cancelado
    ) DEFAULT 'pending',
    
    -- === CUSTOS ===
    shipping_cost BIGINT NOT NULL DEFAULT 0 COMMENT 'Custo de frete',
    insurance_cost BIGINT NOT NULL DEFAULT 0 COMMENT 'Custo de seguro',
    customs_cost BIGINT NOT NULL DEFAULT 0 COMMENT 'Custos alfandegários',
    other_costs BIGINT NOT NULL DEFAULT 0,
    total_cost BIGINT NOT NULL DEFAULT 0,
    
    -- === ENDEREÇOS ===
    origin_address TEXT NULL,
    destination_address TEXT NULL,
    
    -- === DIMENSÕES ===
    total_weight DECIMAL(10,2) NULL COMMENT 'Peso total (kg)',
    total_volume DECIMAL(10,2) NULL COMMENT 'Volume total (m³)',
    number_of_packages INT NULL COMMENT 'Número de volumes',
    
    -- === NOTAS ===
    shipping_instructions TEXT NULL,
    notes TEXT NULL,
    
    -- === AUDITORIA ===
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    INDEX idx_shipment_number (shipment_number),
    INDEX idx_sales_order (sales_order_id),
    INDEX idx_status (status),
    INDEX idx_tracking (tracking_number),
    INDEX idx_shipment_date (shipment_date),
    
    FOREIGN KEY (sales_order_id) REFERENCES sales_orders(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE shipment_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    shipment_id BIGINT UNSIGNED NOT NULL,
    sales_order_item_id BIGINT UNSIGNED NOT NULL,
    
    -- === PRODUTO ===
    product_name VARCHAR(255) NOT NULL,
    product_sku VARCHAR(100) NULL,
    
    -- === QUANTIDADE ===
    quantity INT NOT NULL,
    
    -- === DIMENSÕES ===
    weight DECIMAL(10,2) NULL COMMENT 'Peso (kg)',
    volume DECIMAL(10,2) NULL COMMENT 'Volume (m³)',
    
    -- === NOTAS ===
    notes TEXT NULL,
    
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_shipment (shipment_id),
    INDEX idx_so_item (sales_order_item_id),
    
    FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE,
    FOREIGN KEY (sales_order_item_id) REFERENCES sales_order_items(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE shipment_tracking_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    shipment_id BIGINT UNSIGNED NOT NULL,
    
    -- === EVENTO ===
    event_type VARCHAR(100) NOT NULL COMMENT 'picked_up, in_transit, delivered, etc',
    event_description TEXT NOT NULL,
    
    -- === LOCALIZAÇÃO ===
    location VARCHAR(255) NULL,
    
    -- === DATA/HORA ===
    event_datetime TIMESTAMP NOT NULL,
    
    -- === ORIGEM ===
    source ENUM('manual', 'api', 'email') DEFAULT 'manual',
    
    created_at TIMESTAMP NULL,
    
    INDEX idx_shipment (shipment_id),
    INDEX idx_datetime (event_datetime),
    
    FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 2.2 Service: ShippingService

```php
<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\SalesOrder;
use Illuminate\Support\Facades\DB;

class ShippingService
{
    /**
     * Criar shipment a partir de Sales Order
     */
    public function createShipment(SalesOrder $salesOrder, array $data): Shipment
    {
        return DB::transaction(function () use ($salesOrder, $data) {
            // Criar shipment
            $shipment = Shipment::create([
                'shipment_number' => $this->generateShipmentNumber(),
                'sales_order_id' => $salesOrder->id,
                'carrier' => $data['carrier'] ?? null,
                'tracking_number' => $data['tracking_number'] ?? null,
                'service_type' => $data['service_type'] ?? null,
                'shipment_date' => $data['shipment_date'] ?? now(),
                'estimated_delivery_date' => $data['estimated_delivery_date'] ?? null,
                'shipping_cost' => $data['shipping_cost'] ?? 0,
                'insurance_cost' => $data['insurance_cost'] ?? 0,
                'status' => 'pending',
                'origin_address' => $data['origin_address'] ?? null,
                'destination_address' => $salesOrder->shipping_address,
                'created_by' => auth()->id(),
            ]);
            
            // Adicionar items
            foreach ($data['items'] as $itemData) {
                $soItem = $salesOrder->items()->find($itemData['sales_order_item_id']);
                
                if ($soItem) {
                    $shipment->items()->create([
                        'sales_order_item_id' => $soItem->id,
                        'product_name' => $soItem->product_name,
                        'product_sku' => $soItem->product_sku,
                        'quantity' => $itemData['quantity'],
                        'weight' => $itemData['weight'] ?? null,
                        'volume' => $itemData['volume'] ?? null,
                    ]);
                }
            }
            
            // Atualizar totais
            $this->updateTotals($shipment);
            
            // Criar evento inicial
            $this->addTrackingEvent($shipment, 'created', 'Shipment created');
            
            return $shipment->fresh();
        });
    }
    
    /**
     * Adicionar evento de rastreamento
     */
    public function addTrackingEvent(
        Shipment $shipment,
        string $eventType,
        string $description,
        string $location = null,
        string $source = 'manual'
    ): void {
        $shipment->trackingEvents()->create([
            'event_type' => $eventType,
            'event_description' => $description,
            'location' => $location,
            'event_datetime' => now(),
            'source' => $source,
        ]);
    }
    
    /**
     * Atualizar status do shipment
     */
    public function updateStatus(Shipment $shipment, string $status, string $notes = null): void
    {
        $shipment->update(['status' => $status]);
        
        // Adicionar evento
        $this->addTrackingEvent(
            $shipment,
            $status,
            $notes ?? "Status updated to: {$status}"
        );
        
        // Se entregue, atualizar data
        if ($status === 'delivered') {
            $shipment->update(['actual_delivery_date' => now()]);
            
            // Atualizar Sales Order
            $shipment->salesOrder->update(['status' => 'delivered']);
        }
    }
    
    /**
     * Gerar número de shipment
     */
    protected function generateShipmentNumber(): string
    {
        $year = now()->format('Y');
        
        $lastShipment = Shipment::withTrashed()
            ->where('shipment_number', 'like', "SHP-{$year}-%")
            ->orderBy('shipment_number', 'desc')
            ->first();
        
        $sequential = 1;
        if ($lastShipment) {
            preg_match('/(\d{4})$/', $lastShipment->shipment_number, $matches);
            $sequential = ((int) $matches[1]) + 1;
        }
        
        return "SHP-{$year}-" . str_pad($sequential, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Atualizar totais
     */
    protected function updateTotals(Shipment $shipment): void
    {
        $totalWeight = $shipment->items->sum('weight');
        $totalVolume = $shipment->items->sum('volume');
        $numberOfPackages = $shipment->items->count();
        $totalCost = $shipment->shipping_cost + $shipment->insurance_cost + 
                     $shipment->customs_cost + $shipment->other_costs;
        
        $shipment->update([
            'total_weight' => $totalWeight,
            'total_volume' => $totalVolume,
            'number_of_packages' => $numberOfPackages,
            'total_cost' => $totalCost,
        ]);
    }
}
```

---

Continuarei com os outros módulos no próximo arquivo...


# 3. ADVANCED DASHBOARD 📊

## 3.1 Estrutura de Widgets

```php
<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\BankAccount;

class FinancialOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            // CASH POSITION
            Stat::make('Total Cash', $this->formatMoney($this->getTotalCash()))
                ->description('Across all bank accounts')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            
            // ACCOUNTS RECEIVABLE
            Stat::make('Accounts Receivable', $this->formatMoney($this->getTotalAR()))
                ->description($this->getAROverdue() . ' overdue')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color($this->getAROverdue() > 0 ? 'warning' : 'success'),
            
            // ACCOUNTS PAYABLE
            Stat::make('Accounts Payable', $this->formatMoney($this->getTotalAP()))
                ->description($this->getAPOverdue() . ' overdue')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color($this->getAPOverdue() > 0 ? 'danger' : 'success'),
            
            // NET POSITION
            Stat::make('Net Position', $this->formatMoney($this->getNetPosition()))
                ->description('Cash + AR - AP')
                ->descriptionIcon('heroicon-m-calculator')
                ->color($this->getNetPosition() >= 0 ? 'success' : 'danger'),
        ];
    }
    
    protected function getTotalCash(): int
    {
        return BankAccount::where('is_active', true)
            ->sum('current_balance');
    }
    
    protected function getTotalAR(): int
    {
        return SalesOrder::whereIn('payment_status', ['unpaid', 'partial'])
            ->sum('remaining_amount');
    }
    
    protected function getTotalAP(): int
    {
        return PurchaseOrder::whereIn('payment_status', ['unpaid', 'partial'])
            ->sum('remaining_amount');
    }
    
    protected function getNetPosition(): int
    {
        return $this->getTotalCash() + $this->getTotalAR() - $this->getTotalAP();
    }
    
    protected function getAROverdue(): int
    {
        return SalesOrder::whereIn('payment_status', ['unpaid', 'partial'])
            ->where('due_date', '<', now())
            ->count();
    }
    
    protected function getAPOverdue(): int
    {
        return PurchaseOrder::whereIn('payment_status', ['unpaid', 'partial'])
            ->where('due_date', '<', now())
            ->count();
    }
    
    protected function formatMoney(int $cents): string
    {
        return '$' . number_format($cents / 100, 2);
    }
}
```

```php
<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\Shipment;

class OperationsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            // OPEN PURCHASE ORDERS
            Stat::make('Open Purchase Orders', $this->getOpenPOs())
                ->description('Pending or confirmed')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('info')
                ->url(route('filament.resources.purchase-orders.index')),
            
            // OPEN SALES ORDERS
            Stat::make('Open Sales Orders', $this->getOpenSOs())
                ->description('Not yet delivered')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('warning')
                ->url(route('filament.resources.sales-orders.index')),
            
            // PENDING SHIPMENTS
            Stat::make('Pending Shipments', $this->getPendingShipments())
                ->description('Awaiting dispatch')
                ->descriptionIcon('heroicon-m-truck')
                ->color('primary')
                ->url(route('filament.resources.shipments.index')),
            
            // IN TRANSIT
            Stat::make('In Transit', $this->getInTransit())
                ->description('Currently shipping')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('success'),
        ];
    }
    
    protected function getOpenPOs(): int
    {
        return PurchaseOrder::whereIn('status', ['draft', 'sent', 'confirmed'])
            ->count();
    }
    
    protected function getOpenSOs(): int
    {
        return SalesOrder::whereNotIn('status', ['delivered', 'cancelled'])
            ->count();
    }
    
    protected function getPendingShipments(): int
    {
        return Shipment::whereIn('status', ['pending', 'preparing', 'ready_to_ship'])
            ->count();
    }
    
    protected function getInTransit(): int
    {
        return Shipment::whereIn('status', ['picked_up', 'in_transit', 'customs_clearance'])
            ->count();
    }
}
```

```php
<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\SalesOrder;
use Illuminate\Support\Facades\DB;

class MonthlyRevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Monthly Revenue & Profit';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $data = SalesOrder::where('status', 'completed')
            ->where('sale_date', '>=', now()->subMonths(12))
            ->select(
                DB::raw('DATE_FORMAT(sale_date, "%Y-%m") as month'),
                DB::raw('SUM(total_amount) as revenue'),
                DB::raw('SUM(total_amount - total_cost) as profit')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();
        
        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => $data->pluck('revenue')->map(fn($v) => $v / 100)->toArray(),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                ],
                [
                    'label' => 'Profit',
                    'data' => $data->pluck('profit')->map(fn($v) => $v / 100)->toArray(),
                    'backgroundColor' => 'rgba(34, 197, 94, 0.5)',
                    'borderColor' => 'rgb(34, 197, 94)',
                ],
            ],
            'labels' => $data->pluck('month')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
```

```php
<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\SalesOrder;

class OverdueInvoicesWidget extends BaseWidget
{
    protected static ?string $heading = 'Overdue Invoices';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    protected function getTableQuery()
    {
        return SalesOrder::query()
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->where('due_date', '<', now())
            ->orderBy('due_date', 'asc');
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('sale_number')
                ->label('SO Number')
                ->searchable(),
                
            Tables\Columns\TextColumn::make('customer.name')
                ->searchable(),
                
            Tables\Columns\TextColumn::make('total_amount')
                ->money('USD')
                ->label('Total'),
                
            Tables\Columns\TextColumn::make('remaining_amount')
                ->money('USD')
                ->label('Outstanding'),
                
            Tables\Columns\TextColumn::make('due_date')
                ->date()
                ->color('danger'),
                
            Tables\Columns\TextColumn::make('days_overdue')
                ->label('Days Overdue')
                ->getStateUsing(fn($record) => now()->diffInDays($record->due_date))
                ->badge()
                ->color('danger'),
        ];
    }
}
```

## 3.2 Dashboard Service

```php
<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\BankAccount;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Obter dashboard completo
     */
    public function getFinancialDashboard(): array
    {
        return [
            'cash_flow' => $this->getCashFlowData(),
            'profitability' => $this->getProfitabilityData(),
            'operations' => $this->getOperationsData(),
            'alerts' => $this->getAlerts(),
        ];
    }
    
    /**
     * Dados de fluxo de caixa
     */
    protected function getCashFlowData(): array
    {
        return [
            'total_cash' => BankAccount::where('is_active', true)->sum('current_balance'),
            'accounts_receivable' => SalesOrder::whereIn('payment_status', ['unpaid', 'partial'])->sum('remaining_amount'),
            'accounts_payable' => PurchaseOrder::whereIn('payment_status', ['unpaid', 'partial'])->sum('remaining_amount'),
            'net_position' => $this->getNetPosition(),
            'forecast_30_days' => $this->getCashFlowForecast(30),
        ];
    }
    
    /**
     * Dados de rentabilidade
     */
    protected function getProfitabilityData(): array
    {
        $mtdSales = SalesOrder::where('status', 'completed')
            ->whereMonth('sale_date', now()->month)
            ->get();
        
        $totalRevenue = $mtdSales->sum('total_amount');
        $totalCost = $mtdSales->sum('total_cost');
        $grossProfit = $totalRevenue - $totalCost;
        
        return [
            'gross_margin_mtd' => $totalRevenue > 0 ? ($grossProfit / $totalRevenue) * 100 : 0,
            'revenue_mtd' => $totalRevenue,
            'profit_mtd' => $grossProfit,
            'top_5_products' => $this->getTopProducts(5),
            'top_5_customers' => $this->getTopCustomers(5),
        ];
    }
    
    /**
     * Dados operacionais
     */
    protected function getOperationsData(): array
    {
        return [
            'open_pos' => PurchaseOrder::whereIn('status', ['draft', 'sent', 'confirmed'])->count(),
            'open_sos' => SalesOrder::whereNotIn('status', ['delivered', 'cancelled'])->count(),
            'pending_shipments' => Shipment::whereIn('status', ['pending', 'preparing'])->count(),
            'overdue_payments' => SalesOrder::whereIn('payment_status', ['unpaid', 'partial'])
                ->where('due_date', '<', now())
                ->count(),
        ];
    }
    
    /**
     * Alertas
     */
    protected function getAlerts(): array
    {
        return [
            'overdue_invoices' => $this->getOverdueInvoices(),
            'expiring_documents' => $this->getExpiringDocuments(),
            'low_cash_warning' => $this->checkLowCash(),
            'delayed_shipments' => $this->getDelayedShipments(),
        ];
    }
    
    /**
     * Previsão de fluxo de caixa
     */
    protected function getCashFlowForecast(int $days): array
    {
        $currentCash = BankAccount::where('is_active', true)->sum('current_balance');
        
        // Recebimentos esperados
        $expectedReceipts = SalesOrder::whereIn('payment_status', ['unpaid', 'partial'])
            ->where('due_date', '<=', now()->addDays($days))
            ->sum('remaining_amount');
        
        // Pagamentos esperados
        $expectedPayments = PurchaseOrder::whereIn('payment_status', ['unpaid', 'partial'])
            ->where('due_date', '<=', now()->addDays($days))
            ->sum('remaining_amount');
        
        $projectedCash = $currentCash + $expectedReceipts - $expectedPayments;
        
        return [
            'current_cash' => $currentCash,
            'expected_receipts' => $expectedReceipts,
            'expected_payments' => $expectedPayments,
            'projected_cash' => $projectedCash,
            'net_change' => $projectedCash - $currentCash,
        ];
    }
    
    /**
     * Top produtos
     */
    protected function getTopProducts(int $limit): array
    {
        return DB::table('sales_order_items')
            ->join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->where('sales_orders.status', 'completed')
            ->whereMonth('sales_orders.sale_date', now()->month)
            ->select(
                'sales_order_items.product_name',
                DB::raw('SUM(sales_order_items.quantity) as total_quantity'),
                DB::raw('SUM(sales_order_items.total_amount) as total_revenue'),
                DB::raw('SUM(sales_order_items.total_amount - sales_order_items.total_cost) as total_profit')
            )
            ->groupBy('sales_order_items.product_name')
            ->orderBy('total_revenue', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
    
    /**
     * Top clientes
     */
    protected function getTopCustomers(int $limit): array
    {
        return DB::table('sales_orders')
            ->join('customers', 'sales_orders.customer_id', '=', 'customers.id')
            ->where('sales_orders.status', 'completed')
            ->whereMonth('sales_orders.sale_date', now()->month)
            ->select(
                'customers.name',
                DB::raw('COUNT(sales_orders.id) as order_count'),
                DB::raw('SUM(sales_orders.total_amount) as total_revenue'),
                DB::raw('SUM(sales_orders.total_amount - sales_orders.total_cost) as total_profit')
            )
            ->groupBy('customers.id', 'customers.name')
            ->orderBy('total_revenue', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
```

---

# 4. EMAIL AUTOMATION 📧

## 4.1 Estrutura de Emails

```php
<?php

namespace App\Mail;

use App\Models\SalesOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProformaInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public SalesOrder $salesOrder;
    public string $proformaPath;

    public function __construct(SalesOrder $salesOrder, string $proformaPath)
    {
        $this->salesOrder = $salesOrder;
        $this->proformaPath = $proformaPath;
    }

    public function build()
    {
        return $this->subject("Proforma Invoice - {$this->salesOrder->sale_number}")
            ->markdown('emails.proforma-invoice')
            ->attach($this->proformaPath, [
                'as' => "Proforma_{$this->salesOrder->sale_number}.pdf",
                'mime' => 'application/pdf',
            ]);
    }
}
```

```php
<?php

namespace App\Mail;

use App\Models\SalesOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public SalesOrder $salesOrder;
    public int $daysOverdue;

    public function __construct(SalesOrder $salesOrder)
    {
        $this->salesOrder = $salesOrder;
        $this->daysOverdue = now()->diffInDays($salesOrder->due_date);
    }

    public function build()
    {
        $subject = $this->daysOverdue > 0
            ? "Payment Overdue - {$this->salesOrder->sale_number}"
            : "Payment Reminder - {$this->salesOrder->sale_number}";
        
        return $this->subject($subject)
            ->markdown('emails.payment-reminder');
    }
}
```

```php
<?php

namespace App\Mail;

use App\Models\Shipment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ShipmentNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public Shipment $shipment;

    public function __construct(Shipment $shipment)
    {
        $this->shipment = $shipment;
    }

    public function build()
    {
        return $this->subject("Shipment Update - {$this->shipment->shipment_number}")
            ->markdown('emails.shipment-notification');
    }
}
```

## 4.2 Email Templates (Blade)

**resources/views/emails/proforma-invoice.blade.php:**
```blade
@component('mail::message')
# Proforma Invoice

Dear {{ $salesOrder->customer->name }},

Please find attached the Proforma Invoice for your order.

**Order Details:**
- Order Number: {{ $salesOrder->sale_number }}
- Order Date: {{ $salesOrder->sale_date->format('Y-m-d') }}
- Total Amount: {{ money($salesOrder->total_amount, $salesOrder->currency->code) }}

@component('mail::button', ['url' => route('customer.orders.show', $salesOrder)])
View Order
@endcomponent

If you have any questions, please don't hesitate to contact us.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
```

**resources/views/emails/payment-reminder.blade.php:**
```blade
@component('mail::message')
# Payment Reminder

Dear {{ $salesOrder->customer->name }},

@if($daysOverdue > 0)
This is a friendly reminder that payment for order {{ $salesOrder->sale_number }} is now **{{ $daysOverdue }} days overdue**.
@else
This is a friendly reminder that payment for order {{ $salesOrder->sale_number }} is due soon.
@endif

**Payment Details:**
- Order Number: {{ $salesOrder->sale_number }}
- Total Amount: {{ money($salesOrder->total_amount, $salesOrder->currency->code) }}
- Outstanding: {{ money($salesOrder->remaining_amount, $salesOrder->currency->code) }}
- Due Date: {{ $salesOrder->due_date->format('Y-m-d') }}

@component('mail::button', ['url' => route('customer.payment', $salesOrder)])
Make Payment
@endcomponent

Thank you for your prompt attention to this matter.

Best regards,<br>
{{ config('app.name') }}
@endcomponent
```

**resources/views/emails/shipment-notification.blade.php:**
```blade
@component('mail::message')
# Shipment Update

Dear {{ $shipment->salesOrder->customer->name }},

Your order has been shipped!

**Shipment Details:**
- Shipment Number: {{ $shipment->shipment_number }}
- Order Number: {{ $shipment->salesOrder->sale_number }}
- Carrier: {{ $shipment->carrier }}
- Tracking Number: {{ $shipment->tracking_number }}
- Estimated Delivery: {{ $shipment->estimated_delivery_date?->format('Y-m-d') }}

@if($shipment->tracking_number)
@component('mail::button', ['url' => "https://track.carrier.com/{$shipment->tracking_number}"])
Track Shipment
@endcomponent
@endif

Thank you for your business!

Best regards,<br>
{{ config('app.name') }}
@endcomponent
```

## 4.3 Email Automation Service

```php
<?php

namespace App\Services;

use App\Models\SalesOrder;
use App\Models\Shipment;
use App\Mail\ProformaInvoiceMail;
use App\Mail\PaymentReminderMail;
use App\Mail\ShipmentNotificationMail;
use Illuminate\Support\Facades\Mail;

class EmailAutomationService
{
    /**
     * Enviar proforma automaticamente
     */
    public function sendProforma(SalesOrder $salesOrder, string $proformaPath): void
    {
        Mail::to($salesOrder->customer->email)
            ->send(new ProformaInvoiceMail($salesOrder, $proformaPath));
        
        $salesOrder->update([
            'proforma_sent_at' => now(),
            'status' => 'proforma_sent',
        ]);
    }
    
    /**
     * Enviar lembrete de pagamento
     */
    public function sendPaymentReminder(SalesOrder $salesOrder): void
    {
        if ($salesOrder->payment_status !== 'paid') {
            Mail::to($salesOrder->customer->email)
                ->send(new PaymentReminderMail($salesOrder));
            
            $salesOrder->update(['last_reminder_sent_at' => now()]);
        }
    }
    
    /**
     * Enviar notificação de envio
     */
    public function sendShipmentNotification(Shipment $shipment): void
    {
        Mail::to($shipment->salesOrder->customer->email)
            ->send(new ShipmentNotificationMail($shipment));
        
        $shipment->update(['notification_sent_at' => now()]);
    }
    
    /**
     * Enviar lembretes em lote (scheduled job)
     */
    public function sendBatchReminders(): void
    {
        // Enviar para faturas vencidas há 7, 14, 30 dias
        $overdueOrders = SalesOrder::whereIn('payment_status', ['unpaid', 'partial'])
            ->where('due_date', '<', now())
            ->whereIn(DB::raw('DATEDIFF(NOW(), due_date)'), [7, 14, 30])
            ->get();
        
        foreach ($overdueOrders as $order) {
            $this->sendPaymentReminder($order);
        }
    }
}
```

## 4.4 Scheduled Jobs

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EmailAutomationService;

class SendPaymentReminders extends Command
{
    protected $signature = 'email:payment-reminders';
    protected $description = 'Send payment reminders for overdue invoices';

    public function handle(EmailAutomationService $emailService)
    {
        $this->info('Sending payment reminders...');
        
        $emailService->sendBatchReminders();
        
        $this->info('Payment reminders sent successfully!');
    }
}
```

**app/Console/Kernel.php:**
```php
protected function schedule(Schedule $schedule)
{
    // Enviar lembretes de pagamento diariamente às 9h
    $schedule->command('email:payment-reminders')
        ->dailyAt('09:00');
}
```

---

# 5. WAREHOUSE MANAGEMENT 🏭

## 5.1 Estrutura de Dados

```sql
CREATE TABLE warehouses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- === IDENTIFICAÇÃO ===
    code VARCHAR(50) NOT NULL UNIQUE COMMENT 'Ex: WH-001',
    name VARCHAR(255) NOT NULL,
    
    -- === TIPO ===
    type ENUM('owned', 'rented', 'third_party') DEFAULT 'owned',
    
    -- === ENDEREÇO ===
    address TEXT NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(100) NULL,
    country VARCHAR(100) NULL,
    postal_code VARCHAR(20) NULL,
    
    -- === CONTATO ===
    contact_person VARCHAR(255) NULL,
    contact_phone VARCHAR(50) NULL,
    contact_email VARCHAR(255) NULL,
    
    -- === CAPACIDADE ===
    total_capacity DECIMAL(10,2) NULL COMMENT 'Capacidade total (m³)',
    used_capacity DECIMAL(10,2) DEFAULT 0 COMMENT 'Capacidade usada (m³)',
    
    -- === STATUS ===
    is_active BOOLEAN DEFAULT TRUE,
    
    -- === NOTAS ===
    notes TEXT NULL,
    
    -- === AUDITORIA ===
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    INDEX idx_code (code),
    INDEX idx_is_active (is_active),
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE warehouse_locations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    warehouse_id BIGINT UNSIGNED NOT NULL,
    
    -- === IDENTIFICAÇÃO ===
    location_code VARCHAR(50) NOT NULL COMMENT 'Ex: A-01-01 (Aisle-Rack-Shelf)',
    location_name VARCHAR(255) NULL,
    
    -- === TIPO ===
    location_type ENUM('shelf', 'pallet', 'bin', 'floor') DEFAULT 'shelf',
    
    -- === CAPACIDADE ===
    capacity DECIMAL(10,2) NULL COMMENT 'Capacidade (m³)',
    
    -- === STATUS ===
    is_available BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    UNIQUE KEY unique_location (warehouse_id, location_code),
    INDEX idx_warehouse (warehouse_id),
    INDEX idx_available (is_available),
    
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE warehouse_stock (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    warehouse_id BIGINT UNSIGNED NOT NULL,
    warehouse_location_id BIGINT UNSIGNED NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    
    -- === QUANTIDADE ===
    quantity INT NOT NULL DEFAULT 0,
    
    -- === CUSTO ===
    unit_cost BIGINT NOT NULL DEFAULT 0,
    total_value BIGINT NOT NULL DEFAULT 0,
    
    -- === DATAS ===
    last_movement_date DATE NULL,
    
    updated_at TIMESTAMP NULL,
    
    UNIQUE KEY unique_stock (warehouse_id, warehouse_location_id, product_id),
    INDEX idx_warehouse (warehouse_id),
    INDEX idx_product (product_id),
    INDEX idx_location (warehouse_location_id),
    
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE RESTRICT,
    FOREIGN KEY (warehouse_location_id) REFERENCES warehouse_locations(id) ON DELETE SET NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE warehouse_transfers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- === IDENTIFICAÇÃO ===
    transfer_number VARCHAR(50) NOT NULL UNIQUE COMMENT 'Ex: TRF-2025-0001',
    
    -- === ORIGEM/DESTINO ===
    from_warehouse_id BIGINT UNSIGNED NOT NULL,
    to_warehouse_id BIGINT UNSIGNED NOT NULL,
    
    -- === STATUS ===
    status ENUM('pending', 'in_transit', 'completed', 'cancelled') DEFAULT 'pending',
    
    -- === DATAS ===
    transfer_date DATE NOT NULL,
    expected_arrival_date DATE NULL,
    actual_arrival_date DATE NULL,
    
    -- === NOTAS ===
    notes TEXT NULL,
    
    -- === AUDITORIA ===
    created_by BIGINT UNSIGNED NULL,
    approved_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_transfer_number (transfer_number),
    INDEX idx_from_warehouse (from_warehouse_id),
    INDEX idx_to_warehouse (to_warehouse_id),
    INDEX idx_status (status),
    
    FOREIGN KEY (from_warehouse_id) REFERENCES warehouses(id) ON DELETE RESTRICT,
    FOREIGN KEY (to_warehouse_id) REFERENCES warehouses(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE warehouse_transfer_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    warehouse_transfer_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    
    -- === QUANTIDADE ===
    quantity INT NOT NULL,
    
    -- === CUSTO ===
    unit_cost BIGINT NOT NULL,
    
    created_at TIMESTAMP NULL,
    
    INDEX idx_transfer (warehouse_transfer_id),
    INDEX idx_product (product_id),
    
    FOREIGN KEY (warehouse_transfer_id) REFERENCES warehouse_transfers(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 5.2 Service: WarehouseService

```php
<?php

namespace App\Services;

use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Models\WarehouseTransfer;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class WarehouseService
{
    /**
     * Adicionar estoque ao armazém
     */
    public function addStock(
        int $warehouseId,
        int $productId,
        int $quantity,
        int $unitCost,
        int $locationId = null
    ): void {
        DB::transaction(function () use ($warehouseId, $productId, $quantity, $unitCost, $locationId) {
            $stock = WarehouseStock::firstOrNew([
                'warehouse_id' => $warehouseId,
                'warehouse_location_id' => $locationId,
                'product_id' => $productId,
            ]);
            
            $stock->quantity += $quantity;
            $stock->unit_cost = $unitCost;
            $stock->total_value = $stock->quantity * $unitCost;
            $stock->last_movement_date = now();
            $stock->save();
        });
    }
    
    /**
     * Remover estoque do armazém
     */
    public function removeStock(
        int $warehouseId,
        int $productId,
        int $quantity,
        int $locationId = null
    ): void {
        DB::transaction(function () use ($warehouseId, $productId, $quantity, $locationId) {
            $stock = WarehouseStock::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->where('warehouse_location_id', $locationId)
                ->firstOrFail();
            
            if ($stock->quantity < $quantity) {
                throw new \Exception('Insufficient stock');
            }
            
            $stock->quantity -= $quantity;
            $stock->total_value = $stock->quantity * $stock->unit_cost;
            $stock->last_movement_date = now();
            $stock->save();
        });
    }
    
    /**
     * Transferir estoque entre armazéns
     */
    public function createTransfer(array $data): WarehouseTransfer
    {
        return DB::transaction(function () use ($data) {
            // Criar transferência
            $transfer = WarehouseTransfer::create([
                'transfer_number' => $this->generateTransferNumber(),
                'from_warehouse_id' => $data['from_warehouse_id'],
                'to_warehouse_id' => $data['to_warehouse_id'],
                'transfer_date' => $data['transfer_date'] ?? now(),
                'expected_arrival_date' => $data['expected_arrival_date'] ?? null,
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);
            
            // Adicionar items
            foreach ($data['items'] as $item) {
                $transfer->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                ]);
            }
            
            return $transfer;
        });
    }
    
    /**
     * Completar transferência
     */
    public function completeTransfer(WarehouseTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            foreach ($transfer->items as $item) {
                // Remover do armazém de origem
                $this->removeStock(
                    $transfer->from_warehouse_id,
                    $item->product_id,
                    $item->quantity
                );
                
                // Adicionar ao armazém de destino
                $this->addStock(
                    $transfer->to_warehouse_id,
                    $item->product_id,
                    $item->quantity,
                    $item->unit_cost
                );
            }
            
            $transfer->update([
                'status' => 'completed',
                'actual_arrival_date' => now(),
            ]);
        });
    }
    
    /**
     * Obter estoque por produto
     */
    public function getStockByProduct(int $productId): array
    {
        $stocks = WarehouseStock::where('product_id', $productId)
            ->with('warehouse')
            ->get();
        
        return [
            'total_quantity' => $stocks->sum('quantity'),
            'total_value' => $stocks->sum('total_value'),
            'by_warehouse' => $stocks->map(fn($stock) => [
                'warehouse' => $stock->warehouse->name,
                'quantity' => $stock->quantity,
                'value' => $stock->total_value,
            ])->toArray(),
        ];
    }
    
    /**
     * Gerar número de transferência
     */
    protected function generateTransferNumber(): string
    {
        $year = now()->format('Y');
        
        $lastTransfer = WarehouseTransfer::withTrashed()
            ->where('transfer_number', 'like', "TRF-{$year}-%")
            ->orderBy('transfer_number', 'desc')
            ->first();
        
        $sequential = 1;
        if ($lastTransfer) {
            preg_match('/(\d{4})$/', $lastTransfer->transfer_number, $matches);
            $sequential = ((int) $matches[1]) + 1;
        }
        
        return "TRF-{$year}-" . str_pad($sequential, 4, '0', STR_PAD_LEFT);
    }
}
```

---

Continuarei com Quality Control e Supplier Performance no próximo bloco...


# 6. QUALITY CONTROL 🔍

## 6.1 Estrutura de Dados

```sql
CREATE TABLE quality_inspections (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- === IDENTIFICAÇÃO ===
    inspection_number VARCHAR(50) NOT NULL UNIQUE COMMENT 'Ex: QC-2025-0001',
    
    -- === RELACIONAMENTO ===
    inspectable_type VARCHAR(100) NOT NULL COMMENT 'PurchaseOrder, SalesOrder, Product',
    inspectable_id BIGINT UNSIGNED NOT NULL,
    
    -- === TIPO ===
    inspection_type ENUM(
        'incoming',         -- Inspeção de recebimento
        'in_process',       -- Inspeção em processo
        'final',            -- Inspeção final
        'random',           -- Inspeção aleatória
        'customer_return'   -- Inspeção de devolução
    ) NOT NULL,
    
    -- === STATUS ===
    status ENUM('pending', 'in_progress', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    
    -- === RESULTADO ===
    result ENUM('passed', 'failed', 'conditional') NULL,
    
    -- === DATAS ===
    inspection_date DATE NOT NULL,
    completed_date DATE NULL,
    
    -- === INSPETOR ===
    inspector_id BIGINT UNSIGNED NULL,
    inspector_name VARCHAR(255) NULL,
    
    -- === NOTAS ===
    notes TEXT NULL,
    failure_reason TEXT NULL,
    corrective_action TEXT NULL,
    
    -- === AUDITORIA ===
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    INDEX idx_inspection_number (inspection_number),
    INDEX idx_inspectable (inspectable_type, inspectable_id),
    INDEX idx_status (status),
    INDEX idx_result (result),
    INDEX idx_date (inspection_date),
    
    FOREIGN KEY (inspector_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE quality_inspection_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    quality_inspection_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    
    -- === QUANTIDADE ===
    quantity_inspected INT NOT NULL,
    quantity_passed INT NOT NULL DEFAULT 0,
    quantity_failed INT NOT NULL DEFAULT 0,
    
    -- === RESULTADO ===
    result ENUM('passed', 'failed', 'conditional') NULL,
    
    -- === NOTAS ===
    defects_found TEXT NULL,
    notes TEXT NULL,
    
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_inspection (quality_inspection_id),
    INDEX idx_product (product_id),
    
    FOREIGN KEY (quality_inspection_id) REFERENCES quality_inspections(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE quality_checkpoints (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- === IDENTIFICAÇÃO ===
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    
    -- === TIPO ===
    checkpoint_type ENUM('visual', 'measurement', 'functional', 'documentation') NOT NULL,
    
    -- === CRITÉRIO ===
    criterion TEXT NOT NULL COMMENT 'Critério de aceitação',
    
    -- === APLICAÇÃO ===
    applies_to ENUM('all', 'product_category', 'specific_product') DEFAULT 'all',
    product_category_id BIGINT UNSIGNED NULL,
    product_id BIGINT UNSIGNED NULL,
    
    -- === STATUS ===
    is_active BOOLEAN DEFAULT TRUE,
    is_mandatory BOOLEAN DEFAULT FALSE,
    
    -- === ORDEM ===
    sort_order INT DEFAULT 0,
    
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_type (checkpoint_type),
    INDEX idx_active (is_active),
    
    FOREIGN KEY (product_category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE quality_inspection_checkpoints (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    quality_inspection_id BIGINT UNSIGNED NOT NULL,
    quality_checkpoint_id BIGINT UNSIGNED NOT NULL,
    
    -- === RESULTADO ===
    result ENUM('pass', 'fail', 'n/a') NOT NULL,
    
    -- === MEDIÇÃO ===
    measured_value VARCHAR(255) NULL,
    expected_value VARCHAR(255) NULL,
    
    -- === NOTAS ===
    notes TEXT NULL,
    
    -- === INSPETOR ===
    checked_by BIGINT UNSIGNED NULL,
    checked_at TIMESTAMP NULL,
    
    created_at TIMESTAMP NULL,
    
    INDEX idx_inspection (quality_inspection_id),
    INDEX idx_checkpoint (quality_checkpoint_id),
    
    FOREIGN KEY (quality_inspection_id) REFERENCES quality_inspections(id) ON DELETE CASCADE,
    FOREIGN KEY (quality_checkpoint_id) REFERENCES quality_checkpoints(id) ON DELETE RESTRICT,
    FOREIGN KEY (checked_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE quality_certificates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    quality_inspection_id BIGINT UNSIGNED NOT NULL,
    
    -- === CERTIFICADO ===
    certificate_number VARCHAR(100) NOT NULL UNIQUE,
    certificate_type VARCHAR(100) NOT NULL COMMENT 'ISO, CE, FDA, etc',
    
    -- === DATAS ===
    issue_date DATE NOT NULL,
    expiry_date DATE NULL,
    
    -- === ARQUIVO ===
    file_path VARCHAR(500) NULL,
    
    -- === STATUS ===
    status ENUM('valid', 'expired', 'revoked') DEFAULT 'valid',
    
    -- === NOTAS ===
    notes TEXT NULL,
    
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_certificate_number (certificate_number),
    INDEX idx_inspection (quality_inspection_id),
    INDEX idx_status (status),
    
    FOREIGN KEY (quality_inspection_id) REFERENCES quality_inspections(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 6.2 Service: QualityControlService

```php
<?php

namespace App\Services;

use App\Models\QualityInspection;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use Illuminate\Support\Facades\DB;

class QualityControlService
{
    /**
     * Criar inspeção de recebimento (PO)
     */
    public function createIncomingInspection(PurchaseOrder $po, array $data): QualityInspection
    {
        return DB::transaction(function () use ($po, $data) {
            // Criar inspeção
            $inspection = QualityInspection::create([
                'inspection_number' => $this->generateInspectionNumber(),
                'inspectable_type' => 'PurchaseOrder',
                'inspectable_id' => $po->id,
                'inspection_type' => 'incoming',
                'inspection_date' => $data['inspection_date'] ?? now(),
                'inspector_id' => $data['inspector_id'] ?? auth()->id(),
                'inspector_name' => $data['inspector_name'] ?? auth()->user()->name,
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);
            
            // Adicionar items
            foreach ($po->items as $poItem) {
                $inspection->items()->create([
                    'product_id' => $poItem->product_id,
                    'quantity_inspected' => $poItem->quantity,
                ]);
            }
            
            // Adicionar checkpoints
            $this->addCheckpoints($inspection);
            
            return $inspection->fresh();
        });
    }
    
    /**
     * Criar inspeção final (SO)
     */
    public function createFinalInspection(SalesOrder $so, array $data): QualityInspection
    {
        return DB::transaction(function () use ($so, $data) {
            $inspection = QualityInspection::create([
                'inspection_number' => $this->generateInspectionNumber(),
                'inspectable_type' => 'SalesOrder',
                'inspectable_id' => $so->id,
                'inspection_type' => 'final',
                'inspection_date' => $data['inspection_date'] ?? now(),
                'inspector_id' => $data['inspector_id'] ?? auth()->id(),
                'inspector_name' => $data['inspector_name'] ?? auth()->user()->name,
                'status' => 'pending',
                'created_by' => auth()->id(),
            ]);
            
            foreach ($so->items as $soItem) {
                $inspection->items()->create([
                    'product_id' => $soItem->product_id,
                    'quantity_inspected' => $soItem->quantity,
                ]);
            }
            
            $this->addCheckpoints($inspection);
            
            return $inspection->fresh();
        });
    }
    
    /**
     * Completar inspeção
     */
    public function completeInspection(QualityInspection $inspection, array $data): void
    {
        DB::transaction(function () use ($inspection, $data) {
            // Atualizar items
            foreach ($data['items'] as $itemId => $itemData) {
                $inspection->items()->where('id', $itemId)->update([
                    'quantity_passed' => $itemData['quantity_passed'],
                    'quantity_failed' => $itemData['quantity_failed'],
                    'result' => $itemData['result'],
                    'defects_found' => $itemData['defects_found'] ?? null,
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }
            
            // Determinar resultado geral
            $allPassed = $inspection->items()->where('result', '!=', 'passed')->count() === 0;
            $anyFailed = $inspection->items()->where('result', 'failed')->count() > 0;
            
            $result = $allPassed ? 'passed' : ($anyFailed ? 'failed' : 'conditional');
            
            // Atualizar inspeção
            $inspection->update([
                'status' => 'completed',
                'result' => $result,
                'completed_date' => now(),
                'failure_reason' => $data['failure_reason'] ?? null,
                'corrective_action' => $data['corrective_action'] ?? null,
            ]);
            
            // Se falhou, atualizar PO/SO
            if ($result === 'failed') {
                $this->handleFailedInspection($inspection);
            }
        });
    }
    
    /**
     * Adicionar checkpoints à inspeção
     */
    protected function addCheckpoints(QualityInspection $inspection): void
    {
        $checkpoints = QualityCheckpoint::where('is_active', true)
            ->where(function ($query) use ($inspection) {
                $query->where('applies_to', 'all');
                
                // Adicionar checkpoints específicos por produto
                if ($inspection->inspectable_type === 'PurchaseOrder') {
                    $productIds = $inspection->inspectable->items->pluck('product_id');
                    $query->orWhereIn('product_id', $productIds);
                }
            })
            ->orderBy('sort_order')
            ->get();
        
        foreach ($checkpoints as $checkpoint) {
            $inspection->checkpoints()->create([
                'quality_checkpoint_id' => $checkpoint->id,
                'result' => 'n/a',
            ]);
        }
    }
    
    /**
     * Tratar inspeção falhada
     */
    protected function handleFailedInspection(QualityInspection $inspection): void
    {
        if ($inspection->inspectable_type === 'PurchaseOrder') {
            $inspection->inspectable->update([
                'status' => 'quality_failed',
                'notes' => "Quality inspection failed: {$inspection->failure_reason}",
            ]);
        } elseif ($inspection->inspectable_type === 'SalesOrder') {
            $inspection->inspectable->update([
                'status' => 'quality_hold',
            ]);
        }
    }
    
    /**
     * Gerar número de inspeção
     */
    protected function generateInspectionNumber(): string
    {
        $year = now()->format('Y');
        
        $lastInspection = QualityInspection::withTrashed()
            ->where('inspection_number', 'like', "QC-{$year}-%")
            ->orderBy('inspection_number', 'desc')
            ->first();
        
        $sequential = 1;
        if ($lastInspection) {
            preg_match('/(\d{4})$/', $lastInspection->inspection_number, $matches);
            $sequential = ((int) $matches[1]) + 1;
        }
        
        return "QC-{$year}-" . str_pad($sequential, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Gerar certificado de qualidade
     */
    public function generateCertificate(QualityInspection $inspection, array $data): QualityCertificate
    {
        if ($inspection->result !== 'passed') {
            throw new \Exception('Cannot generate certificate for failed inspection');
        }
        
        return $inspection->certificates()->create([
            'certificate_number' => $this->generateCertificateNumber(),
            'certificate_type' => $data['certificate_type'],
            'issue_date' => $data['issue_date'] ?? now(),
            'expiry_date' => $data['expiry_date'] ?? null,
            'file_path' => $data['file_path'] ?? null,
            'status' => 'valid',
            'notes' => $data['notes'] ?? null,
        ]);
    }
    
    protected function generateCertificateNumber(): string
    {
        $year = now()->format('Y');
        $month = now()->format('m');
        
        $lastCert = QualityCertificate::where('certificate_number', 'like', "CERT-{$year}{$month}-%")
            ->orderBy('certificate_number', 'desc')
            ->first();
        
        $sequential = 1;
        if ($lastCert) {
            preg_match('/(\d{4})$/', $lastCert->certificate_number, $matches);
            $sequential = ((int) $matches[1]) + 1;
        }
        
        return "CERT-{$year}{$month}-" . str_pad($sequential, 4, '0', STR_PAD_LEFT);
    }
}
```

---

# 7. SUPPLIER PERFORMANCE 📈

## 7.1 Estrutura de Dados

```sql
CREATE TABLE supplier_performance_metrics (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    supplier_id BIGINT UNSIGNED NOT NULL,
    
    -- === PERÍODO ===
    period_year INT NOT NULL,
    period_month INT NOT NULL,
    
    -- === MÉTRICAS DE ENTREGA ===
    total_orders INT DEFAULT 0,
    on_time_deliveries INT DEFAULT 0,
    late_deliveries INT DEFAULT 0,
    average_delay_days DECIMAL(5,2) DEFAULT 0,
    
    -- === MÉTRICAS DE QUALIDADE ===
    total_inspections INT DEFAULT 0,
    passed_inspections INT DEFAULT 0,
    failed_inspections INT DEFAULT 0,
    quality_score DECIMAL(5,2) DEFAULT 0 COMMENT 'Percentual de aprovação',
    
    -- === MÉTRICAS FINANCEIRAS ===
    total_purchase_value BIGINT DEFAULT 0,
    total_orders_value BIGINT DEFAULT 0,
    average_order_value BIGINT DEFAULT 0,
    
    -- === MÉTRICAS DE COMUNICAÇÃO ===
    response_time_hours DECIMAL(10,2) DEFAULT 0,
    communication_score DECIMAL(5,2) DEFAULT 0,
    
    -- === SCORE GERAL ===
    overall_score DECIMAL(5,2) DEFAULT 0 COMMENT '0-100',
    rating ENUM('excellent', 'good', 'average', 'poor', 'unacceptable') NULL,
    
    -- === NOTAS ===
    notes TEXT NULL,
    
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    UNIQUE KEY unique_period (supplier_id, period_year, period_month),
    INDEX idx_supplier (supplier_id),
    INDEX idx_period (period_year, period_month),
    INDEX idx_score (overall_score),
    
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE supplier_performance_reviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    supplier_id BIGINT UNSIGNED NOT NULL,
    
    -- === PERÍODO ===
    review_date DATE NOT NULL,
    review_period_start DATE NOT NULL,
    review_period_end DATE NOT NULL,
    
    -- === AVALIAÇÃO ===
    delivery_score DECIMAL(5,2) NOT NULL COMMENT '0-100',
    quality_score DECIMAL(5,2) NOT NULL COMMENT '0-100',
    pricing_score DECIMAL(5,2) NOT NULL COMMENT '0-100',
    communication_score DECIMAL(5,2) NOT NULL COMMENT '0-100',
    overall_score DECIMAL(5,2) NOT NULL COMMENT '0-100',
    
    -- === CLASSIFICAÇÃO ===
    rating ENUM('A', 'B', 'C', 'D', 'F') NOT NULL,
    
    -- === COMENTÁRIOS ===
    strengths TEXT NULL,
    weaknesses TEXT NULL,
    recommendations TEXT NULL,
    
    -- === DECISÃO ===
    decision ENUM('continue', 'monitor', 'improve_required', 'discontinue') NOT NULL,
    
    -- === REVISOR ===
    reviewed_by BIGINT UNSIGNED NULL,
    
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_supplier (supplier_id),
    INDEX idx_review_date (review_date),
    INDEX idx_rating (rating),
    
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE supplier_issues (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    supplier_id BIGINT UNSIGNED NOT NULL,
    purchase_order_id BIGINT UNSIGNED NULL,
    
    -- === TIPO ===
    issue_type ENUM(
        'late_delivery',
        'quality_problem',
        'wrong_quantity',
        'wrong_product',
        'damaged_goods',
        'pricing_error',
        'communication_issue',
        'other'
    ) NOT NULL,
    
    -- === SEVERIDADE ===
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    
    -- === STATUS ===
    status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
    
    -- === DESCRIÇÃO ===
    description TEXT NOT NULL,
    
    -- === RESOLUÇÃO ===
    resolution TEXT NULL,
    resolution_date DATE NULL,
    
    -- === IMPACTO FINANCEIRO ===
    financial_impact BIGINT DEFAULT 0 COMMENT 'Custo do problema',
    
    -- === DATAS ===
    reported_date DATE NOT NULL,
    
    -- === RESPONSÁVEL ===
    reported_by BIGINT UNSIGNED NULL,
    assigned_to BIGINT UNSIGNED NULL,
    
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_supplier (supplier_id),
    INDEX idx_po (purchase_order_id),
    INDEX idx_type (issue_type),
    INDEX idx_severity (severity),
    INDEX idx_status (status),
    
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT,
    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE SET NULL,
    FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adicionar campos ao supplier
ALTER TABLE suppliers ADD COLUMN current_rating ENUM('A', 'B', 'C', 'D', 'F') NULL;
ALTER TABLE suppliers ADD COLUMN current_score DECIMAL(5,2) DEFAULT 0;
ALTER TABLE suppliers ADD COLUMN is_approved BOOLEAN DEFAULT TRUE;
ALTER TABLE suppliers ADD COLUMN approval_status ENUM('approved', 'conditional', 'suspended', 'blacklisted') DEFAULT 'approved';
```

## 7.2 Service: SupplierPerformanceService

```php
<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\SupplierPerformanceMetric;
use App\Models\PurchaseOrder;
use App\Models\QualityInspection;
use Illuminate\Support\Facades\DB;

class SupplierPerformanceService
{
    /**
     * Calcular métricas mensais
     */
    public function calculateMonthlyMetrics(Supplier $supplier, int $year, int $month): SupplierPerformanceMetric
    {
        $startDate = "{$year}-{$month}-01";
        $endDate = date('Y-m-t', strtotime($startDate));
        
        // Buscar POs do período
        $pos = PurchaseOrder::where('supplier_id', $supplier->id)
            ->whereBetween('po_date', [$startDate, $endDate])
            ->get();
        
        // Métricas de entrega
        $totalOrders = $pos->count();
        $onTimeDeliveries = $pos->where('delivery_status', 'on_time')->count();
        $lateDeliveries = $pos->where('delivery_status', 'late')->count();
        
        $totalDelay = $pos->sum(function ($po) {
            if ($po->actual_delivery_date && $po->expected_delivery_date) {
                $delay = $po->actual_delivery_date->diffInDays($po->expected_delivery_date, false);
                return $delay > 0 ? $delay : 0;
            }
            return 0;
        });
        
        $averageDelayDays = $lateDeliveries > 0 ? $totalDelay / $lateDeliveries : 0;
        
        // Métricas de qualidade
        $inspections = QualityInspection::where('inspectable_type', 'PurchaseOrder')
            ->whereIn('inspectable_id', $pos->pluck('id'))
            ->where('status', 'completed')
            ->get();
        
        $totalInspections = $inspections->count();
        $passedInspections = $inspections->where('result', 'passed')->count();
        $failedInspections = $inspections->where('result', 'failed')->count();
        $qualityScore = $totalInspections > 0 ? ($passedInspections / $totalInspections) * 100 : 0;
        
        // Métricas financeiras
        $totalPurchaseValue = $pos->sum('total_amount');
        $averageOrderValue = $totalOrders > 0 ? $totalPurchaseValue / $totalOrders : 0;
        
        // Calcular score geral
        $deliveryScore = $totalOrders > 0 ? ($onTimeDeliveries / $totalOrders) * 100 : 0;
        $overallScore = ($deliveryScore * 0.4) + ($qualityScore * 0.6);
        
        // Determinar rating
        $rating = $this->determineRating($overallScore);
        
        // Criar ou atualizar métrica
        $metric = SupplierPerformanceMetric::updateOrCreate(
            [
                'supplier_id' => $supplier->id,
                'period_year' => $year,
                'period_month' => $month,
            ],
            [
                'total_orders' => $totalOrders,
                'on_time_deliveries' => $onTimeDeliveries,
                'late_deliveries' => $lateDeliveries,
                'average_delay_days' => $averageDelayDays,
                'total_inspections' => $totalInspections,
                'passed_inspections' => $passedInspections,
                'failed_inspections' => $failedInspections,
                'quality_score' => $qualityScore,
                'total_purchase_value' => $totalPurchaseValue,
                'total_orders_value' => $totalPurchaseValue,
                'average_order_value' => $averageOrderValue,
                'overall_score' => $overallScore,
                'rating' => $rating,
            ]
        );
        
        // Atualizar supplier
        $supplier->update([
            'current_score' => $overallScore,
            'current_rating' => $this->scoreToLetterRating($overallScore),
        ]);
        
        return $metric;
    }
    
    /**
     * Criar review de performance
     */
    public function createPerformanceReview(Supplier $supplier, array $data): SupplierPerformanceReview
    {
        $overallScore = (
            $data['delivery_score'] * 0.3 +
            $data['quality_score'] * 0.3 +
            $data['pricing_score'] * 0.2 +
            $data['communication_score'] * 0.2
        );
        
        $rating = $this->scoreToLetterRating($overallScore);
        
        $review = SupplierPerformanceReview::create([
            'supplier_id' => $supplier->id,
            'review_date' => $data['review_date'] ?? now(),
            'review_period_start' => $data['review_period_start'],
            'review_period_end' => $data['review_period_end'],
            'delivery_score' => $data['delivery_score'],
            'quality_score' => $data['quality_score'],
            'pricing_score' => $data['pricing_score'],
            'communication_score' => $data['communication_score'],
            'overall_score' => $overallScore,
            'rating' => $rating,
            'strengths' => $data['strengths'] ?? null,
            'weaknesses' => $data['weaknesses'] ?? null,
            'recommendations' => $data['recommendations'] ?? null,
            'decision' => $data['decision'],
            'reviewed_by' => auth()->id(),
        ]);
        
        // Atualizar status do supplier baseado na decisão
        if ($data['decision'] === 'discontinue') {
            $supplier->update([
                'approval_status' => 'blacklisted',
                'is_approved' => false,
            ]);
        } elseif ($data['decision'] === 'monitor') {
            $supplier->update([
                'approval_status' => 'conditional',
            ]);
        }
        
        return $review;
    }
    
    /**
     * Registrar issue com fornecedor
     */
    public function reportIssue(Supplier $supplier, array $data): SupplierIssue
    {
        return SupplierIssue::create([
            'supplier_id' => $supplier->id,
            'purchase_order_id' => $data['purchase_order_id'] ?? null,
            'issue_type' => $data['issue_type'],
            'severity' => $data['severity'] ?? 'medium',
            'status' => 'open',
            'description' => $data['description'],
            'financial_impact' => $data['financial_impact'] ?? 0,
            'reported_date' => $data['reported_date'] ?? now(),
            'reported_by' => auth()->id(),
            'assigned_to' => $data['assigned_to'] ?? null,
        ]);
    }
    
    /**
     * Resolver issue
     */
    public function resolveIssue(SupplierIssue $issue, string $resolution): void
    {
        $issue->update([
            'status' => 'resolved',
            'resolution' => $resolution,
            'resolution_date' => now(),
        ]);
    }
    
    /**
     * Obter dashboard de performance
     */
    public function getPerformanceDashboard(Supplier $supplier): array
    {
        $lastMonth = SupplierPerformanceMetric::where('supplier_id', $supplier->id)
            ->orderBy('period_year', 'desc')
            ->orderBy('period_month', 'desc')
            ->first();
        
        $last12Months = SupplierPerformanceMetric::where('supplier_id', $supplier->id)
            ->where('period_year', '>=', now()->subYear()->year)
            ->orderBy('period_year')
            ->orderBy('period_month')
            ->get();
        
        $openIssues = SupplierIssue::where('supplier_id', $supplier->id)
            ->whereIn('status', ['open', 'in_progress'])
            ->get();
        
        return [
            'current_metrics' => $lastMonth,
            'trend_data' => $last12Months,
            'open_issues' => $openIssues,
            'total_issues' => SupplierIssue::where('supplier_id', $supplier->id)->count(),
            'critical_issues' => $openIssues->where('severity', 'critical')->count(),
            'average_score_12m' => $last12Months->avg('overall_score'),
            'total_purchase_12m' => $last12Months->sum('total_purchase_value'),
        ];
    }
    
    /**
     * Determinar rating baseado no score
     */
    protected function determineRating(float $score): string
    {
        if ($score >= 90) return 'excellent';
        if ($score >= 75) return 'good';
        if ($score >= 60) return 'average';
        if ($score >= 40) return 'poor';
        return 'unacceptable';
    }
    
    /**
     * Converter score para letra
     */
    protected function scoreToLetterRating(float $score): string
    {
        if ($score >= 90) return 'A';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C';
        if ($score >= 60) return 'D';
        return 'F';
    }
}
```

---

# 8. ROADMAP DE IMPLEMENTAÇÃO 🗓️

## Fase 1: Core Modules (4 semanas)

### Semana 1-2: Document Management + Shipping
- [ ] Migrations (documents, shipments, tracking_events)
- [ ] Models (Document, Shipment, ShipmentItem, TrackingEvent)
- [ ] Services (DocumentService, ShippingService)
- [ ] Filament Resources
- [ ] Testes básicos

### Semana 3-4: Dashboard + Email Automation
- [ ] Widgets (Financial, Operations, Charts)
- [ ] DashboardService
- [ ] Email templates (Blade)
- [ ] Mailable classes
- [ ] EmailAutomationService
- [ ] Scheduled jobs

**Entregável:** Sistema com documentos, shipping, dashboard e emails funcionando

---

## Fase 2: Warehouse Management (3 semanas)

### Semana 5-6: Warehouse Core
- [ ] Migrations (warehouses, locations, stock)
- [ ] Models (Warehouse, WarehouseLocation, WarehouseStock)
- [ ] WarehouseService
- [ ] Filament Resources

### Semana 7: Warehouse Transfers
- [ ] Migrations (transfers, transfer_items)
- [ ] Transfer functionality
- [ ] Filament UI
- [ ] Testes

**Entregável:** Gestão completa de múltiplos armazéns

---

## Fase 3: Quality Control (3 semanas)

### Semana 8-9: Quality Inspections
- [ ] Migrations (inspections, items, checkpoints)
- [ ] Models (QualityInspection, QualityCheckpoint)
- [ ] QualityControlService
- [ ] Filament Resources

### Semana 10: Quality Certificates
- [ ] Migration (certificates)
- [ ] Certificate generation
- [ ] PDF templates
- [ ] Testes

**Entregável:** Sistema completo de controle de qualidade

---

## Fase 4: Supplier Performance (3 semanas)

### Semana 11-12: Performance Metrics
- [ ] Migrations (metrics, reviews, issues)
- [ ] Models (SupplierPerformanceMetric, SupplierPerformanceReview)
- [ ] SupplierPerformanceService
- [ ] Cálculo automático de métricas

### Semana 13: Performance Dashboard
- [ ] Widgets de performance
- [ ] Relatórios
- [ ] Filament Resources
- [ ] Testes finais

**Entregável:** Sistema completo de avaliação de fornecedores

---

## TOTAL: 13 semanas (3.25 meses)

---

# 9. RESUMO EXECUTIVO 📊

## Módulos Implementados

| Módulo | Tabelas | Services | Resources | Complexidade |
|--------|---------|----------|-----------|--------------|
| **Document Management** | 2 | 1 | 1 | Baixa |
| **Shipping & Logistics** | 3 | 1 | 2 | Média |
| **Advanced Dashboard** | 0 | 1 | 5 widgets | Baixa |
| **Email Automation** | 0 | 1 | 3 emails | Baixa |
| **Warehouse Management** | 5 | 1 | 3 | Alta |
| **Quality Control** | 5 | 1 | 3 | Alta |
| **Supplier Performance** | 3 | 1 | 3 | Média |
| **TOTAL** | **18** | **7** | **15+** | - |

---

## Benefícios Esperados

### Document Management
- ✅ Todos os documentos organizados
- ✅ Versionamento automático
- ✅ Alertas de expiração
- ✅ Acesso rápido

### Shipping & Logistics
- ✅ Rastreamento em tempo real
- ✅ Notificações automáticas
- ✅ Histórico completo
- ✅ Integração com carriers

### Dashboard
- ✅ Visão 360° do negócio
- ✅ KPIs em tempo real
- ✅ Alertas proativos
- ✅ Decisões baseadas em dados

### Email Automation
- ✅ 80% redução em emails manuais
- ✅ Comunicação profissional
- ✅ Lembretes automáticos
- ✅ Melhor relacionamento com clientes

### Warehouse Management
- ✅ Múltiplos armazéns
- ✅ Localização precisa de produtos
- ✅ Transferências rastreadas
- ✅ Otimização de espaço

### Quality Control
- ✅ Inspeções padronizadas
- ✅ Rastreabilidade completa
- ✅ Certificados automáticos
- ✅ Redução de defeitos

### Supplier Performance
- ✅ Avaliação objetiva
- ✅ Identificação de problemas
- ✅ Melhoria contínua
- ✅ Decisões de sourcing embasadas

---

## Investimento vs Retorno

**Investimento:**
- 13 semanas de desenvolvimento
- 1 desenvolvedor full-stack
- 1 testador part-time

**Retorno Esperado:**
- 60% redução em trabalho manual
- 40% melhoria em qualidade
- 30% redução em problemas com fornecedores
- 50% melhoria em visibilidade operacional
- ROI estimado: 6-12 meses

---

# 10. PRÓXIMOS PASSOS 🚀

**Aguardando sua decisão:**

1️⃣ **Aprovar todos os módulos?**  
2️⃣ **Priorizar alguns módulos específicos?**  
3️⃣ **Ajustar alguma funcionalidade?**  
4️⃣ **Começar implementação imediatamente?**

---

**Estou pronto para implementar!** 🎯

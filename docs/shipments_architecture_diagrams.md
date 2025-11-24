# Shipments System - Architecture Diagrams

**Visual reference for the refactored Shipments system**

---

## 📊 Entity Relationship Diagram (ERD)

```mermaid
erDiagram
    SALES_INVOICE ||--o{ SALES_INVOICE_ITEM : contains
    SALES_INVOICE ||--o{ SHIPMENT_INVOICE : "linked to"
    SHIPMENT ||--o{ SHIPMENT_INVOICE : contains
    SHIPMENT ||--o{ SHIPMENT_ITEM : contains
    SHIPMENT ||--o{ PACKING_BOX : contains
    SHIPMENT ||--|| COMMERCIAL_INVOICE : generates
    SALES_INVOICE_ITEM ||--o{ SHIPMENT_ITEM : "shipped via"
    SHIPMENT_ITEM ||--o{ PACKING_BOX_ITEM : "packed in"
    PACKING_BOX ||--o{ PACKING_BOX_ITEM : contains
    PRODUCT ||--o{ SHIPMENT_ITEM : references
    
    SALES_INVOICE {
        bigint id PK
        string invoice_number UK
        bigint client_id FK
        date invoice_date
        decimal total
        string status
    }
    
    SALES_INVOICE_ITEM {
        bigint id PK
        bigint sales_invoice_id FK
        bigint product_id FK
        int quantity
        int quantity_shipped
        int quantity_remaining
        string shipment_status
    }
    
    SHIPMENT {
        bigint id PK
        string shipment_number UK
        string shipment_type
        string carrier
        string tracking_number
        string status
        date shipment_date
        int total_boxes
        decimal total_weight
        decimal total_volume
    }
    
    SHIPMENT_INVOICE {
        bigint id PK
        bigint shipment_id FK
        bigint sales_invoice_id FK
        int total_items
        int total_quantity
        decimal total_value
    }
    
    SHIPMENT_ITEM {
        bigint id PK
        bigint shipment_id FK
        bigint sales_invoice_item_id FK
        bigint product_id FK
        int quantity_to_ship
        int quantity_shipped
        string hs_code
        string country_of_origin
        decimal customs_value
        string packing_status
    }
    
    PACKING_BOX {
        bigint id PK
        bigint shipment_id FK
        int box_number
        string box_type
        decimal length
        decimal width
        decimal height
        decimal gross_weight
        decimal net_weight
        decimal volume
        string packing_status
    }
    
    PACKING_BOX_ITEM {
        bigint id PK
        bigint packing_box_id FK
        bigint shipment_item_id FK
        int quantity
    }
    
    COMMERCIAL_INVOICE {
        bigint id PK
        bigint shipment_id FK
        string invoice_number UK
        date invoice_date
        string exporter_name
        string importer_name
        string incoterm
        decimal total_value
        string status
    }
    
    PRODUCT {
        bigint id PK
        string sku UK
        string name
        string hs_code
        string country_of_origin
    }
```

---

## 🔄 Shipment Creation Workflow

```mermaid
flowchart TD
    Start([Start: Create Shipment]) --> Draft[Create Draft Shipment]
    Draft --> SelectInvoices[Select Sales Invoices]
    SelectInvoices --> ShowItems[Show Items from Selected Invoices]
    ShowItems --> SelectItems[Select Items & Quantities]
    
    SelectItems --> ValidateQty{Validate Quantities}
    ValidateQty -->|Invalid| ShowItems
    ValidateQty -->|Valid| CreateShipmentItems[Create Shipment Items]
    
    CreateShipmentItems --> PackingDecision{Pack Items?}
    PackingDecision -->|No| ShippingDetails
    PackingDecision -->|Yes| CreateBoxes[Create Packing Boxes]
    
    CreateBoxes --> AssignItems[Assign Items to Boxes]
    AssignItems --> ValidatePacking{All Items Packed?}
    ValidatePacking -->|No| AssignItems
    ValidatePacking -->|Yes| ShippingDetails[Enter Shipping Details]
    
    ShippingDetails --> GenerateDocs[Generate Documents]
    GenerateDocs --> CommercialInvoice[Commercial Invoice]
    GenerateDocs --> PackingList[Packing List]
    
    CommercialInvoice --> Review[Review Shipment]
    PackingList --> Review
    
    Review --> ConfirmDecision{Confirm Shipment?}
    ConfirmDecision -->|No| Draft
    ConfirmDecision -->|Yes| Confirm[Confirm & Lock Shipment]
    
    Confirm --> UpdateInvoices[Update Invoice Item Quantities]
    UpdateInvoices --> UpdateStatus[Update Shipment Status]
    UpdateStatus --> Notify[Send Notifications]
    Notify --> End([End: Shipment Confirmed])
    
    style Start fill:#90EE90
    style End fill:#90EE90
    style Confirm fill:#FFD700
    style ValidateQty fill:#FFA500
    style ValidatePacking fill:#FFA500
```

---

## 📦 Packing Process Flow

```mermaid
flowchart LR
    subgraph "Shipment Items"
        SI1[Item A<br/>Qty: 100]
        SI2[Item B<br/>Qty: 50]
        SI3[Item C<br/>Qty: 75]
    end
    
    subgraph "Packing Boxes"
        Box1[Box 1<br/>50x40x30cm<br/>25kg]
        Box2[Box 2<br/>50x40x30cm<br/>30kg]
        Box3[Box 3<br/>60x50x40cm<br/>40kg]
    end
    
    SI1 -->|60 pcs| Box1
    SI1 -->|40 pcs| Box2
    SI2 -->|30 pcs| Box1
    SI2 -->|20 pcs| Box3
    SI3 -->|75 pcs| Box3
    
    Box1 --> |Sealed| Shipment[Shipment<br/>Total: 3 boxes<br/>95kg, 0.25m³]
    Box2 --> |Sealed| Shipment
    Box3 --> |Sealed| Shipment
    
    style Shipment fill:#90EE90
    style Box1 fill:#87CEEB
    style Box2 fill:#87CEEB
    style Box3 fill:#87CEEB
```

---

## 🔢 Quantity Tracking Logic

```mermaid
flowchart TD
    Invoice[Sales Invoice Item<br/>Quantity: 100]
    
    Invoice --> Check{Check Remaining}
    Check --> Remaining[Remaining to Ship: 100]
    
    Remaining --> Ship1[Shipment 1<br/>Ship: 60]
    Ship1 --> Update1[Update Invoice<br/>Shipped: 60<br/>Remaining: 40]
    
    Update1 --> Status1{Check Status}
    Status1 -->|Remaining > 0| Partial1[Status: Partially Shipped]
    
    Partial1 --> Ship2[Shipment 2<br/>Ship: 40]
    Ship2 --> Update2[Update Invoice<br/>Shipped: 100<br/>Remaining: 0]
    
    Update2 --> Status2{Check Status}
    Status2 -->|Remaining = 0| Full[Status: Fully Shipped]
    
    style Invoice fill:#87CEEB
    style Partial1 fill:#FFD700
    style Full fill:#90EE90
```

---

## 🌍 Multi-Invoice Shipment Example

```mermaid
graph TB
    subgraph "Sales Invoices"
        INV1[Invoice SI-2025-001<br/>Client: ABC Corp<br/>Items: 3]
        INV2[Invoice SI-2025-002<br/>Client: ABC Corp<br/>Items: 2]
        INV3[Invoice SI-2025-003<br/>Client: XYZ Ltd<br/>Items: 4]
    end
    
    subgraph "Shipment SHP-2025-001"
        SHIP[Shipment Details<br/>Carrier: DHL<br/>Tracking: 123456]
        
        subgraph "Items from INV1"
            I1[Widget A x 50]
            I2[Widget B x 30]
        end
        
        subgraph "Items from INV2"
            I3[Widget C x 20]
        end
        
        subgraph "Items from INV3"
            I4[Widget D x 40]
            I5[Widget E x 25]
        end
    end
    
    INV1 -.->|Partial| SHIP
    INV2 -.->|Full| SHIP
    INV3 -.->|Partial| SHIP
    
    SHIP --> I1
    SHIP --> I2
    SHIP --> I3
    SHIP --> I4
    SHIP --> I5
    
    style SHIP fill:#90EE90
    style INV1 fill:#FFD700
    style INV2 fill:#90EE90
    style INV3 fill:#FFD700
```

---

## 📄 Document Generation Flow

```mermaid
flowchart LR
    Shipment[Confirmed Shipment] --> GenDocs{Generate Documents}
    
    GenDocs --> CI[Commercial Invoice]
    GenDocs --> PL[Packing List]
    GenDocs --> Other[Other Documents]
    
    CI --> CIData[Collect Data:<br/>- Exporter Info<br/>- Importer Info<br/>- Items with HS Codes<br/>- Customs Values<br/>- Incoterm]
    CIData --> CITemplate[Apply Template]
    CITemplate --> CIPDF[Commercial Invoice PDF]
    
    PL --> PLData[Collect Data:<br/>- Packing Boxes<br/>- Box Dimensions<br/>- Box Contents<br/>- Weights & Volumes]
    PLData --> PLTemplate[Apply Template]
    PLTemplate --> PLPDF[Packing List PDF]
    
    CIPDF --> Store[Store Documents]
    PLPDF --> Store
    Other --> Store
    
    Store --> Download[Download/Email/Print]
    
    style CIPDF fill:#90EE90
    style PLPDF fill:#90EE90
    style Download fill:#87CEEB
```

---

## 🎯 Shipment Status Lifecycle

```mermaid
stateDiagram-v2
    [*] --> Draft
    Draft --> Preparing: Add Items
    Preparing --> ReadyToShip: Complete Packing
    ReadyToShip --> Confirmed: Confirm Shipment
    Confirmed --> PickedUp: Carrier Pickup
    PickedUp --> InTransit: In Transit
    InTransit --> CustomsClearance: At Customs
    CustomsClearance --> OutForDelivery: Cleared
    OutForDelivery --> Delivered: Delivered
    Delivered --> [*]
    
    Draft --> Cancelled: Cancel
    Preparing --> Cancelled: Cancel
    ReadyToShip --> Cancelled: Cancel
    Confirmed --> Cancelled: Cancel
    Cancelled --> [*]
    
    InTransit --> Returned: Return to Sender
    Returned --> [*]
    
    note right of Draft
        Editable
        Can add/remove items
    end note
    
    note right of Confirmed
        Locked
        Updates invoice quantities
    end note
    
    note right of Delivered
        Final status
        Trigger notifications
    end note
```

---

## 🏗️ System Architecture Layers

```mermaid
graph TB
    subgraph "Presentation Layer"
        UI[Filament UI]
        Forms[Forms & Wizards]
        Tables[Data Tables]
        Actions[Actions & Buttons]
    end
    
    subgraph "Application Layer"
        Resources[Filament Resources]
        Pages[Custom Pages]
        RelationManagers[Relation Managers]
    end
    
    subgraph "Business Logic Layer"
        Services[Service Classes]
        Observers[Model Observers]
        Validators[Validators]
        Calculators[Calculators]
    end
    
    subgraph "Domain Layer"
        Models[Eloquent Models]
        Relationships[Relationships]
        Scopes[Query Scopes]
        Accessors[Accessors/Mutators]
    end
    
    subgraph "Data Layer"
        Database[(MySQL Database)]
        Migrations[Migrations]
        Seeders[Seeders]
    end
    
    subgraph "External Services"
        PDF[PDF Generator]
        Email[Email Service]
        Storage[File Storage]
        Tracking[Tracking APIs]
    end
    
    UI --> Resources
    Forms --> Resources
    Tables --> Resources
    Actions --> Resources
    
    Resources --> Services
    Pages --> Services
    RelationManagers --> Services
    
    Services --> Models
    Observers --> Models
    Validators --> Models
    Calculators --> Models
    
    Models --> Database
    Relationships --> Database
    Scopes --> Database
    
    Database --> Migrations
    Database --> Seeders
    
    Services --> PDF
    Services --> Email
    Services --> Storage
    Services --> Tracking
    
    style UI fill:#87CEEB
    style Services fill:#FFD700
    style Models fill:#90EE90
    style Database fill:#FFA500
```

---

## 📊 Data Flow: Creating a Shipment

```mermaid
sequenceDiagram
    participant User
    participant UI as Filament UI
    participant Service as ShipmentService
    participant Shipment
    participant ShipmentItem
    participant SalesInvoiceItem
    participant PackingBox
    
    User->>UI: Create New Shipment
    UI->>Service: createShipment(data)
    Service->>Shipment: create(basic_info)
    Shipment-->>Service: shipment_id
    
    User->>UI: Select Invoices
    UI->>Service: attachInvoices(shipment_id, invoice_ids)
    Service->>Shipment: invoices()->attach()
    
    User->>UI: Select Items & Quantities
    UI->>Service: validateQuantities(items)
    Service->>SalesInvoiceItem: checkRemaining()
    SalesInvoiceItem-->>Service: validation_result
    
    alt Quantities Valid
        Service->>ShipmentItem: create(items)
        ShipmentItem-->>Service: created
    else Quantities Invalid
        Service-->>UI: Error: Exceeds remaining
        UI-->>User: Show Error
    end
    
    User->>UI: Create Packing Boxes
    UI->>Service: createPackingBoxes(boxes)
    Service->>PackingBox: create(boxes)
    
    User->>UI: Assign Items to Boxes
    UI->>Service: assignItemsToBoxes(assignments)
    Service->>PackingBox: items()->attach()
    
    User->>UI: Confirm Shipment
    UI->>Service: confirmShipment(shipment_id)
    Service->>Service: validatePacking()
    
    alt Packing Valid
        Service->>Shipment: status = 'confirmed'
        Service->>ShipmentItem: quantity_shipped = quantity_to_ship
        Service->>SalesInvoiceItem: updateShippedQuantity()
        SalesInvoiceItem->>SalesInvoiceItem: updateStatus()
        Service-->>UI: Success
        UI-->>User: Shipment Confirmed
    else Packing Invalid
        Service-->>UI: Error: Packing incomplete
        UI-->>User: Show Error
    end
```

---

## 🎨 UI Component Hierarchy

```mermaid
graph TD
    ShipmentResource[ShipmentResource]
    
    ShipmentResource --> ListShipments[ListShipments Page]
    ShipmentResource --> CreateShipment[CreateShipment Page]
    ShipmentResource --> EditShipment[EditShipment Page]
    ShipmentResource --> ViewShipment[ViewShipment Page]
    
    CreateShipment --> Wizard[Wizard Form]
    Wizard --> Step1[Step 1: Basic Info]
    Wizard --> Step2[Step 2: Select Invoices]
    Wizard --> Step3[Step 3: Select Items]
    Wizard --> Step4[Step 4: Packing]
    Wizard --> Step5[Step 5: Shipping Details]
    Wizard --> Step6[Step 6: Review]
    
    ViewShipment --> TabOverview[Tab: Overview]
    ViewShipment --> TabInvoices[Tab: Invoices]
    ViewShipment --> TabItems[Tab: Items]
    ViewShipment --> TabPacking[Tab: Packing]
    ViewShipment --> TabDocuments[Tab: Documents]
    ViewShipment --> TabTracking[Tab: Tracking]
    
    TabInvoices --> InvoicesRM[InvoicesRelationManager]
    TabItems --> ItemsRM[ItemsRelationManager]
    TabPacking --> PackingRM[PackingBoxesRelationManager]
    
    TabDocuments --> GenCI[Generate Commercial Invoice]
    TabDocuments --> GenPL[Generate Packing List]
    
    ViewShipment --> Actions[Header Actions]
    Actions --> ConfirmAction[Confirm Shipment]
    Actions --> CancelAction[Cancel Shipment]
    Actions --> TrackAction[Add Tracking Event]
    
    style ShipmentResource fill:#87CEEB
    style Wizard fill:#FFD700
    style ViewShipment fill:#90EE90
```

---

## 🔐 Permission Structure

```mermaid
graph TB
    SuperAdmin[Super Admin]
    Manager[Manager]
    Warehouse[Warehouse Staff]
    Viewer[Viewer]
    
    SuperAdmin --> AllPerms[All Permissions]
    
    Manager --> ViewShipments[View Shipments]
    Manager --> CreateShipments[Create Shipments]
    Manager --> EditShipments[Edit Shipments]
    Manager --> ConfirmShipments[Confirm Shipments]
    Manager --> GenerateDocs[Generate Documents]
    Manager --> DeleteShipments[Delete Shipments]
    
    Warehouse --> ViewShipments
    Warehouse --> CreateShipments
    Warehouse --> EditShipments
    Warehouse --> PackItems[Pack Items]
    Warehouse --> UpdateTracking[Update Tracking]
    
    Viewer --> ViewShipments
    Viewer --> ViewDocs[View Documents]
    
    style SuperAdmin fill:#FF6347
    style Manager fill:#FFD700
    style Warehouse fill:#87CEEB
    style Viewer fill:#90EE90
```

---

These diagrams provide a visual reference for understanding the refactored Shipments system architecture, workflows, and data relationships.

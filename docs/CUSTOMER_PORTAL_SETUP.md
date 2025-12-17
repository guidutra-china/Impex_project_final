# Customer Portal - Setup & Usage Guide

**Date:** December 16, 2025  
**Status:** ✅ Complete (Phase 1 & 2)

## 🎯 Overview

The Customer Portal is a separate Filament panel that allows your clients to access their data in a secure, role-based environment. Clients can view quotes, purchase orders, and invoices without accessing your internal admin panel.

## 📋 Features Implemented

### **Purchasing Department** (Role: `purchasing`)
1. **Customer Quotes**
   - View all quotes for their company
   - See quote options (Option A, B, C...)
   - Select preferred option
   - Track quote status

2. **Purchase Orders**
   - View all purchase orders
   - Track order status
   - See supplier information
   - View items and pricing

### **Finance Department** (Role: `finance`)
1. **Proforma Invoices**
   - View all invoices
   - See payment terms
   - Track approval status
   - View items breakdown

### **Security Features**
- ✅ Multi-tenancy (users see only their client's data)
- ✅ Role-based access control
- ✅ Separate authentication from admin panel
- ✅ Read-only access (no create/edit/delete)
- ✅ Automatic data filtering by `client_id`

---

## 🚀 Setup Instructions

### **Step 1: Run Migrations**

Add `client_id` field to users table:

```bash
php artisan migrate
```

This will create the foreign key relationship between users and clients.

---

### **Step 2: Create Portal Roles**

Run the seeder to create the portal roles:

```bash
php artisan db:seed --class=PortalRolesSeeder
```

This creates three roles:
- `purchasing` - Access to quotes and purchase orders
- `finance` - Access to invoices and payments
- `logistics` - Access to shipments (future)

---

### **Step 3: Create Portal Users**

You need to create users for your clients. Here's how:

#### **Option A: Via Admin Panel**

1. Go to **Admin Panel** → **Users**
2. Click **Create User**
3. Fill in user details:
   - Name
   - Email
   - Password
   - **Client:** Select the client company
   - **Role:** Select `purchasing` or `finance`
4. Save

#### **Option B: Via Tinker**

```bash
php artisan tinker
```

```php
// Create a purchasing user for Abbott and Sons
$user = \App\Models\User::create([
    'name' => 'John Doe',
    'email' => 'john@abbottandsons.com',
    'password' => bcrypt('password123'),
    'client_id' => 1, // Abbott and Sons client ID
    'status' => 'active',
]);

// Assign purchasing role
$user->assignRole('purchasing');

// Create a finance user for the same client
$user2 = \App\Models\User::create([
    'name' => 'Jane Smith',
    'email' => 'jane@abbottandsons.com',
    'password' => bcrypt('password123'),
    'client_id' => 1,
    'status' => 'active',
]);

$user2->assignRole('finance');
```

---

### **Step 4: Test Portal Access**

1. **Open Portal URL:**
   ```
   http://your-domain.com/portal/login
   ```

2. **Login with portal user credentials**

3. **Verify access:**
   - Purchasing users should see: Customer Quotes, Purchase Orders
   - Finance users should see: Proforma Invoices

---

## 📊 Portal Structure

```
/portal
├── Dashboard (role-based widgets)
├── Purchasing (role: purchasing)
│   ├── Customer Quotes
│   │   ├── List (filtered by client_id)
│   │   └── View (with select option action)
│   └── Purchase Orders
│       ├── List (filtered by client_id)
│       └── View (with items)
└── Finance (role: finance)
    └── Proforma Invoices
        ├── List (filtered by client_id)
        └── View (with complete info)
```

---

## 🔒 Security & Data Isolation

### **Multi-Tenancy Implementation**

All resources automatically filter data by `client_id`:

**Customer Quotes:**
```php
whereHas('order', function ($query) {
    $query->where('client_id', auth()->user()->client_id);
});
```

**Purchase Orders:**
```php
whereHas('proformaInvoice.order', function ($query) {
    $query->where('client_id', auth()->user()->client_id);
});
```

**Proforma Invoices:**
```php
whereHas('order', function ($query) {
    $query->where('client_id', auth()->user()->client_id);
});
```

### **Role-Based Access**

Each resource checks permissions:

```php
public static function canAccess(): bool
{
    return auth()->user()->hasRole('purchasing');
}
```

---

## 🎨 Customization

### **Change Portal Branding**

Edit `app/Providers/Filament/PortalPanelProvider.php`:

```php
->brandName('Your Company Portal')
->favicon(asset('favicon.ico'))
->colors([
    'primary' => Color::Blue, // Change primary color
])
```

### **Add Custom Widgets to Dashboard**

Edit `app/Filament/Portal/Pages/Dashboard.php`:

```php
public function getWidgets(): array
{
    return [
        \App\Filament\Portal\Widgets\QuotesOverviewWidget::class,
        \App\Filament\Portal\Widgets\OrdersStatusWidget::class,
    ];
}
```

### **Customize Dashboard View**

Edit `resources/views/filament/portal/pages/dashboard.blade.php`

---

## 📝 Usage Workflows

### **Workflow 1: Customer Selects Quote Option**

1. **Admin creates Customer Quote** with multiple options
2. **Customer logs into portal** (purchasing role)
3. **Navigates to Customer Quotes** → Views quote
4. **Sees all options** (Option A, B, C...)
5. **Clicks "Select Option"** button
6. **Chooses preferred option** from dropdown
7. **Submits selection**
8. **System updates** `is_selected_by_customer` flag
9. **Admin sees selection** in admin panel

### **Workflow 2: Customer Tracks Purchase Order**

1. **Admin creates Purchase Order** (after Proforma approval)
2. **Customer logs into portal** (purchasing role)
3. **Navigates to Purchase Orders**
4. **Views PO details:**
   - Supplier information
   - Items ordered
   - Status (sent, confirmed, in production, completed)
   - Total amount

### **Workflow 3: Finance Reviews Proforma Invoice**

1. **Admin creates Proforma Invoice**
2. **Finance user logs into portal** (finance role)
3. **Navigates to Proforma Invoices**
4. **Views invoice details:**
   - Items breakdown
   - Payment terms
   - Total amount
   - Approval status

---

## 🐛 Troubleshooting

### **Issue: User can't login to portal**

**Solution:**
1. Check if user has `client_id` set
2. Verify user has portal role (`purchasing`, `finance`, or `logistics`)
3. Check user status is `active`

```bash
php artisan tinker
```

```php
$user = \App\Models\User::where('email', 'user@example.com')->first();
$user->client_id; // Should not be null
$user->getRoleNames(); // Should include 'purchasing' or 'finance'
$user->status; // Should be 'active'
```

### **Issue: User sees no data in portal**

**Solution:**
1. Verify `client_id` matches existing client
2. Check if client has orders/quotes/invoices
3. Verify relationships are correct

```php
$user = auth()->user();
$user->client; // Should return Client model
$user->client->orders; // Should return orders
```

### **Issue: Portal shows 404**

**Solution:**
1. Clear cache: `php artisan optimize:clear`
2. Verify `PortalPanelProvider` is registered in `bootstrap/providers.php`
3. Check routes: `php artisan route:list | grep portal`

---

## 🔄 Future Enhancements

### **Phase 3 (Planned)**
- [ ] Email notifications when quotes are sent
- [ ] Email tracking (opened, viewed)
- [ ] Analytics dashboard
- [ ] Logistics role implementation
- [ ] Shipment tracking
- [ ] Document downloads (PDF)
- [ ] Comments/messaging system

---

## 📊 Database Schema Changes

### **Migration: Add client_id to users**

```sql
ALTER TABLE users 
ADD COLUMN client_id BIGINT UNSIGNED NULL 
AFTER is_admin;

ALTER TABLE users 
ADD CONSTRAINT users_client_id_foreign 
FOREIGN KEY (client_id) REFERENCES clients(id) 
ON DELETE SET NULL;
```

### **Roles Created**

```sql
INSERT INTO roles (name, guard_name) VALUES
('purchasing', 'web'),
('finance', 'web'),
('logistics', 'web');
```

---

## 🎯 Best Practices

### **Creating Portal Users**

1. **Always set client_id** - Users without client_id can't access portal
2. **Assign appropriate role** - Don't give admin roles to portal users
3. **Use strong passwords** - Portal is externally accessible
4. **Set status to active** - Inactive users can't login

### **Managing Access**

1. **One user per email** - Don't share credentials
2. **Separate roles** - Don't mix purchasing and finance in same user
3. **Regular audits** - Review portal users periodically
4. **Deactivate when needed** - Set status to 'inactive' instead of deleting

### **Data Security**

1. **Never bypass client_id filter** - Always use `getEloquentQuery()`
2. **Keep read-only** - Don't add create/edit actions
3. **Validate permissions** - Always use `canAccess()` checks
4. **Log access** - Consider adding activity logging

---

## 📞 Support

If you encounter issues:

1. Check this documentation first
2. Review error logs: `storage/logs/laravel.log`
3. Test with tinker to verify data
4. Check Filament documentation: https://filamentphp.com

---

**Status:** ✅ Customer Portal is ready for production use!

**Access URLs:**
- Admin Panel: `/admin`
- Customer Portal: `/portal`

**Default Test Credentials:** (Create your own)
- Email: `customer@example.com`
- Password: `password123`
- Role: `purchasing` or `finance`

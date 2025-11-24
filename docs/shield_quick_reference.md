# Filament Shield - Quick Reference

## 🚀 Installation Commands

```bash
# Install package
composer require bezhansalleh/filament-shield

# Install Shield (interactive)
php artisan shield:install

# Generate permissions for all Resources
php artisan shield:generate --all

# Generate permissions for specific Resource
php artisan shield:generate --resource=FinancialTransactionResource

# Create super admin user
php artisan shield:super-admin
```

---

## 👤 User Management (Tinker)

```bash
php artisan tinker
```

### Create User with Role
```php
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => bcrypt('password'),
]);

$user->assignRole('super_admin');
```

### Assign Multiple Roles
```php
$user->assignRole(['accountant', 'manager']);
```

### Remove Role
```php
$user->removeRole('accountant');
```

### Check if User has Role
```php
$user->hasRole('super_admin'); // true/false
```

### Check if User has Permission
```php
$user->can('view_any_financial_transaction'); // true/false
```

### Get User's Roles
```php
$user->roles; // Collection of roles
```

### Get User's Permissions
```php
$user->getAllPermissions(); // Collection of permissions
```

---

## 🛡️ Role Management (Tinker)

### Create Role
```php
use Spatie\Permission\Models\Role;

$role = Role::create(['name' => 'accountant']);
```

### Assign Permissions to Role
```php
use Spatie\Permission\Models\Permission;

$role = Role::findByName('accountant');

$role->givePermissionTo([
    'view_any_financial_transaction',
    'view_financial_transaction',
    'create_financial_transaction',
    'update_financial_transaction',
]);
```

### Remove Permission from Role
```php
$role->revokePermissionTo('delete_financial_transaction');
```

### Get Role's Permissions
```php
$role->permissions; // Collection
```

---

## 🔐 Permission Naming Pattern

```
{action}_{resource_name}
```

### Standard Actions
- `view` - View single record
- `view_any` - View list/table
- `create` - Create new record
- `update` - Edit existing record
- `delete` - Soft delete record
- `delete_any` - Bulk delete
- `force_delete` - Permanently delete
- `force_delete_any` - Bulk permanent delete
- `restore` - Restore soft-deleted record
- `restore_any` - Bulk restore
- `replicate` - Duplicate record

### Examples
```
view_any_financial_transaction
create_financial_payment
update_recurring_transaction
delete_financial_category
force_delete_bank_account
restore_supplier
```

---

## 🎯 Common Role Configurations

### Super Admin (Full Access)
```php
$user->assignRole('super_admin');
// Has ALL permissions automatically
```

### Accountant (Finance Only)
```php
$role = Role::create(['name' => 'accountant']);

$permissions = [
    // Financial Transactions
    'view_any_financial_transaction',
    'view_financial_transaction',
    'create_financial_transaction',
    'update_financial_transaction',
    
    // Financial Payments
    'view_any_financial_payment',
    'view_financial_payment',
    'create_financial_payment',
    'update_financial_payment',
    
    // Recurring Transactions
    'view_any_recurring_transaction',
    'view_recurring_transaction',
    'create_recurring_transaction',
    'update_recurring_transaction',
    
    // Financial Categories
    'view_any_financial_category',
    'view_financial_category',
    
    // Bank Accounts
    'view_any_bank_account',
    'view_bank_account',
];

$role->givePermissionTo($permissions);
```

### Manager (Most Access, No Force Delete)
```php
$role = Role::create(['name' => 'manager']);

// Get all permissions except force_delete
$permissions = Permission::where('name', 'not like', 'force_delete%')->pluck('name');

$role->givePermissionTo($permissions);
```

### Viewer (Read-Only)
```php
$role = Role::create(['name' => 'viewer']);

// Only view permissions
$permissions = Permission::where('name', 'like', 'view%')->pluck('name');

$role->givePermissionTo($permissions);
```

---

## 🧹 Maintenance Commands

### Clear Caches
```bash
php artisan cache:clear
php artisan config:clear
php artisan permission:cache-reset
```

### Regenerate Permissions (after adding new Resources)
```bash
php artisan shield:generate --all
```

### Publish Shield Config (for customization)
```bash
php artisan vendor:publish --tag=filament-shield-config
```

---

## 🔍 Debugging

### List All Roles
```php
use Spatie\Permission\Models\Role;
Role::all()->pluck('name');
```

### List All Permissions
```php
use Spatie\Permission\Models\Permission;
Permission::all()->pluck('name');
```

### Check User's Permissions
```php
$user = User::find(1);
$user->getAllPermissions()->pluck('name');
```

### Check Role's Permissions
```php
$role = Role::findByName('accountant');
$role->permissions->pluck('name');
```

### Users with Specific Role
```php
User::role('accountant')->get();
```

### Users with Specific Permission
```php
User::permission('create_financial_transaction')->get();
```

---

## ⚠️ Important Notes

1. **Super Admin Bypass:** Users with `super_admin` role bypass ALL permission checks
2. **Cache:** Permissions are cached. Clear cache after changes: `php artisan permission:cache-reset`
3. **Middleware:** Shield automatically applies middleware to Resources
4. **Direct Permissions:** You can assign permissions directly to users (not recommended, use roles instead)
5. **Multiple Roles:** Users can have multiple roles
6. **Guard:** Default guard is `web`. Change in `config/filament-shield.php` if needed

---

## 🎨 Custom Permissions in Resources

### Example: Add custom permission check
```php
// In your Resource
public static function canApprove(Model $record): bool
{
    return auth()->user()->can('approve_financial_transaction');
}

// In your Action
Action::make('approve')
    ->visible(fn ($record) => static::canApprove($record))
```

---

## 📱 Quick Test Script

Create `tests/test_permissions.php`:

```php
<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

// Create test users
$admin = User::create([
    'name' => 'Admin',
    'email' => 'admin@test.com',
    'password' => bcrypt('password'),
]);
$admin->assignRole('super_admin');

$accountant = User::create([
    'name' => 'Accountant',
    'email' => 'accountant@test.com',
    'password' => bcrypt('password'),
]);
$accountant->assignRole('accountant');

$viewer = User::create([
    'name' => 'Viewer',
    'email' => 'viewer@test.com',
    'password' => bcrypt('password'),
]);
$viewer->assignRole('viewer');

echo "✅ Test users created!\n";
echo "Admin: admin@test.com / password\n";
echo "Accountant: accountant@test.com / password\n";
echo "Viewer: viewer@test.com / password\n";
```

Run: `php artisan tinker < tests/test_permissions.php`

---

## 🆘 Emergency: Reset All Permissions

```bash
php artisan migrate:fresh --seed
php artisan shield:generate --all
php artisan shield:super-admin
```

⚠️ **WARNING:** This will delete ALL data!

---

## 📞 Support

- **Issues:** https://github.com/bezhanSalleh/filament-shield/issues
- **Discussions:** https://github.com/bezhanSalleh/filament-shield/discussions
- **Filament Discord:** https://discord.gg/filamentphp

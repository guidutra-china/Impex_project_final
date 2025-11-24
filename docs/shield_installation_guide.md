# Filament Shield Installation Guide

**Date:** November 24, 2025  
**Purpose:** Install and configure Filament Shield for role-based permissions

---

## 📦 Step 1: Install the Package

Run in your local environment:

```bash
composer require bezhansalleh/filament-shield
```

---

## 🔧 Step 2: Install Shield

Run the installation command:

```bash
php artisan shield:install
```

This will:
- Publish Shield configuration
- Publish Shield migrations
- Install Spatie Laravel Permission package
- Configure the User model

**When prompted, answer:**
- "Would you like to run migrations?" → **Yes**
- "Would you like to generate permissions?" → **Yes** (we'll do this later with custom options)

---

## 📝 Step 3: Update User Model

The installer should automatically update your `User` model, but verify it has:

```php
<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        // Only allow users with 'super_admin' role or specific permissions
        return $this->hasRole('super_admin') || $this->can('view_admin');
    }
}
```

---

## 🛡️ Step 4: Generate Permissions

Generate permissions for all your Resources:

```bash
php artisan shield:generate --all
```

This will create permissions for:
- ✅ view
- ✅ view_any
- ✅ create
- ✅ update
- ✅ delete
- ✅ delete_any
- ✅ force_delete
- ✅ force_delete_any
- ✅ restore
- ✅ restore_any
- ✅ replicate

For each Resource in your project.

---

## 👤 Step 5: Create Super Admin User

Create a super admin user (if you don't have one):

```bash
php artisan shield:super-admin
```

Or manually in tinker:

```bash
php artisan tinker
```

```php
$user = User::create([
    'name' => 'Super Admin',
    'email' => 'admin@example.com',
    'password' => bcrypt('password'),
]);

$user->assignRole('super_admin');
```

---

## 🎯 Step 6: Protect Resources

Shield automatically protects your Resources. No additional code needed!

But you can customize by adding to your Resource:

```php
public static function canViewAny(): bool
{
    return auth()->user()->can('view_any_financial_transaction');
}

public static function canCreate(): bool
{
    return auth()->user()->can('create_financial_transaction');
}

public static function canEdit(Model $record): bool
{
    return auth()->user()->can('update_financial_transaction');
}

public static function canDelete(Model $record): bool
{
    return auth()->user()->can('delete_financial_transaction');
}
```

---

## 📋 Step 7: Manage Roles & Permissions

Shield automatically creates a **Role Resource** in your admin panel.

Navigate to: **Admin Panel → Shield → Roles**

**Default Roles Created:**
- `super_admin` - Full access to everything
- `panel_user` - Basic panel access

**Create Custom Roles:**
1. Click "Create Role"
2. Enter name (e.g., "Accountant", "Manager", "Viewer")
3. Select permissions for each Resource
4. Save

**Assign Roles to Users:**
1. Go to Users Resource
2. Edit a user
3. Select role(s) from dropdown
4. Save

---

## 🔐 Permission Naming Convention

Shield uses this pattern:

```
{action}_{resource_name}
```

**Examples:**
- `view_any_financial_transaction`
- `create_financial_payment`
- `update_recurring_transaction`
- `delete_financial_category`

---

## 🎨 Step 8: Customize Shield (Optional)

### Exclude Resources from Shield

Edit `config/filament-shield.php`:

```php
'exclude' => [
    'enabled' => true,
    'resources' => [
        // Add Resources to exclude from Shield
    ],
],
```

### Custom Permissions

Add custom permissions in `config/filament-shield.php`:

```php
'permission_prefixes' => [
    'view',
    'view_any',
    'create',
    'update',
    'delete',
    'delete_any',
    'force_delete',
    'force_delete_any',
    'restore',
    'restore_any',
    'replicate',
    'reorder', // Custom permission
    'approve', // Custom permission
],
```

---

## 🧪 Step 9: Test Permissions

1. **Create Test Users:**
   - Super Admin (full access)
   - Accountant (finance only)
   - Viewer (read-only)

2. **Assign Roles:**
   - Super Admin → `super_admin` role
   - Accountant → Create "Accountant" role with finance permissions
   - Viewer → Create "Viewer" role with view-only permissions

3. **Test Access:**
   - Login as each user
   - Verify they can only see/do what they're allowed

---

## 📊 Common Role Examples

### Accountant Role
Permissions:
- ✅ view_any_financial_transaction
- ✅ view_financial_transaction
- ✅ create_financial_transaction
- ✅ update_financial_transaction
- ✅ view_any_financial_payment
- ✅ view_financial_payment
- ✅ create_financial_payment
- ✅ update_financial_payment
- ✅ view_any_recurring_transaction
- ✅ view_recurring_transaction
- ❌ delete_* (no delete permissions)

### Manager Role
Permissions:
- ✅ All view permissions
- ✅ All create permissions
- ✅ All update permissions
- ✅ delete_* (except force_delete)
- ❌ force_delete_* (only super admin)

### Viewer Role
Permissions:
- ✅ view_any_* (all resources)
- ✅ view_* (all resources)
- ❌ create_* (no create)
- ❌ update_* (no update)
- ❌ delete_* (no delete)

---

## 🚨 Troubleshooting

### Issue: "Permission denied" even as super_admin

**Solution:**
```bash
php artisan cache:clear
php artisan config:clear
php artisan shield:generate --all
```

### Issue: Roles not showing in User form

**Solution:**
Make sure `HasRoles` trait is added to User model:
```php
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;
}
```

### Issue: Permissions not working after adding new Resource

**Solution:**
Regenerate permissions:
```bash
php artisan shield:generate --all
```

---

## 📚 Resources

- **Shield Documentation:** https://filamentphp.com/plugins/bezhansalleh-shield
- **Spatie Permissions:** https://spatie.be/docs/laravel-permission
- **Shield GitHub:** https://github.com/bezhanSalleh/filament-shield

---

## ✅ Checklist

After installation, verify:

- [ ] Shield package installed
- [ ] Migrations run
- [ ] User model updated with `HasRoles` trait
- [ ] Permissions generated for all Resources
- [ ] Super admin user created
- [ ] Role Resource visible in admin panel
- [ ] Test user with limited permissions works correctly

---

## 🎉 You're Done!

Your Filament application now has a complete role-based access control system!

**Next Steps:**
1. Create roles for your organization (Accountant, Manager, etc.)
2. Assign users to appropriate roles
3. Test access control with different users
4. Customize permissions as needed

Enjoy your secure application! 🔐

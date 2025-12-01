# Role Access Management Guide

## 🔐 Can See All Feature

The system has a `can_see_all` field in the `roles` table that controls whether users with that role can see all clients and related data, regardless of ownership.

---

## 📊 How It Works

### **Roles with `can_see_all = true`**
- See ALL clients
- See ALL RFQs (Orders)
- See ALL Purchase Orders
- See ALL Sales Invoices
- See ALL Supplier Quotes

### **Roles with `can_see_all = false`**
- See ONLY clients where `client.user_id = their_user_id`
- See ONLY RFQs/POs/Invoices related to their clients

---

## 🛠️ Managing can_see_all

### **Option 1: Via Tinker (Recommended)**

```bash
php artisan tinker
```

**Enable "See All" for a role:**
```php
$role = Spatie\Permission\Models\Role::where('name', 'manager')->first();
$role->can_see_all = true;
$role->save();
echo "✅ Role '{$role->name}' can now see all clients!";
exit
```

**Disable "See All" for a role:**
```php
$role = Spatie\Permission\Models\Role::where('name', 'sales_rep')->first();
$role->can_see_all = false;
$role->save();
echo "✅ Role '{$role->name}' now sees only assigned clients!";
exit
```

**Check current status:**
```php
$roles = Spatie\Permission\Models\Role::all();
foreach ($roles as $role) {
    $status = $role->can_see_all ? '✅ YES' : '❌ NO';
    echo "{$role->name}: Can See All = {$status}\n";
}
exit
```

---

### **Option 2: Via Database**

```sql
-- Enable "See All" for a role
UPDATE roles SET can_see_all = 1 WHERE name = 'manager';

-- Disable "See All" for a role
UPDATE roles SET can_see_all = 0 WHERE name = 'sales_rep';

-- Check all roles
SELECT name, can_see_all FROM roles;
```

---

### **Option 3: Via Migration (When Creating New Roles)**

When you create a new role via seeder or migration:

```php
use Spatie\Permission\Models\Role;

// Create role with "See All" enabled
$role = Role::create([
    'name' => 'manager',
    'guard_name' => 'web',
    'can_see_all' => true, // ← Set here
]);

// Or create and then update
$role = Role::create(['name' => 'sales_rep']);
$role->can_see_all = false;
$role->save();
```

---

## 📋 Common Scenarios

### **Scenario 1: New Manager Role**
You want managers to see everything:

```bash
php artisan tinker
```
```php
$role = Spatie\Permission\Models\Role::create(['name' => 'manager']);
$role->can_see_all = true;
$role->save();
$role->givePermissionTo(['view_any_client', 'view_any_order', 'view_any_purchase_order']);
exit
```

### **Scenario 2: New Sales Rep Role**
You want sales reps to see only their clients:

```bash
php artisan tinker
```
```php
$role = Spatie\Permission\Models\Role::create(['name' => 'sales_rep']);
$role->can_see_all = false; // ← Default, but explicit is better
$role->save();
$role->givePermissionTo(['view_any_client', 'view_any_order']);
exit
```

### **Scenario 3: Convert Existing Role**
You have a "team_lead" role that should see everything:

```bash
php artisan tinker
```
```php
$role = Spatie\Permission\Models\Role::where('name', 'team_lead')->first();
$role->can_see_all = true;
$role->save();
exit
```

---

## 🎯 Current Setup

After running the migration, your roles are:

| Role | can_see_all | Description |
|------|-------------|-------------|
| `super_admin` | ✅ `true` | Full access (set automatically by migration) |
| `panel_user` | ❌ `false` | Sees only assigned clients |

---

## 💡 Best Practices

1. **Super Admin** should always have `can_see_all = true`
2. **Manager/Admin** roles typically have `can_see_all = true`
3. **Sales/Account Manager** roles typically have `can_see_all = false`
4. Document which roles have full access in your team wiki

---

## 🔍 Troubleshooting

### **User can't see their clients**
Check:
1. Is the client assigned to them? `client.user_id = user.id`
2. Does their role have `can_see_all = false`?
3. Do they have the correct permissions?

### **User sees too much**
Check:
1. Do they have a role with `can_see_all = true`?
2. If they have multiple roles, ANY role with `can_see_all = true` gives full access

### **Changes not taking effect**
```bash
php artisan optimize:clear
```
Then logout and login again.

---

## 📚 Technical Details

### **Database Schema**
```sql
ALTER TABLE roles ADD COLUMN can_see_all BOOLEAN DEFAULT FALSE;
```

### **Scope Logic**
Located in: `app/Models/Scopes/ClientOwnershipScope.php`

```php
$canSeeAll = $user->roles()->where('can_see_all', true)->exists();

if ($canSeeAll) {
    return; // No filtering
}

// Apply ownership filters...
```

### **Policy Logic**
Located in: `app/Traits/HasClientOwnership.php`

```php
protected function canSeeAll(User $user): bool
{
    return $user->roles()->where('can_see_all', true)->exists();
}
```

---

## 🎊 Summary

The `can_see_all` feature is **working perfectly** via database-level management. While there's no UI checkbox (due to Filament version compatibility), managing it via Tinker or SQL is simple and effective.

**Quick Command:**
```bash
php artisan tinker
Spatie\Permission\Models\Role::where('name', 'YOUR_ROLE')->first()->update(['can_see_all' => true]);
exit
```

Done! 🚀

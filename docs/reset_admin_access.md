# Reset Admin Access - Troubleshooting Guide

## 🚨 Problem: Can't Access with admin@impex.ltd

---

## ✅ Solution 1: Reset Admin User (RECOMMENDED)

### Quick Reset Script

I've created a script that automatically resets the admin user.

```bash
php artisan tinker < reset_admin.php
```

This will:
- ✅ Find or create admin@impex.ltd user
- ✅ Reset password to: `password`
- ✅ Assign super_admin role
- ✅ Set is_admin flag to 1
- ✅ Remove any conflicting roles

**After running, login with:**
- Email: `admin@impex.ltd`
- Password: `password`

---

## ✅ Solution 2: Manual Reset via Tinker

```bash
php artisan tinker
```

### Option A: Reset Existing User

```php
$user = User::where('email', 'admin@impex.ltd')->first();

// Reset password
$user->password = bcrypt('password');
$user->is_admin = 1;
$user->save();

// Assign super_admin role
$user->syncRoles(['super_admin']);

echo "✅ Admin reset! Login: admin@impex.ltd / password";
```

### Option B: Create New Admin User

```php
// Delete old user if exists
User::where('email', 'admin@impex.ltd')->delete();

// Create new admin
$user = User::create([
    'name' => 'Admin',
    'email' => 'admin@impex.ltd',
    'password' => bcrypt('password'),
    'is_admin' => 1,
]);

// Assign super_admin role
$user->assignRole('super_admin');

echo "✅ New admin created! Login: admin@impex.ltd / password";
```

---

## ✅ Solution 3: Check User Status

### Verify User Exists

```bash
php artisan tinker
```

```php
$user = User::where('email', 'admin@impex.ltd')->first();

if ($user) {
    echo "✅ User exists\n";
    echo "Name: " . $user->name . "\n";
    echo "Email: " . $user->email . "\n";
    echo "is_admin: " . $user->is_admin . "\n";
    echo "Roles: " . $user->roles->pluck('name')->implode(', ') . "\n";
    echo "Can access panel: " . ($user->canAccessPanel(app(\Filament\Panel::class)) ? 'YES' : 'NO') . "\n";
} else {
    echo "❌ User not found!\n";
}
```

### Check Roles

```php
use Spatie\Permission\Models\Role;

// List all roles
Role::all()->pluck('name');

// Check if super_admin exists
Role::where('name', 'super_admin')->exists();
```

---

## ✅ Solution 4: Database Direct Check

### Check users table

```bash
php artisan tinker
```

```php
// List all users
User::all(['id', 'name', 'email', 'is_admin']);

// Check specific user
DB::table('users')->where('email', 'admin@impex.ltd')->first();
```

### Check model_has_roles table

```php
// Check user's roles
DB::table('model_has_roles')
    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
    ->where('model_type', 'App\\Models\\User')
    ->where('model_id', 1) // Change to your user ID
    ->select('roles.name')
    ->get();
```

---

## 🔍 Common Issues & Fixes

### Issue 1: "Invalid credentials"

**Cause:** Wrong password or user doesn't exist

**Fix:**
```bash
php artisan tinker < reset_admin.php
```

### Issue 2: "You don't have permission to access this panel"

**Cause:** User doesn't have super_admin role or is_admin is not set

**Fix:**
```php
$user = User::where('email', 'admin@impex.ltd')->first();
$user->is_admin = 1;
$user->save();
$user->assignRole('super_admin');
```

### Issue 3: "Too many login attempts"

**Cause:** Account locked due to failed login attempts

**Fix:**
```bash
# Clear rate limiter cache
php artisan cache:clear

# Or wait 1 minute and try again
```

### Issue 4: User exists but can't login

**Cause:** Password hash issue or email verification required

**Fix:**
```php
$user = User::where('email', 'admin@impex.ltd')->first();
$user->password = bcrypt('password');
$user->email_verified_at = now(); // If email verification is enabled
$user->save();
```

---

## 🆘 Emergency: Create New Super Admin

If nothing works, create a completely new super admin:

```bash
php artisan tinker
```

```php
// Create new admin with different email
$admin = User::create([
    'name' => 'Emergency Admin',
    'email' => 'emergency@impex.ltd',
    'password' => bcrypt('emergency123'),
    'is_admin' => 1,
    'email_verified_at' => now(),
]);

// Ensure super_admin role exists
$role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin']);

// Assign role
$admin->assignRole('super_admin');

echo "✅ Emergency admin created!\n";
echo "Email: emergency@impex.ltd\n";
echo "Password: emergency123\n";
```

Then login with:
- Email: `emergency@impex.ltd`
- Password: `emergency123`

---

## 🧹 Clean Start (Nuclear Option)

If you want to completely reset permissions:

```bash
# Backup database first!
php artisan db:backup # If you have backup package

# Clear all roles and permissions
php artisan tinker
```

```php
// Remove all role assignments
DB::table('model_has_roles')->truncate();
DB::table('model_has_permissions')->truncate();
DB::table('role_has_permissions')->truncate();

// Delete all roles except super_admin
\Spatie\Permission\Models\Role::where('name', '!=', 'super_admin')->delete();

// Recreate super_admin role
$role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin']);

// Assign to admin user
$user = User::where('email', 'admin@impex.ltd')->first();
$user->assignRole('super_admin');

echo "✅ Permissions reset!";
```

Then regenerate permissions:

```bash
php artisan shield:generate --all
```

---

## 🔐 Security Best Practices

### Change Default Password

After logging in, immediately change the password:

1. Go to **Profile** (top right)
2. Click **Edit Profile**
3. Change password
4. Save

### Create Personal Admin Account

Don't use shared admin accounts in production:

```php
$myAdmin = User::create([
    'name' => 'Your Name',
    'email' => 'your.email@company.com',
    'password' => bcrypt('your-secure-password'),
    'is_admin' => 1,
]);

$myAdmin->assignRole('super_admin');
```

---

## 📋 Verification Checklist

After reset, verify:

- [ ] Can login with admin@impex.ltd / password
- [ ] User has super_admin role
- [ ] User has is_admin = 1
- [ ] Can access all menu items
- [ ] Can create/edit/delete records
- [ ] Shield → Roles is accessible

---

## 🧪 Test Login

### Via Browser
1. Go to `/admin/login`
2. Enter: `admin@impex.ltd`
3. Password: `password`
4. Click Login

### Via Tinker (Test Authentication)
```php
$user = User::where('email', 'admin@impex.ltd')->first();

// Test password
Hash::check('password', $user->password); // Should return true

// Test panel access
$user->canAccessPanel(app(\Filament\Panel::class)); // Should return true

// Test role
$user->hasRole('super_admin'); // Should return true
```

---

## 📞 Still Can't Login?

### Check these:

1. **Environment:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   ```

2. **Database connection:**
   ```bash
   php artisan tinker
   >>> DB::connection()->getPdo();
   ```

3. **User model:**
   ```bash
   php artisan tinker
   >>> User::count(); // Should return number > 0
   ```

4. **Filament config:**
   ```bash
   cat config/filament.php | grep -i auth
   ```

---

## 🎯 Quick Commands Summary

```bash
# Reset admin user (EASIEST)
php artisan tinker < reset_admin.php

# Clear caches
php artisan cache:clear
php artisan config:clear

# Check user in database
php artisan tinker
>>> User::where('email', 'admin@impex.ltd')->first();

# Create emergency admin
php artisan shield:super-admin
```

---

## ✅ Expected Result

After following any of the solutions above:

```
✅ Login successful
✅ Redirected to admin dashboard
✅ All menu items visible
✅ Full access to all resources
✅ Shield → Roles accessible
```

**Default credentials:**
- Email: `admin@impex.ltd`
- Password: `password`

**Remember to change the password after first login!** 🔐

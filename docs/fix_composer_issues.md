# Fix Composer Issues - Shield Installation

## 🚨 Problems Identified

### Problem 1: Missing PHP Extension
```
ext-mysql_xdevapi * but it is missing from your system
```

### Problem 2: Security Advisory
```
symfony/http-foundation affected by security advisories (PKSA-365x-2zjk-pt47)
```

---

## ✅ Solution 1: Remove mysql_xdevapi Requirement

This extension is NOT needed for your project. Let's remove it.

### Step 1: Edit composer.json

Open `composer.json` and look for this section:

```json
"require": {
    "ext-mysql_xdevapi": "*",
    ...
}
```

**Remove the line:** `"ext-mysql_xdevapi": "*",`

### Step 2: Update Laravel Framework (Security Fix)

The security advisory is for Symfony HTTP Foundation. Update Laravel to get the fix:

```bash
composer update laravel/framework symfony/http-foundation --with-all-dependencies
```

---

## ✅ Solution 2: Quick Fix (Ignore Platform Requirements)

If you want to install Shield immediately without fixing the above:

```bash
composer require bezhansalleh/filament-shield --ignore-platform-req=ext-mysql_xdevapi
```

**⚠️ Note:** This only ignores the extension check, you still need to fix the security issue.

---

## ✅ Complete Fix (Recommended)

### Step 1: Remove mysql_xdevapi from composer.json

```bash
# Open composer.json in your editor and remove:
"ext-mysql_xdevapi": "*",
```

### Step 2: Update Dependencies (Fix Security Issue)

```bash
composer update laravel/framework symfony/http-foundation --with-all-dependencies
```

### Step 3: Install Shield

```bash
composer require bezhansalleh/filament-shield
```

---

## 🔧 Alternative: Ignore Security Advisory (Not Recommended)

If you can't update right now, you can temporarily ignore the advisory:

Edit `composer.json` and add:

```json
{
    "config": {
        "audit": {
            "ignore": ["PKSA-365x-2zjk-pt47"]
        }
    }
}
```

Then run:

```bash
composer require bezhansalleh/filament-shield --ignore-platform-req=ext-mysql_xdevapi
```

---

## 📋 Full Step-by-Step

### 1. Check your composer.json

```bash
cat composer.json | grep mysql_xdevapi
```

If it shows the extension, remove it manually.

### 2. Remove the extension requirement

Open `composer.json` and delete:
```json
"ext-mysql_xdevapi": "*",
```

### 3. Update Laravel (Security Fix)

```bash
composer update laravel/framework symfony/http-foundation --with-all-dependencies
```

This will update to a secure version of Symfony HTTP Foundation.

### 4. Install Shield

```bash
composer require bezhansalleh/filament-shield
```

### 5. Continue with Shield Installation

```bash
php artisan shield:install
php artisan shield:generate --all
php artisan shield:super-admin
```

---

## 🆘 If Update Fails

If updating Laravel/Symfony fails due to version conflicts:

### Option A: Update Laravel to Latest

```bash
composer update laravel/framework --with-all-dependencies
```

### Option B: Ignore Advisory Temporarily

Edit `composer.json`:

```json
{
    "config": {
        "audit": {
            "block-insecure": false
        }
    }
}
```

Then:

```bash
composer require bezhansalleh/filament-shield --ignore-platform-req=ext-mysql_xdevapi
```

**⚠️ Remember to fix the security issue later!**

---

## 🔍 Why mysql_xdevapi?

The `mysql_xdevapi` extension is for MySQL X DevAPI (Document Store). You likely don't need it unless you're using:
- MySQL 8.0+ Document Store
- NoSQL-style operations on MySQL

For standard MySQL operations, you only need:
- `ext-pdo`
- `ext-pdo_mysql`

These are already in your system.

---

## 🧪 Verify After Fix

```bash
# Check if Shield is installed
composer show bezhansalleh/filament-shield

# Check Laravel version
php artisan --version

# Check Symfony version
composer show symfony/http-foundation
```

---

## 📞 Still Having Issues?

### Check PHP Extensions

```bash
php -m | grep -i mysql
```

Should show:
- `mysqli`
- `pdo_mysql`

Should NOT require:
- `mysql_xdevapi`

### Check Composer Config

```bash
composer config --list
```

Look for `platform` settings that might be forcing the extension.

---

## ✅ Expected Result

After the fix:

```bash
composer require bezhansalleh/filament-shield
```

Should output:

```
Using version ^3.x for bezhansalleh/filament-shield
./composer.json has been updated
Running composer update bezhansalleh/filament-shield
...
Package operations: X installs, Y updates, Z removals
  - Installing spatie/laravel-permission
  - Installing bezhansalleh/filament-shield
...
Package manifest generated successfully.
```

Then continue with:

```bash
php artisan shield:install
```

---

## 🎯 Summary

**Quick Fix:**
1. Remove `"ext-mysql_xdevapi": "*"` from `composer.json`
2. Run: `composer update laravel/framework symfony/http-foundation --with-all-dependencies`
3. Run: `composer require bezhansalleh/filament-shield`
4. Run: `php artisan shield:install`

**Done!** 🎉

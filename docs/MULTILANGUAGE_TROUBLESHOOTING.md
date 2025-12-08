# Multi-Language Troubleshooting Guide

## 🐛 Problem: Language Not Changing

If you've switched the language in your profile but the interface is still in English, follow these steps:

---

## ✅ Step 1: Verify Migration Ran

```bash
# Check if locale column exists in users table
php artisan tinker
>>> \DB::select("DESCRIBE users");
# Look for 'locale' column
>>> exit
```

**Expected:** You should see a `locale` column in the users table.

**If missing:**
```bash
php artisan migrate
```

---

## ✅ Step 2: Check Your User's Locale

```bash
php artisan tinker
>>> $user = \App\Models\User::find(YOUR_USER_ID);
>>> $user->locale;
# Should show: "zh_CN" or "en"
>>> exit
```

**If it shows `null`:**
1. Go to Profile → Personal Information
2. Select language
3. Click "Save Changes"
4. Check again

---

## ✅ Step 3: Clear All Caches

```bash
# Clear ALL caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Restart PHP-FPM
sudo systemctl restart php8.3-fpm

# Restart web server
sudo systemctl restart nginx
# OR
sudo systemctl restart apache2
```

---

## ✅ Step 4: Verify Translation Files Exist

```bash
# Check if translation files exist
ls -la lang/en/
ls -la lang/zh_CN/

# Should show:
# - common.php
# - fields.php
# - navigation.php
# - documents.php
```

**If missing:** Pull from GitHub again
```bash
git pull origin main
```

---

## ✅ Step 5: Test Translations Manually

Create a test file `test_translation.php`:

```php
<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Test English
app()->setLocale('en');
echo "English: " . __('navigation.shipments') . "\n";

// Test Chinese
app()->setLocale('zh_CN');
echo "Chinese: " . __('navigation.shipments') . "\n";
```

Run it:
```bash
php test_translation.php
```

**Expected output:**
```
English: Shipments
Chinese: 货运
```

**If you see:**
```
English: navigation.shipments
Chinese: navigation.shipments
```
→ Translation files are not being loaded!

---

## ✅ Step 6: Check Middleware is Registered

```bash
cat bootstrap/app.php | grep SetLocale
```

**Expected:** Should show `SetLocale::class`

**If missing:**
```bash
# Edit bootstrap/app.php
# Add to web middleware group:
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\SetLocale::class,
    ]);
})
```

---

## ✅ Step 7: Check Session is Working

```bash
php artisan tinker
>>> session()->put('test', 'value');
>>> session()->get('test');
# Should show: "value"
>>> exit
```

**If session doesn't work:**
```bash
# Check session driver
cat .env | grep SESSION_DRIVER

# Should be: file, database, or redis
# NOT: array or cookie

# Clear session
php artisan session:clear
```

---

## ✅ Step 8: Force Locale in Middleware

Temporarily edit `app/Http/Middleware/SetLocale.php`:

```php
public function handle(Request $request, Closure $next): Response
{
    // TEMPORARY DEBUG - Force Chinese
    app()->setLocale('zh_CN');
    
    // ... rest of code
    
    return $next($request);
}
```

**Refresh browser** → Should see Chinese

**If it works:** Problem is with locale detection logic  
**If it doesn't work:** Problem is with translation files or Filament

---

## ✅ Step 9: Check Browser Console

1. Open browser DevTools (F12)
2. Go to Console tab
3. Look for JavaScript errors
4. Go to Network tab
5. Refresh page
6. Check if any requests are failing

---

## ✅ Step 10: Verify File Permissions

```bash
# Translation files must be readable
chmod -R 755 lang/
chown -R www-data:www-data lang/

# Or your web server user:
chown -R nginx:nginx lang/
```

---

## 🔍 Common Issues & Solutions

### Issue 1: "Class SetLocale not found"
**Solution:**
```bash
composer dump-autoload
php artisan config:clear
```

### Issue 2: Translations show as "navigation.shipments"
**Cause:** Translation files not found or not loaded

**Solutions:**
1. Check file exists: `lang/zh_CN/navigation.php`
2. Check file permissions
3. Clear config cache: `php artisan config:clear`
4. Check locale is exactly `zh_CN` (case-sensitive!)

### Issue 3: Language changes but reverts on refresh
**Cause:** Session not persisting or middleware not running

**Solutions:**
1. Check session driver in `.env`
2. Clear sessions: `php artisan session:clear`
3. Verify middleware is registered
4. Check if user->locale is saved in database

### Issue 4: Some parts change, others don't
**Cause:** Not all Resources have translation methods

**Solution:** We applied translations to 31 resources. Check if the specific resource you're looking at was included.

---

## 📊 Debugging Checklist

- [ ] Migration ran (locale column exists)
- [ ] User locale is set in database
- [ ] All caches cleared
- [ ] PHP-FPM restarted
- [ ] Translation files exist
- [ ] Translation files are readable
- [ ] Middleware is registered
- [ ] Session is working
- [ ] Manual translation test works
- [ ] Browser console has no errors

---

## 🆘 Still Not Working?

Run this comprehensive debug script:

```bash
php artisan tinker
```

```php
// 1. Check user
$user = auth()->user();
echo "User locale: " . ($user->locale ?? 'NULL') . "\n";

// 2. Check session
echo "Session locale: " . (session('locale') ?? 'NULL') . "\n";

// 3. Check app
echo "App locale: " . app()->getLocale() . "\n";

// 4. Check config
echo "Available locales: " . json_encode(config('app.available_locales')) . "\n";

// 5. Test translation
app()->setLocale('zh_CN');
echo "Test translation: " . __('navigation.shipments') . "\n";

// 6. Check if file exists
echo "File exists: " . (file_exists(base_path('lang/zh_CN/navigation.php')) ? 'YES' : 'NO') . "\n";

// 7. Load file directly
$trans = include base_path('lang/zh_CN/navigation.php');
echo "Direct load: " . ($trans['shipments'] ?? 'NOT FOUND') . "\n";

exit
```

**Send me the output** and I can help diagnose the issue!

---

## 📝 Quick Fix Commands

```bash
# Nuclear option - reset everything
git pull origin main
php artisan migrate:fresh --seed
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
composer dump-autoload
sudo systemctl restart php8.3-fpm nginx

# Then login and set language again
```

⚠️ **WARNING:** `migrate:fresh` will delete all data!

---

## ✅ Expected Behavior

When working correctly:

1. **User sets language** in Profile → Personal Information
2. **Clicks "Save Changes"**
3. **Database updated:** `users.locale = 'zh_CN'`
4. **Session updated:** `session('locale') = 'zh_CN'`
5. **App locale set:** `app()->setLocale('zh_CN')`
6. **Page refreshes**
7. **Middleware runs:** Detects user->locale = 'zh_CN'
8. **Sets app locale:** `app()->setLocale('zh_CN')`
9. **Filament renders:** Calls `__('navigation.shipments')`
10. **Laravel loads:** `lang/zh_CN/navigation.php`
11. **Returns:** "货运"
12. **User sees:** Chinese navigation! 🎉

---

## 📧 Need Help?

If none of these steps work, provide:

1. Output of debug script above
2. Laravel version: `php artisan --version`
3. Filament version: `composer show filament/filament`
4. PHP version: `php --version`
5. Any error messages from logs: `tail -50 storage/logs/laravel.log`

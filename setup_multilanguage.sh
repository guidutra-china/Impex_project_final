#!/bin/bash

# Multi-Language Setup Script for Impex Project
# Languages: English (EN) + Chinese Simplified (ZH_CN)

echo "🌍 Setting up Multi-Language Support..."
echo ""

# Step 1: Install Filament Translatable Plugin
echo "📦 Step 1: Installing Filament Spatie Translatable Plugin..."
# composer require filament/spatie-laravel-translatable-plugin
# Note: Run this manually in your local environment
echo "   ⚠️  Run manually: composer require filament/spatie-laravel-translatable-plugin"
echo ""

# Step 2: Create lang directory structure
echo "📁 Step 2: Creating translation file structure..."
mkdir -p lang/en
mkdir -p lang/zh_CN

# Create translation files for English
touch lang/en/common.php
touch lang/en/fields.php
touch lang/en/resources.php
touch lang/en/navigation.php
touch lang/en/actions.php
touch lang/en/notifications.php
touch lang/en/documents.php
touch lang/en/validation.php

# Create translation files for Chinese
touch lang/zh_CN/common.php
touch lang/zh_CN/fields.php
touch lang/zh_CN/resources.php
touch lang/zh_CN/navigation.php
touch lang/zh_CN/actions.php
touch lang/zh_CN/notifications.php
touch lang/zh_CN/documents.php
touch lang/zh_CN/validation.php

echo "   ✅ Translation files created"
echo ""

# Step 3: Create migration for user locale
echo "🗄️  Step 3: Creating migration for user locale..."
php artisan make:migration add_locale_to_users_table --path=database/migrations
echo "   ✅ Migration created (needs to be edited manually)"
echo ""

# Step 4: Create middleware
echo "🔧 Step 4: Creating SetLocale middleware..."
php artisan make:middleware SetLocale
echo "   ✅ Middleware created (needs to be edited manually)"
echo ""

echo "✨ Setup complete!"
echo ""
echo "📋 Next steps:"
echo "   1. Run: composer require filament/spatie-laravel-translatable-plugin"
echo "   2. Edit migration: database/migrations/*_add_locale_to_users_table.php"
echo "   3. Run: php artisan migrate"
echo "   4. Edit middleware: app/Http/Middleware/SetLocale.php"
echo "   5. Register middleware in app/Http/Kernel.php"
echo "   6. Configure Filament in app/Providers/Filament/AdminPanelProvider.php"
echo ""

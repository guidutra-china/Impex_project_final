#!/bin/bash

# Setup Tests Script
# This script prepares the environment for running tests
# Compatible with macOS and Linux

set -e

echo "🧪 Setting up test environment..."

# Check if .env.testing exists, if not create it
if [ ! -f .env.testing ]; then
    echo "📋 Creating .env.testing..."
    cp .env.example .env.testing
    
    # Use different sed syntax for macOS and Linux
    if [[ "$OSTYPE" == "darwin"* ]]; then
        # macOS
        sed -i '' 's/APP_ENV=local/APP_ENV=testing/' .env.testing
        sed -i '' 's/DB_CONNECTION=mysql/DB_CONNECTION=sqlite/' .env.testing
        sed -i '' 's/DB_DATABASE=.*/DB_DATABASE=:memory:/' .env.testing
    else
        # Linux
        sed -i 's/APP_ENV=local/APP_ENV=testing/' .env.testing
        sed -i 's/DB_CONNECTION=mysql/DB_CONNECTION=sqlite/' .env.testing
        sed -i 's/DB_DATABASE=.*/DB_DATABASE=:memory:/' .env.testing
    fi
else
    echo "✅ .env.testing already exists"
fi

# Generate APP_KEY if not present
if ! grep -q "APP_KEY=base64:" .env.testing; then
    echo "🔑 Generating APP_KEY..."
    php artisan key:generate --env=testing
else
    echo "✅ APP_KEY already configured"
fi

# Clear test cache
echo "🧹 Clearing cache..."
php artisan config:clear --env=testing
php artisan cache:clear --env=testing

# Run migrations for testing database
echo "📊 Running migrations for test database..."
php artisan migrate --env=testing --force

# Seed the database with minimal test data using TestDatabaseSeeder
echo "🌱 Seeding test database..."
php artisan db:seed Database\\Seeders\\TestDatabaseSeeder --env=testing --force

echo ""
echo "✅ Test environment setup complete!"
echo ""
echo "Run tests with: php artisan test"
echo "Run specific test: php artisan test tests/Feature/YourTest.php"
echo "Run with coverage: php artisan test --coverage"

#!/bin/bash

# Payment Terms Enhancement Deployment Script
# This script deploys the Payment Terms enhancements with shipment date support

set -e  # Exit on error

echo "=========================================="
echo "Payment Terms Enhancement Deployment"
echo "=========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}ℹ $1${NC}"
}

# Check if we're in the correct directory
if [ ! -f "artisan" ]; then
    print_error "Error: artisan file not found. Please run this script from the Laravel root directory."
    exit 1
fi

print_info "Starting deployment process..."
echo ""

# Step 1: Backup database
echo "Step 1: Creating database backup..."
BACKUP_FILE="backup_$(date +%Y%m%d_%H%M%S).sql"
print_warning "Please backup your database manually before proceeding!"
print_info "Recommended command: php artisan db:backup (if available) or mysqldump"
echo ""
read -p "Have you backed up the database? (yes/no): " backup_confirm
if [ "$backup_confirm" != "yes" ]; then
    print_error "Deployment cancelled. Please backup your database first."
    exit 1
fi
print_success "Database backup confirmed"
echo ""

# Step 2: Pull latest changes from Git
echo "Step 2: Pulling latest changes from Git..."
if git pull origin main; then
    print_success "Git pull completed"
else
    print_warning "Git pull failed or no changes to pull"
fi
echo ""

# Step 3: Check PHP version
echo "Step 3: Checking PHP version..."
PHP_VERSION=$(php -r 'echo PHP_VERSION;')
print_info "PHP Version: $PHP_VERSION"
if php -r 'exit(version_compare(PHP_VERSION, "8.2.0", ">=") ? 0 : 1);'; then
    print_success "PHP version is 8.2 or higher"
else
    print_error "PHP version must be 8.2 or higher. Current: $PHP_VERSION"
    exit 1
fi
echo ""

# Step 4: Install/Update Composer dependencies
echo "Step 4: Updating Composer dependencies..."
if composer install --no-dev --optimize-autoloader; then
    print_success "Composer dependencies updated"
else
    print_error "Composer install failed"
    exit 1
fi
echo ""

# Step 5: Clear caches
echo "Step 5: Clearing application caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
print_success "Caches cleared"
echo ""

# Step 6: Run migrations
echo "Step 6: Running database migrations..."
print_warning "The following migrations will be executed:"
echo "  - 2025_11_22_120000_add_payment_term_id_to_invoices.php"
echo "  - 2025_11_22_130000_add_calculation_base_to_payment_term_stages.php"
echo "  - 2025_11_22_130001_add_shipment_date_to_invoices.php"
echo ""
read -p "Proceed with migrations? (yes/no): " migrate_confirm
if [ "$migrate_confirm" != "yes" ]; then
    print_error "Deployment cancelled by user"
    exit 1
fi

if php artisan migrate --force; then
    print_success "Migrations completed successfully"
else
    print_error "Migration failed! Please check the error messages above."
    print_warning "You may need to rollback: php artisan migrate:rollback"
    exit 1
fi
echo ""

# Step 7: Optimize application
echo "Step 7: Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
print_success "Application optimized"
echo ""

# Step 8: Verify migrations
echo "Step 8: Verifying database changes..."
print_info "Checking if new columns exist..."

# Check if columns were added (this is a basic check)
if php artisan tinker --execute="
    echo 'Checking payment_term_stages table...' . PHP_EOL;
    if (Schema::hasColumn('payment_term_stages', 'calculation_base')) {
        echo '✓ calculation_base column exists' . PHP_EOL;
    } else {
        echo '✗ calculation_base column missing!' . PHP_EOL;
    }
    
    echo 'Checking invoices tables...' . PHP_EOL;
    if (Schema::hasColumn('purchase_invoices', 'payment_term_id')) {
        echo '✓ purchase_invoices.payment_term_id exists' . PHP_EOL;
    }
    if (Schema::hasColumn('purchase_invoices', 'shipment_date')) {
        echo '✓ purchase_invoices.shipment_date exists' . PHP_EOL;
    }
    if (Schema::hasColumn('sales_invoices', 'payment_term_id')) {
        echo '✓ sales_invoices.payment_term_id exists' . PHP_EOL;
    }
    if (Schema::hasColumn('sales_invoices', 'shipment_date')) {
        echo '✓ sales_invoices.shipment_date exists' . PHP_EOL;
    }
"; then
    print_success "Database verification completed"
else
    print_warning "Could not verify database changes automatically"
fi
echo ""

# Step 9: Restart services (if applicable)
echo "Step 9: Restarting services..."
print_warning "You may need to restart the following services manually:"
echo "  - PHP-FPM: sudo systemctl restart php8.2-fpm"
echo "  - Nginx: sudo systemctl restart nginx"
echo "  - Queue workers: php artisan queue:restart"
echo ""
read -p "Do you want to restart queue workers now? (yes/no): " queue_confirm
if [ "$queue_confirm" = "yes" ]; then
    php artisan queue:restart
    print_success "Queue workers restarted"
fi
echo ""

# Final summary
echo "=========================================="
echo "Deployment Summary"
echo "=========================================="
print_success "All deployment steps completed successfully!"
echo ""
echo "Changes deployed:"
echo "  ✓ Payment Terms now support calculation from Invoice Date or Shipment Date"
echo "  ✓ Added payment_term_id to both invoice types"
echo "  ✓ Added shipment_date field to both invoice types"
echo "  ✓ Updated forms with automatic due_date calculation"
echo "  ✓ Updated tables to display Payment Terms"
echo ""
echo "Next steps:"
echo "  1. Test creating a new Purchase Invoice with Payment Terms"
echo "  2. Test creating a new Sales Invoice with Payment Terms"
echo "  3. Configure existing Payment Terms with calculation_base"
echo "  4. Update Payment Term Stages to use 'invoice_date' or 'shipment_date'"
echo ""
print_warning "Important: Review the documentation files:"
echo "  - PAYMENT_TERMS_IMPLEMENTATION.md"
echo "  - PAYMENT_TERMS_SHIPMENT_DATE_UPDATE.md"
echo ""
print_success "Deployment completed at $(date)"
echo "=========================================="

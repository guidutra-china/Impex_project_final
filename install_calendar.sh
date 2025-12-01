#!/bin/bash

echo "📅 Installing FullCalendar by Saade..."
echo "======================================"
echo ""

# Install the package
echo "1. Installing composer package..."
composer require saade/filament-fullcalendar:"^3.0"

echo ""
echo "2. Publishing assets..."
php artisan filament:assets

echo ""
echo "✅ FullCalendar package installed!"
echo ""
echo "Next steps:"
echo "- Create Event model and migration"
echo "- Create Calendar resource"
echo "- Configure event types"
echo "- Add automatic event creation"

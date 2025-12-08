#!/bin/bash

# Translation Script for Pages
set -e

BASE_DIR="/home/ubuntu/Impex_project_final"
cd "$BASE_DIR"

translate_file() {
    local file="$1"
    local backup="${file}.bak"
    
    cp "$file" "$backup"
    
    # Common actions
    sed -i "s/->label('Save')/->label(__('common.save'))/g" "$file"
    sed -i "s/->label('Save Settings')/->label(__('common.save'))/g" "$file"
    sed -i "s/->label('Edit')/->label(__('common.edit'))/g" "$file"
    sed -i "s/->label('Edit Product')/->label(__('common.edit'))/g" "$file"
    sed -i "s/->label('Delete')/->label(__('common.delete'))/g" "$file"
    sed -i "s/->label('Create')/->label(__('common.create'))/g" "$file"
    sed -i "s/->label('Cancel')/->label(__('common.cancel'))/g" "$file"
    sed -i "s/->label('Confirm')/->label(__('common.confirm'))/g" "$file"
    sed -i "s/->label('Submit')/->label(__('common.submit'))/g" "$file"
    sed -i "s/->label('Export')/->label(__('common.export'))/g" "$file"
    sed -i "s/->label('Import')/->label(__('common.import'))/g" "$file"
    sed -i "s/->label('Import from Excel')/->label(__('common.import'))/g" "$file"
    sed -i "s/->label('Download')/->label(__('common.download'))/g" "$file"
    sed -i "s/->label('Upload')/->label(__('common.upload'))/g" "$file"
    sed -i "s/->label('View')/->label(__('common.view'))/g" "$file"
    sed -i "s/->label('Print')/->label(__('common.print'))/g" "$file"
    sed -i "s/->label('Refresh')/->label(__('common.refresh'))/g" "$file"
    sed -i "s/->label('Approve')/->label(__('common.approved'))/g" "$file"
    sed -i "s/->label('Reject')/->label(__('common.rejected'))/g" "$file"
    
    # Status
    sed -i "s/->label('Status')/->label(__('fields.status'))/g" "$file"
    sed -i "s/->label('Type')/->label(__('fields.type'))/g" "$file"
    sed -i "s/->label('Active')/->label(__('common.active'))/g" "$file"
    sed -i "s/->label('Pending')/->label(__('common.pending'))/g" "$file"
    sed -i "s/->label('Completed')/->label(__('common.completed'))/g" "$file"
    
    # Basic fields
    sed -i "s/->label('Name')/->label(__('fields.name'))/g" "$file"
    sed -i "s/->label('Description')/->label(__('fields.description'))/g" "$file"
    sed -i "s/->label('Notes')/->label(__('fields.notes'))/g" "$file"
    sed -i "s/->label('Internal Notes')/->label(__('fields.notes'))/g" "$file"
    sed -i "s/->label('Additional Notes')/->label(__('fields.notes'))/g" "$file"
    sed -i "s/->label('Date')/->label(__('fields.date'))/g" "$file"
    sed -i "s/->label('Created')/->label(__('fields.created_at'))/g" "$file"
    sed -i "s/->label('Updated')/->label(__('fields.updated_at'))/g" "$file"
    sed -i "s/->label('Deleted')/->label(__('fields.deleted_at'))/g" "$file"
    
    # Financial fields
    sed -i "s/->label('Amount')/->label(__('fields.amount'))/g" "$file"
    sed -i "s/->label('Currency')/->label(__('fields.currency'))/g" "$file"
    sed -i "s/->label('Total')/->label(__('fields.total'))/g" "$file"
    sed -i "s/->label('Exchange Rate')/->label(__('fields.exchange_rate'))/g" "$file"
    sed -i "s/->label('Payment Terms')/->label(__('fields.payment_terms'))/g" "$file"
    sed -i "s/->label('Payment Method')/->label(__('fields.payment_method'))/g" "$file"
    sed -i "s/->label('Payment Reference')/->label(__('fields.payment_reference'))/g" "$file"
    
    # Shipment specific
    sed -i "s/->label('Shipment #')/->label(__('fields.shipment_number'))/g" "$file"
    sed -i "s/->label('Tracking Number')/->label(__('fields.tracking_number'))/g" "$file"
    sed -i "s/->label('Carrier')/->label(__('fields.carrier'))/g" "$file"
    sed -i "s/->label('Shipping Method')/->label(__('fields.shipping_method'))/g" "$file"
    sed -i "s/->label('Shipping Cost')/->label(__('fields.freight_cost'))/g" "$file"
    sed -i "s/->label('Insurance Cost')/->label(__('fields.insurance_cost'))/g" "$file"
    sed -i "s/->label('Origin Address')/->label(__('fields.origin_address'))/g" "$file"
    sed -i "s/->label('Destination Address')/->label(__('fields.destination_address'))/g" "$file"
    
    # Quantity/Weight/Volume
    sed -i "s/->label('Quantity')/->label(__('fields.quantity'))/g" "$file"
    sed -i "s/->label('Total Quantity')/->label(__('fields.quantity'))/g" "$file"
    sed -i "s/->label('Weight')/->label(__('fields.weight'))/g" "$file"
    sed -i "s/->label('Total Weight')/->label(__('fields.weight'))/g" "$file"
    sed -i "s/->label('Volume')/->label(__('fields.volume'))/g" "$file"
    sed -i "s/->label('Total Volume')/->label(__('fields.volume'))/g" "$file"
    
    # Date fields
    sed -i "s/->label('Due Date')/->label(__('fields.due_date'))/g" "$file"
    sed -i "s/->label('Shipment Date')/->label(__('fields.shipment_date'))/g" "$file"
    sed -i "s/->label('Transaction Date')/->label(__('fields.date'))/g" "$file"
    
    # Category/File
    sed -i "s/->label('Category')/->label(__('fields.category'))/g" "$file"
    sed -i "s/->label('Expense Category')/->label(__('fields.category'))/g" "$file"
    sed -i "s/->label('File')/->label(__('fields.file'))/g" "$file"
    sed -i "s/->label('Excel File')/->label(__('fields.file'))/g" "$file"
    
    if ! diff -q "$file" "$backup" > /dev/null 2>&1; then
        echo "✓ Translated: $file"
        rm "$backup"
        return 0
    else
        mv "$backup" "$file"
        return 1
    fi
}

echo "Processing Page files..."
modified=0
total=0

for file in app/Filament/Resources/**/Pages/*.php; do
    if [ -f "$file" ]; then
        total=$((total + 1))
        if translate_file "$file"; then
            modified=$((modified + 1))
        fi
    fi
done

echo ""
echo "========================================"
echo "SUMMARY"
echo "========================================"
echo "Files processed: $total"
echo "Files modified: $modified"
echo "========================================"

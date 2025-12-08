#!/bin/bash

# Translation Script for RelationManagers
set -e

BASE_DIR="/home/ubuntu/Impex_project_final"
cd "$BASE_DIR"

translate_file() {
    local file="$1"
    local backup="${file}.bak"
    
    cp "$file" "$backup"
    
    # Basic fields
    sed -i "s/->label('Name')/->label(__('fields.name'))/g" "$file"
    sed -i "s/->label('Code')/->label(__('fields.code'))/g" "$file"
    sed -i "s/->label('SKU')/->label(__('fields.code'))/g" "$file"
    sed -i "s/->label('Type')/->label(__('fields.type'))/g" "$file"
    sed -i "s/->label('Status')/->label(__('fields.status'))/g" "$file"
    sed -i "s/->label('Description')/->label(__('fields.description'))/g" "$file"
    sed -i "s/->label('Notes')/->label(__('fields.notes'))/g" "$file"
    sed -i "s/->label('Date')/->label(__('fields.date'))/g" "$file"
    sed -i "s/->label('Created')/->label(__('fields.created_at'))/g" "$file"
    sed -i "s/->label('Order')/->label(__('fields.sort_order'))/g" "$file"
    sed -i "s/->label('Sort Order')/->label(__('fields.sort_order'))/g" "$file"
    
    # Product/Item fields
    sed -i "s/->label('Product')/->label(__('fields.product'))/g" "$file"
    sed -i "s/->label('Product Name')/->label(__('fields.product_name'))/g" "$file"
    sed -i "s/->label('Quantity')/->label(__('fields.quantity'))/g" "$file"
    sed -i "s/->label('Qty')/->label(__('fields.qty'))/g" "$file"
    sed -i "s/->label('Unit Price')/->label(__('fields.unit_price'))/g" "$file"
    sed -i "s/->label('Price')/->label(__('fields.price'))/g" "$file"
    sed -i "s/->label('Total')/->label(__('fields.total'))/g" "$file"
    sed -i "s/->label('Total Price')/->label(__('fields.total'))/g" "$file"
    sed -i "s/->label('HS Code')/->label(__('fields.hs_code'))/g" "$file"
    sed -i "s/->label('Weight')/->label(__('fields.weight'))/g" "$file"
    sed -i "s/->label('Volume')/->label(__('fields.volume'))/g" "$file"
    sed -i "s/->label('Unit Weight')/->label(__('fields.weight'))/g" "$file"
    sed -i "s/->label('Unit Volume')/->label(__('fields.volume'))/g" "$file"
    sed -i "s/->label('Total Weight')/->label(__('fields.weight'))/g" "$file"
    sed -i "s/->label('Total Volume')/->label(__('fields.volume'))/g" "$file"
    
    # Customer/Supplier fields
    sed -i "s/->label('Customer')/->label(__('fields.customer'))/g" "$file"
    sed -i "s/->label('Supplier')/->label(__('fields.supplier'))/g" "$file"
    sed -i "s/->label('Supplier Name')/->label(__('fields.supplier_name'))/g" "$file"
    
    # Financial fields
    sed -i "s/->label('Amount')/->label(__('fields.amount'))/g" "$file"
    sed -i "s/->label('Currency')/->label(__('fields.currency'))/g" "$file"
    sed -i "s/->label('Exchange Rate')/->label(__('fields.exchange_rate'))/g" "$file"
    sed -i "s/->label('Customs Value')/->label(__('fields.customs_value'))/g" "$file"
    
    # Document/File fields
    sed -i "s/->label('Filename')/->label(__('fields.file'))/g" "$file"
    sed -i "s/->label('Photo')/->label(__('fields.file'))/g" "$file"
    sed -i "s/->label('Document')/->label(__('fields.document'))/g" "$file"
    sed -i "s/->label('Upload Date')/->label(__('fields.created_at'))/g" "$file"
    sed -i "s/->label('Size')/->label(__('fields.size'))/g" "$file"
    
    # Shipment specific
    sed -i "s/->label('Container')/->label(__('fields.container_numbers'))/g" "$file"
    sed -i "s/->label('Seal')/->label(__('fields.seal_number'))/g" "$file"
    
    # Common actions
    sed -i "s/->label('View')/->label(__('common.view'))/g" "$file"
    sed -i "s/->label('Edit')/->label(__('common.edit'))/g" "$file"
    sed -i "s/->label('Delete')/->label(__('common.delete'))/g" "$file"
    sed -i "s/->label('Download')/->label(__('common.download'))/g" "$file"
    sed -i "s/->label('Upload')/->label(__('common.upload'))/g" "$file"
    sed -i "s/->label('Export')/->label(__('common.export'))/g" "$file"
    sed -i "s/->label('Import')/->label(__('common.import'))/g" "$file"
    sed -i "s/->label('Add')/->label(__('common.add'))/g" "$file"
    sed -i "s/->label('Create')/->label(__('common.create'))/g" "$file"
    sed -i "s/->label('Save')/->label(__('common.save'))/g" "$file"
    sed -i "s/->label('Cancel')/->label(__('common.cancel'))/g" "$file"
    
    if ! diff -q "$file" "$backup" > /dev/null 2>&1; then
        echo "✓ Translated: $file"
        rm "$backup"
        return 0
    else
        mv "$backup" "$file"
        return 1
    fi
}

echo "Processing RelationManager files..."
modified=0
total=0

for file in app/Filament/Resources/**/RelationManagers/*.php; do
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

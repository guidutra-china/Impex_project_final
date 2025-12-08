#!/bin/bash

# Safe Translation Script
# Translates hardcoded labels to __() calls one file at a time

set -e  # Exit on error

BASE_DIR="/home/ubuntu/Impex_project_final"
cd "$BASE_DIR"

# Function to translate a single file
translate_file() {
    local file="$1"
    local backup="${file}.bak"
    
    # Create backup
    cp "$file" "$backup"
    
    # Apply translations using sed
    sed -i "s/->label('Name')/->label(__('fields.name'))/g" "$file"
    sed -i "s/->label('Email')/->label(__('fields.email'))/g" "$file"
    sed -i "s/->label('Phone')/->label(__('fields.phone'))/g" "$file"
    sed -i "s/->label('Address')/->label(__('fields.address'))/g" "$file"
    sed -i "s/->label('Status')/->label(__('fields.status'))/g" "$file"
    sed -i "s/->label('Type')/->label(__('fields.type'))/g" "$file"
    sed -i "s/->label('Code')/->label(__('fields.code'))/g" "$file"
    sed -i "s/->label('Description')/->label(__('fields.description'))/g" "$file"
    sed -i "s/->label('Notes')/->label(__('fields.notes'))/g" "$file"
    sed -i "s/->label('Created')/->label(__('fields.created_at'))/g" "$file"
    sed -i "s/->label('Updated')/->label(__('fields.updated_at'))/g" "$file"
    sed -i "s/->label('Date')/->label(__('fields.date'))/g" "$file"
    sed -i "s/->label('Amount')/->label(__('fields.amount'))/g" "$file"
    sed -i "s/->label('Total')/->label(__('fields.total'))/g" "$file"
    sed -i "s/->label('Subtotal')/->label(__('fields.subtotal'))/g" "$file"
    sed -i "s/->label('Currency')/->label(__('fields.currency'))/g" "$file"
    sed -i "s/->label('Quantity')/->label(__('fields.quantity'))/g" "$file"
    sed -i "s/->label('Qty')/->label(__('fields.qty'))/g" "$file"
    sed -i "s/->label('Price')/->label(__('fields.price'))/g" "$file"
    sed -i "s/->label('Unit Price')/->label(__('fields.unit_price'))/g" "$file"
    sed -i "s/->label('Customer')/->label(__('fields.customer'))/g" "$file"
    sed -i "s/->label('Supplier')/->label(__('fields.supplier'))/g" "$file"
    sed -i "s/->label('Product')/->label(__('fields.product'))/g" "$file"
    sed -i "s/->label('Order')/->label(__('fields.order'))/g" "$file"
    sed -i "s/->label('Invoice')/->label(__('fields.invoice'))/g" "$file"
    sed -i "s/->label('Shipment')/->label(__('fields.shipment'))/g" "$file"
    
    # Product specific
    sed -i "s/->label('Product Name')/->label(__('fields.product_name'))/g" "$file"
    sed -i "s/->label('Product Code')/->label(__('fields.product_code'))/g" "$file"
    sed -i "s/->label('SKU')/->label(__('fields.code'))/g" "$file"
    sed -i "s/->label('Supplier Code')/->label(__('fields.supplier_code'))/g" "$file"
    sed -i "s/->label('Customer Code')/->label(__('fields.customer_code'))/g" "$file"
    sed -i "s/->label('HS Code')/->label(__('fields.hs_code'))/g" "$file"
    sed -i "s/->label('MOQ')/->label(__('fields.moq'))/g" "$file"
    sed -i "s/->label('Lead Time')/->label(__('fields.lead_time'))/g" "$file"
    sed -i "s/->label('Net Weight')/->label(__('fields.net_weight'))/g" "$file"
    sed -i "s/->label('Gross Weight')/->label(__('fields.gross_weight'))/g" "$file"
    sed -i "s/->label('Origin')/->label(__('fields.country_of_origin'))/g" "$file"
    sed -i "s/->label('Model')/->label(__('fields.model'))/g" "$file"
    
    # Shipment specific
    sed -i "s/->label('Tracking #')/->label(__('fields.tracking_number'))/g" "$file"
    sed -i "s/->label('Carrier')/->label(__('fields.carrier'))/g" "$file"
    sed -i "s/->label('Method')/->label(__('fields.shipping_method'))/g" "$file"
    sed -i "s/->label('Weight (kg)')/->label(__('fields.weight'))/g" "$file"
    sed -i "s/->label('Volume (m³)')/->label(__('fields.volume'))/g" "$file"
    
    # Invoice/Order specific
    sed -i "s/->label('Invoice Number')/->label(__('fields.invoice_number'))/g" "$file"
    sed -i "s/->label('Invoice Date')/->label(__('fields.invoice_date'))/g" "$file"
    sed -i "s/->label('Due Date')/->label(__('fields.due_date'))/g" "$file"
    sed -i "s/->label('Order Number')/->label(__('fields.order_number'))/g" "$file"
    sed -i "s/->label('PO Number')/->label(__('fields.po_number'))/g" "$file"
    sed -i "s/->label('Payment Terms')/->label(__('fields.payment_terms'))/g" "$file"
    
    # Bank specific
    sed -i "s/->label('Bank Name')/->label(__('fields.bank_name'))/g" "$file"
    sed -i "s/->label('Account Number')/->label(__('fields.account_number'))/g" "$file"
    sed -i "s/->label('Account Name')/->label(__('fields.account_name'))/g" "$file"
    sed -i "s/->label('SWIFT Code')/->label(__('fields.swift_code'))/g" "$file"
    sed -i "s/->label('SWIFT')/->label(__('fields.swift_code'))/g" "$file"
    
    # Common sections
    sed -i "s/->label('Active')/->label(__('common.active'))/g" "$file"
    sed -i "s/->label('Inactive')/->label(__('common.inactive'))/g" "$file"
    sed -i "s/->label('Pending')/->label(__('common.pending'))/g" "$file"
    sed -i "s/->label('Approved')/->label(__('common.approved'))/g" "$file"
    sed -i "s/->label('Completed')/->label(__('common.completed'))/g" "$file"
    sed -i "s/->label('Cancelled')/->label(__('common.cancelled'))/g" "$file"
    sed -i "s/->label('Draft')/->label(__('common.draft'))/g" "$file"
    
    # Check if file was modified
    if ! diff -q "$file" "$backup" > /dev/null 2>&1; then
        echo "✓ Translated: $file"
        rm "$backup"
        return 0
    else
        # No changes, restore backup
        mv "$backup" "$file"
        return 1
    fi
}

# Process all Table files
echo "Processing Table files..."
modified=0
total=0

for file in app/Filament/Resources/**/Tables/*.php; do
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

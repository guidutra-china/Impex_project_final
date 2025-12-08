#!/bin/bash

# Translation Script for Schemas (Forms)
set -e

BASE_DIR="/home/ubuntu/Impex_project_final"
cd "$BASE_DIR"

translate_file() {
    local file="$1"
    local backup="${file}.bak"
    
    cp "$file" "$backup"
    
    # Basic fields
    sed -i "s/->label('Name')/->label(__('fields.name'))/g" "$file"
    sed -i "s/->label('Email')/->label(__('fields.email'))/g" "$file"
    sed -i "s/->label('Email Address')/->label(__('fields.email'))/g" "$file"
    sed -i "s/->label('Phone')/->label(__('fields.phone'))/g" "$file"
    sed -i "s/->label('Phone Number')/->label(__('fields.phone'))/g" "$file"
    sed -i "s/->label('Address')/->label(__('fields.address'))/g" "$file"
    sed -i "s/->label('Street Address')/->label(__('fields.address'))/g" "$file"
    sed -i "s/->label('City')/->label(__('fields.city'))/g" "$file"
    sed -i "s/->label('Country')/->label(__('fields.country'))/g" "$file"
    sed -i "s/->label('State')/->label(__('fields.state'))/g" "$file"
    sed -i "s/->label('State\/Province')/->label(__('fields.state'))/g" "$file"
    sed -i "s/->label('ZIP Code')/->label(__('fields.zip'))/g" "$file"
    sed -i "s/->label('ZIP\/Postal Code')/->label(__('fields.zip'))/g" "$file"
    sed -i "s/->label('Tax ID')/->label(__('fields.tax_id'))/g" "$file"
    sed -i "s/->label('Tax ID \/ VAT Number')/->label(__('fields.tax_id'))/g" "$file"
    sed -i "s/->label('Code')/->label(__('fields.code'))/g" "$file"
    sed -i "s/->label('Status')/->label(__('fields.status'))/g" "$file"
    sed -i "s/->label('Account Status')/->label(__('fields.status'))/g" "$file"
    sed -i "s/->label('Notes')/->label(__('fields.notes'))/g" "$file"
    sed -i "s/->label('Internal Notes')/->label(__('fields.notes'))/g" "$file"
    sed -i "s/->label('Description')/->label(__('fields.description'))/g" "$file"
    sed -i "s/->label('Type')/->label(__('fields.type'))/g" "$file"
    sed -i "s/->label('Website')/->label(__('fields.website'))/g" "$file"
    sed -i "s/->label('Created')/->label(__('fields.created_at'))/g" "$file"
    sed -i "s/->label('Last Modified')/->label(__('fields.updated_at'))/g" "$file"
    sed -i "s/->label('Last modified at')/->label(__('fields.updated_at'))/g" "$file"
    
    # Product specific
    sed -i "s/->label('Product Name')/->label(__('fields.product_name'))/g" "$file"
    sed -i "s/->label('Product Code')/->label(__('fields.product_code'))/g" "$file"
    sed -i "s/->label('Supplier Product Code')/->label(__('fields.supplier_code'))/g" "$file"
    sed -i "s/->label('Customer Product Code')/->label(__('fields.customer_code'))/g" "$file"
    sed -i "s/->label('HS Code')/->label(__('fields.hs_code'))/g" "$file"
    sed -i "s/->label('Unit Price')/->label(__('fields.unit_price'))/g" "$file"
    sed -i "s/->label('Current Price')/->label(__('fields.price'))/g" "$file"
    sed -i "s/->label('Model')/->label(__('fields.model'))/g" "$file"
    sed -i "s/->label('Model Number')/->label(__('fields.model'))/g" "$file"
    sed -i "s/->label('Brand')/->label(__('fields.brand'))/g" "$file"
    sed -i "s/->label('Category')/->label(__('fields.category'))/g" "$file"
    sed -i "s/->label('Family')/->label(__('fields.category'))/g" "$file"
    sed -i "s/->label('MOQ')/->label(__('fields.moq'))/g" "$file"
    sed -i "s/->label('Lead Time')/->label(__('fields.lead_time'))/g" "$file"
    
    # Dimensions and weights
    sed -i "s/->label('Length')/->label(__('fields.length'))/g" "$file"
    sed -i "s/->label('Width')/->label(__('fields.width'))/g" "$file"
    sed -i "s/->label('Height')/->label(__('fields.height'))/g" "$file"
    sed -i "s/->label('Weight')/->label(__('fields.weight'))/g" "$file"
    sed -i "s/->label('Net Weight')/->label(__('fields.net_weight'))/g" "$file"
    sed -i "s/->label('Gross Weight')/->label(__('fields.gross_weight'))/g" "$file"
    sed -i "s/->label('Volume')/->label(__('fields.volume'))/g" "$file"
    sed -i "s/->label('CBM')/->label(__('fields.cbm'))/g" "$file"
    
    # Financial fields
    sed -i "s/->label('Price')/->label(__('fields.price'))/g" "$file"
    sed -i "s/->label('Total')/->label(__('fields.total'))/g" "$file"
    sed -i "s/->label('Subtotal')/->label(__('fields.subtotal'))/g" "$file"
    sed -i "s/->label('Currency')/->label(__('fields.currency'))/g" "$file"
    sed -i "s/->label('Amount')/->label(__('fields.amount'))/g" "$file"
    sed -i "s/->label('Quantity')/->label(__('fields.quantity'))/g" "$file"
    sed -i "s/->label('Qty')/->label(__('fields.qty'))/g" "$file"
    sed -i "s/->label('Discount')/->label(__('fields.discount'))/g" "$file"
    sed -i "s/->label('Tax')/->label(__('fields.tax'))/g" "$file"
    sed -i "s/->label('Exchange Rate')/->label(__('fields.exchange_rate'))/g" "$file"
    sed -i "s/->label('Payment Terms')/->label(__('fields.payment_terms'))/g" "$file"
    
    # Invoice/Order specific
    sed -i "s/->label('Invoice Number')/->label(__('fields.invoice_number'))/g" "$file"
    sed -i "s/->label('Invoice Date')/->label(__('fields.invoice_date'))/g" "$file"
    sed -i "s/->label('Issue Date')/->label(__('fields.invoice_date'))/g" "$file"
    sed -i "s/->label('Due Date')/->label(__('fields.due_date'))/g" "$file"
    sed -i "s/->label('Order Number')/->label(__('fields.order_number'))/g" "$file"
    sed -i "s/->label('PO Number')/->label(__('fields.po_number'))/g" "$file"
    sed -i "s/->label('Quote Number')/->label(__('fields.quote_number'))/g" "$file"
    
    # Bank specific
    sed -i "s/->label('Bank Name')/->label(__('fields.bank_name'))/g" "$file"
    sed -i "s/->label('Bank Account')/->label(__('fields.bank_name'))/g" "$file"
    sed -i "s/->label('Account Number')/->label(__('fields.account_number'))/g" "$file"
    sed -i "s/->label('Account Name')/->label(__('fields.account_name'))/g" "$file"
    sed -i "s/->label('SWIFT Code')/->label(__('fields.swift_code'))/g" "$file"
    sed -i "s/->label('SWIFT\/BIC Code')/->label(__('fields.swift_code'))/g" "$file"
    sed -i "s/->label('Routing Number')/->label(__('fields.routing_number'))/g" "$file"
    
    # Customer/Supplier fields
    sed -i "s/->label('Customer')/->label(__('fields.customer'))/g" "$file"
    sed -i "s/->label('Supplier')/->label(__('fields.supplier'))/g" "$file"
    
    # Date fields
    sed -i "s/->label('Date')/->label(__('fields.date'))/g" "$file"
    sed -i "s/->label('Start Date')/->label(__('fields.start_date'))/g" "$file"
    sed -i "s/->label('End Date')/->label(__('fields.end_date'))/g" "$file"
    sed -i "s/->label('Delivery Date')/->label(__('fields.delivery_date'))/g" "$file"
    sed -i "s/->label('Expected Delivery')/->label(__('fields.delivery_date'))/g" "$file"
    sed -i "s/->label('Actual Delivery')/->label(__('fields.delivery_date'))/g" "$file"
    
    # Shipment specific
    sed -i "s/->label('Tracking Number')/->label(__('fields.tracking_number'))/g" "$file"
    sed -i "s/->label('Carrier')/->label(__('fields.carrier'))/g" "$file"
    sed -i "s/->label('Shipping Method')/->label(__('fields.shipping_method'))/g" "$file"
    sed -i "s/->label('Shipping Cost')/->label(__('fields.freight_cost'))/g" "$file"
    
    # Common sections
    sed -i "s/->label('Active')/->label(__('common.active'))/g" "$file"
    sed -i "s/->label('Tags')/->label(__('fields.tags'))/g" "$file"
    
    if ! diff -q "$file" "$backup" > /dev/null 2>&1; then
        echo "✓ Translated: $file"
        rm "$backup"
        return 0
    else
        mv "$backup" "$file"
        return 1
    fi
}

echo "Processing Schema files..."
modified=0
total=0

for file in app/Filament/Resources/**/Schemas/*.php; do
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

# PDF Export - Installation Instructions

## 📦 Installation Steps

### 1. Install the PDF Library

On your **production server** (with PHP 8.2+), run:

```bash
cd /path/to/Impex_project_final
composer require barryvdh/laravel-dompdf
```

### 2. Publish Configuration (Optional)

If you want to customize PDF settings:

```bash
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
```

This will create `config/dompdf.php` where you can adjust:
- Paper size (A4, Letter, etc.)
- Orientation (portrait, landscape)
- DPI settings
- Font directory

### 3. Run Migrations

```bash
php artisan migrate
```

### 4. Clear Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### 5. Test PDF Generation

1. Go to Purchase Invoices or Sales Invoices
2. Click the **"Download PDF"** button on any invoice
3. PDF should download with filename: `PI-2025-0001-Rev1.pdf`

---

## 🎨 Customization

### Update Company Information

Edit the templates to add your company details:

**For Purchase Invoices:**
`resources/views/pdf/invoices/purchase-invoice.blade.php`

**For Sales Invoices:**
`resources/views/pdf/invoices/sales-invoice.blade.php`

Look for these sections and update:

```html
<div class="company-name">Your Company Name</div>
<div>123 Business Street</div>
<div>City, State 12345</div>
<div>Phone: (123) 456-7890</div>
<div>Email: info@yourcompany.com</div>
```

### Add Company Logo

1. Place your logo in `public/images/logo.png`

2. Add this code in the header section of the template:

```html
<div class="company-info">
    <img src="{{ public_path('images/logo.png') }}" alt="Logo" style="max-width: 150px; margin-bottom: 10px;">
    <div class="company-name">Your Company Name</div>
    ...
</div>
```

### Change Colors

The templates use these main colors:
- **Purchase Invoice**: Blue (`#2563eb`)
- **Sales Invoice**: Green (`#10b981`)

Search and replace these hex codes in the templates to change the color scheme.

---

## 📋 Features Included

### ✅ Professional Layout
- Clean, modern design
- Responsive tables
- Clear typography
- Organized sections

### ✅ Status Watermarks
- **DRAFT** - Gray watermark
- **CANCELLED** - Gray watermark with strikethrough
- **SUPERSEDED** - Yellow watermark
- Automatically applied based on invoice status

### ✅ Complete Information
- Company and client/supplier details
- Invoice dates (invoice, shipment, due)
- Payment terms
- Purchase order references
- All items with quantities, prices, taxes
- Subtotals and grand total
- Notes and terms & conditions
- Revision information

### ✅ Status Badges
- Color-coded badges for each status
- Positioned next to invoice number
- Easy visual identification

### ✅ Revision Tracking
- Shows revision number
- Displays revision reason in footer
- Links to original invoice

---

## 🔧 Troubleshooting

### Issue: "Class 'PDF' not found"

**Solution:** Make sure you installed the package:
```bash
composer require barryvdh/laravel-dompdf
```

### Issue: Fonts not rendering correctly

**Solution:** DomPDF uses DejaVu Sans by default. If you need custom fonts:

1. Add fonts to `storage/fonts/`
2. Update `config/dompdf.php`:
```php
'font_dir' => storage_path('fonts/'),
```

### Issue: Images not showing in PDF

**Solution:** Use `public_path()` for image paths:
```php
<img src="{{ public_path('images/logo.png') }}" alt="Logo">
```

### Issue: PDF generation is slow

**Solution:** 
1. Enable caching in `config/dompdf.php`:
```php
'enable_font_subsetting' => false,
'enable_html5_parser' => false,
```

2. Consider queuing PDF generation for large invoices:
```php
dispatch(new GenerateInvoicePdf($invoice));
```

---

## 🚀 Next Steps

### Optional Enhancements

1. **Email PDF Automatically**
   - Add "Send Invoice" action
   - Attach PDF to email
   - Track sent emails

2. **Bulk PDF Export**
   - Add bulk action to export multiple invoices
   - Generate ZIP file with all PDFs

3. **PDF Storage**
   - Save PDFs to storage when invoice is finalized
   - Serve from storage instead of generating on-demand
   - Faster downloads, consistent output

4. **QR Code for Payment**
   - Add QR code with payment information
   - Use `simplesoftwareio/simple-qrcode` package

5. **Multi-language Support**
   - Translate templates
   - Use Laravel localization
   - Select language per client

---

## 📞 Support

If you encounter any issues:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Verify package installation: `composer show barryvdh/laravel-dompdf`
3. Test with a simple invoice first
4. Check file permissions on storage directories

---

## ✅ Checklist

- [ ] Install barryvdh/laravel-dompdf package
- [ ] Clear all caches
- [ ] Test PDF download on Purchase Invoice
- [ ] Test PDF download on Sales Invoice
- [ ] Update company information in templates
- [ ] Add company logo (optional)
- [ ] Customize colors (optional)
- [ ] Test all invoice statuses (draft, sent, paid, etc.)
- [ ] Verify revision information displays correctly
- [ ] Check PDF output on different browsers

---

**Ready to use!** 🎉

The PDF export functionality is now fully implemented and ready for production use.

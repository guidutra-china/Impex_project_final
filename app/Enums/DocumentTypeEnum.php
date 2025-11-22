<?php

namespace App\Enums;

enum DocumentTypeEnum: string
{
    case COMMERCIAL_INVOICE = 'commercial_invoice';
    case PROFORMA_INVOICE = 'proforma_invoice';
    case PACKING_LIST = 'packing_list';
    case BILL_OF_LADING = 'bill_of_lading';
    case CERTIFICATE_OF_ORIGIN = 'certificate_of_origin';
    case QUALITY_CERTIFICATE = 'quality_certificate';
    case INSURANCE_CERTIFICATE = 'insurance_certificate';
    case CUSTOMS_DECLARATION = 'customs_declaration';
    case CONTRACT = 'contract';
    case PURCHASE_ORDER = 'purchase_order';
    case SALES_ORDER = 'sales_order';
    case OTHER = 'other';

    public function label(): string
    {
        return match($this) {
            self::COMMERCIAL_INVOICE => 'Commercial Invoice',
            self::PROFORMA_INVOICE => 'Proforma Invoice',
            self::PACKING_LIST => 'Packing List',
            self::BILL_OF_LADING => 'Bill of Lading',
            self::CERTIFICATE_OF_ORIGIN => 'Certificate of Origin',
            self::QUALITY_CERTIFICATE => 'Quality Certificate',
            self::INSURANCE_CERTIFICATE => 'Insurance Certificate',
            self::CUSTOMS_DECLARATION => 'Customs Declaration',
            self::CONTRACT => 'Contract',
            self::PURCHASE_ORDER => 'Purchase Order',
            self::SALES_ORDER => 'Sales Order',
            self::OTHER => 'Other',
        };
    }
}
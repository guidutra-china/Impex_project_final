<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\GeneratedDocument;
use App\Services\Export\PdfExportService;

class PackingListPdfService
{
    public function __construct(
        protected PdfExportService $pdfExportService
    ) {}

    /**
     * Generate Packing List PDF from Shipment
     */
    public function generate(Shipment $shipment): string
    {
        $documentType = 'packing_list';
        
        // Calculate next revision number
        $revisionNumber = $this->getNextRevisionNumber($shipment, $documentType);
        
        $generatedDocument = $this->pdfExportService->generate(
            model: $shipment,
            documentType: $documentType,
            view: 'pdfs.packing-list',
            data: [
                'shipment' => $shipment->load([
                    'customer',
                    'containers.items.product',
                    'packingList',
                ]),
                'companySettings' => \App\Models\CompanySetting::first(),
            ],
            options: [
                'format' => 'A4',
                'orientation' => 'portrait',
            ],
            revisionNumber: $revisionNumber
        );

        return $generatedDocument->file_path;
    }

    /**
     * Get next revision number for this document type
     */
    protected function getNextRevisionNumber(Shipment $shipment, string $documentType): int
    {
        $lastDocument = GeneratedDocument::query()
            ->where('documentable_type', Shipment::class)
            ->where('documentable_id', $shipment->id)
            ->where('document_type', $documentType)
            ->orderByDesc('revision_number')
            ->first();

        return $lastDocument ? $lastDocument->revision_number + 1 : 1;
    }
}

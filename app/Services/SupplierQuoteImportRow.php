<?php

namespace App\Services;

use App\Exceptions\InvalidRowException;

class SupplierQuoteImportRow
{
    public function __construct(
        public readonly string $productName,
        public readonly int $quantity,
        public readonly ?int $targetPrice,
        public readonly ?int $supplierPrice,
        public readonly int $rowNumber
    ) {
        $this->validate();
    }

    /**
     * Validate row data
     *
     * @throws InvalidRowException
     */
    private function validate(): void
    {
        if (empty($this->productName)) {
            throw new InvalidRowException(
                "Row {$this->rowNumber}: Product name is required",
                $this->toArray()
            );
        }

        if (!is_int($this->quantity) || $this->quantity <= 0) {
            throw new InvalidRowException(
                "Row {$this->rowNumber}: Quantity must be a positive integer",
                $this->toArray()
            );
        }

        if ($this->quantity > SupplierQuoteImportConfig::MAX_QUANTITY) {
            throw new InvalidRowException(
                "Row {$this->rowNumber}: Quantity exceeds maximum allowed (" . SupplierQuoteImportConfig::MAX_QUANTITY . ")",
                $this->toArray()
            );
        }

        if ($this->supplierPrice !== null && $this->supplierPrice < 0) {
            throw new InvalidRowException(
                "Row {$this->rowNumber}: Supplier price cannot be negative",
                $this->toArray()
            );
        }

        if ($this->supplierPrice !== null && $this->supplierPrice > SupplierQuoteImportConfig::MAX_PRICE) {
            throw new InvalidRowException(
                "Row {$this->rowNumber}: Supplier price too large",
                $this->toArray()
            );
        }
    }

    /**
     * Convert to array for logging
     */
    public function toArray(): array
    {
        return [
            'product_name' => $this->productName,
            'quantity' => $this->quantity,
            'target_price' => $this->targetPrice,
            'supplier_price' => $this->supplierPrice,
            'row_number' => $this->rowNumber,
        ];
    }
}

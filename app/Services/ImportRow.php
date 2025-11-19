<?php

namespace App\Services;

use App\Exceptions\InvalidRowException;

class ImportRow
{
    public function __construct(
        public readonly string $productName,
        public readonly int $quantity,
        public readonly ?int $targetPrice,
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

        if (strlen($this->productName) > RFQImportConfig::MAX_PRODUCT_NAME_LENGTH) {
            throw new InvalidRowException(
                "Row {$this->rowNumber}: Product name too long (max " . RFQImportConfig::MAX_PRODUCT_NAME_LENGTH . " characters)",
                $this->toArray()
            );
        }

        if ($this->quantity <= 0) {
            throw new InvalidRowException(
                "Row {$this->rowNumber}: Quantity must be positive",
                $this->toArray()
            );
        }

        if ($this->quantity > RFQImportConfig::MAX_QUANTITY) {
            throw new InvalidRowException(
                "Row {$this->rowNumber}: Quantity too large (max " . number_format(RFQImportConfig::MAX_QUANTITY) . ")",
                $this->toArray()
            );
        }

        if ($this->targetPrice !== null && $this->targetPrice < 0) {
            throw new InvalidRowException(
                "Row {$this->rowNumber}: Target price cannot be negative",
                $this->toArray()
            );
        }

        if ($this->targetPrice !== null && $this->targetPrice > RFQImportConfig::MAX_PRICE) {
            throw new InvalidRowException(
                "Row {$this->rowNumber}: Target price too large",
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
            'row_number' => $this->rowNumber,
        ];
    }
}

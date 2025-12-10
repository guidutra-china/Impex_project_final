<?php

namespace App\Services\AI\Parsers;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExcelParser
{
    /**
     * Parse Excel file and extract data + images
     *
     * @param string $filePath
     * @return array
     */
    public function parse(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();

        return [
            'type' => 'excel',
            'metadata' => $this->extractMetadata($worksheet),
            'headers' => $this->extractHeaders($worksheet),
            'sample_rows' => $this->extractSampleRows($worksheet, 10),
            'all_rows' => $this->extractAllRows($worksheet),
            'has_images' => $this->hasImages($worksheet),
            'images' => $this->extractImages($worksheet),
        ];
    }

    /**
     * Extract metadata from worksheet
     *
     * @param Worksheet $worksheet
     * @return array
     */
    protected function extractMetadata(Worksheet $worksheet): array
    {
        return [
            'sheet_name' => $worksheet->getTitle(),
            'max_row' => $worksheet->getHighestRow(),
            'max_column' => $worksheet->getHighestColumn(),
            'max_column_index' => \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString(
                $worksheet->getHighestColumn()
            ),
        ];
    }

    /**
     * Extract headers (first non-empty row)
     *
     * @param Worksheet $worksheet
     * @return array
     */
    protected function extractHeaders(Worksheet $worksheet): array
    {
        $headers = [];
        $maxCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString(
            $worksheet->getHighestColumn()
        );

        // Try to find header row (usually row 1 or first non-empty row)
        for ($row = 1; $row <= min(10, $worksheet->getHighestRow()); $row++) {
            $rowData = [];
            $hasData = false;

            for ($col = 1; $col <= min(50, $maxCol); $col++) {
                $value = $worksheet->getCellByColumnAndRow($col, $row)->getValue();
                if ($value !== null && $value !== '') {
                    $hasData = true;
                    $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $rowData[$columnLetter] = $value;
                }
            }

            if ($hasData) {
                $headers = $rowData;
                $headers['_detected_row'] = $row;
                break;
            }
        }

        return $headers;
    }

    /**
     * Extract sample rows for analysis
     *
     * @param Worksheet $worksheet
     * @param int $limit
     * @return array
     */
    protected function extractSampleRows(Worksheet $worksheet, int $limit = 10): array
    {
        $rows = [];
        $headerRow = $this->extractHeaders($worksheet)['_detected_row'] ?? 1;
        $startRow = $headerRow + 1;
        $maxCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString(
            $worksheet->getHighestColumn()
        );

        for ($row = $startRow; $row <= min($startRow + $limit - 1, $worksheet->getHighestRow()); $row++) {
            $rowData = [];
            $isEmpty = true;

            for ($col = 1; $col <= min(50, $maxCol); $col++) {
                $value = $worksheet->getCellByColumnAndRow($col, $row)->getValue();
                if ($value !== null && $value !== '') {
                    $isEmpty = false;
                }
                $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $rowData[$columnLetter] = $value;
            }

            if (!$isEmpty) {
                $rowData['_row_number'] = $row;
                $rows[] = $rowData;
            }
        }

        return $rows;
    }

    /**
     * Extract all data rows
     *
     * @param Worksheet $worksheet
     * @return array
     */
    protected function extractAllRows(Worksheet $worksheet): array
    {
        $rows = [];
        $headerRow = $this->extractHeaders($worksheet)['_detected_row'] ?? 1;
        $startRow = $headerRow + 1;
        $maxCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString(
            $worksheet->getHighestColumn()
        );

        for ($row = $startRow; $row <= $worksheet->getHighestRow(); $row++) {
            $rowData = [];
            $isEmpty = true;

            for ($col = 1; $col <= min(50, $maxCol); $col++) {
                $value = $worksheet->getCellByColumnAndRow($col, $row)->getValue();
                if ($value !== null && $value !== '') {
                    $isEmpty = false;
                }
                $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $rowData[$columnLetter] = $value;
            }

            if (!$isEmpty) {
                $rowData['_row_number'] = $row;
                $rows[] = $rowData;
            }
        }

        return $rows;
    }

    /**
     * Check if worksheet has images
     *
     * @param Worksheet $worksheet
     * @return bool
     */
    protected function hasImages(Worksheet $worksheet): bool
    {
        return count($worksheet->getDrawingCollection()) > 0;
    }

    /**
     * Extract and save images from worksheet
     *
     * @param Worksheet $worksheet
     * @return array Map of cell coordinates to saved image paths
     */
    protected function extractImages(Worksheet $worksheet): array
    {
        $images = [];

        foreach ($worksheet->getDrawingCollection() as $drawing) {
            if ($drawing instanceof Drawing) {
                try {
                    $coordinates = $drawing->getCoordinates();
                    $imagePath = $drawing->getPath();

                    if (file_exists($imagePath)) {
                        $savedPath = $this->saveImage($imagePath);
                        if ($savedPath) {
                            $images[$coordinates] = $savedPath;
                        }
                    }
                } catch (\Throwable $e) {
                    \Log::warning('Failed to extract image from Excel', [
                        'coordinates' => $drawing->getCoordinates() ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $images;
    }

    /**
     * Save image to storage
     *
     * @param string $sourcePath
     * @return string|null Storage path
     */
    protected function saveImage(string $sourcePath): ?string
    {
        try {
            $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
            $filename = Str::uuid() . '.' . $extension;
            $storagePath = 'products/import-temp/' . $filename;

            $contents = file_get_contents($sourcePath);
            Storage::disk('public')->put($storagePath, $contents);

            return $storagePath;
        } catch (\Throwable $e) {
            \Log::error('Failed to save image', [
                'source' => $sourcePath,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}

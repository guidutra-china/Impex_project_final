<?php

namespace App\Services\AI\Parsers;

use Smalot\PdfParser\Parser as PdfTextParser;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PDFParser
{
    /**
     * Parse PDF file and extract text + images
     *
     * @param string $filePath
     * @return array
     */
    public function parse(string $filePath): array
    {
        $text = $this->extractText($filePath);
        $images = $this->extractImages($filePath);

        return [
            'type' => 'pdf',
            'metadata' => $this->extractMetadata($text),
            'text' => $text,
            'has_images' => count($images) > 0,
            'images' => $images,
            'headers' => $this->detectHeaders($text),
            'sample_rows' => $this->detectRows($text, 10),
        ];
    }

    /**
     * Extract text from PDF
     *
     * @param string $filePath
     * @return string
     */
    protected function extractText(string $filePath): string
    {
        try {
            $parser = new PdfTextParser();
            $pdf = $parser->parseFile($filePath);
            return $pdf->getText();
        } catch (\Throwable $e) {
            \Log::error('Failed to extract text from PDF', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);
            return '';
        }
    }

    /**
     * Extract images from PDF using pdfimages command
     *
     * @param string $filePath
     * @return array
     */
    protected function extractImages(string $filePath): array
    {
        $images = [];

        try {
            // Check if pdfimages is available
            $checkCommand = 'which pdfimages 2>/dev/null';
            exec($checkCommand, $output, $returnCode);

            if ($returnCode !== 0) {
                \Log::warning('pdfimages command not available, skipping image extraction');
                return [];
            }

            // Create temp directory for extracted images
            $tempDir = storage_path('app/temp/' . Str::uuid());
            mkdir($tempDir, 0755, true);

            // Extract images using pdfimages
            $outputPrefix = $tempDir . '/img';
            $command = sprintf(
                'pdfimages -png %s %s 2>&1',
                escapeshellarg($filePath),
                escapeshellarg($outputPrefix)
            );

            exec($command, $output, $returnCode);

            if ($returnCode === 0) {
                // Find all extracted images
                $extractedFiles = glob($tempDir . '/*');

                foreach ($extractedFiles as $imagePath) {
                    $savedPath = $this->saveImage($imagePath);
                    if ($savedPath) {
                        $images[] = $savedPath;
                    }
                }
            }

            // Clean up temp directory
            array_map('unlink', glob($tempDir . '/*'));
            rmdir($tempDir);

        } catch (\Throwable $e) {
            \Log::error('Failed to extract images from PDF', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);
        }

        return $images;
    }

    /**
     * Save image to storage
     *
     * @param string $sourcePath
     * @return string|null
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
            \Log::error('Failed to save PDF image', [
                'source' => $sourcePath,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Extract metadata from text
     *
     * @param string $text
     * @return array
     */
    protected function extractMetadata(string $text): array
    {
        $metadata = [
            'length' => strlen($text),
            'lines' => substr_count($text, "\n"),
        ];

        // Try to detect supplier info
        if (preg_match('/([A-Z][a-z]+ .*?Co\.,? ?Ltd\.?)/i', $text, $matches)) {
            $metadata['potential_supplier'] = $matches[1];
        }

        // Try to detect email
        if (preg_match('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i', $text, $matches)) {
            $metadata['email'] = $matches[1];
        }

        // Try to detect phone
        if (preg_match('/(\+?\d{1,3}[-.\s]?\(?\d{1,4}\)?[-.\s]?\d{1,4}[-.\s]?\d{1,9})/i', $text, $matches)) {
            $metadata['phone'] = $matches[1];
        }

        return $metadata;
    }

    /**
     * Detect headers from text
     *
     * @param string $text
     * @return array
     */
    protected function detectHeaders(string $text): array
    {
        $headers = [];
        $lines = explode("\n", $text);

        // Look for lines that look like headers (all caps, or with specific keywords)
        $headerKeywords = ['MODEL', 'PRODUCT', 'DESCRIPTION', 'PRICE', 'QTY', 'QUANTITY', 'WEIGHT', 'DIMENSION'];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            foreach ($headerKeywords as $keyword) {
                if (stripos($line, $keyword) !== false) {
                    $headers[] = $line;
                    break;
                }
            }

            // Stop after finding potential header line
            if (count($headers) > 0) break;
        }

        return $headers;
    }

    /**
     * Detect data rows from text
     *
     * @param string $text
     * @param int $limit
     * @return array
     */
    protected function detectRows(string $text, int $limit = 10): array
    {
        $rows = [];
        $lines = explode("\n", $text);

        // Simple heuristic: lines with multiple fields separated by spaces/tabs
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Skip lines that look like headers or titles
            if (preg_match('/^(proforma|invoice|buyer|seller|company|address|tel|email|date)/i', $line)) {
                continue;
            }

            // Look for lines with product-like data (model numbers, prices, etc.)
            if (preg_match('/[A-Z0-9-]{3,}.*?\d+/', $line)) {
                $rows[] = $line;

                if (count($rows) >= $limit) {
                    break;
                }
            }
        }

        return $rows;
    }
}

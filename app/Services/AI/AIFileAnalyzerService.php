<?php

namespace App\Services\AI;

use App\Services\AI\Parsers\ExcelParser;
use App\Services\AI\Parsers\PDFParser;
use Illuminate\Support\Facades\Log;

class AIFileAnalyzerService
{
    protected DeepSeekService $deepSeek;
    protected ExcelParser $excelParser;
    protected PDFParser $pdfParser;

    public function __construct()
    {
        $this->deepSeek = new DeepSeekService();
        $this->excelParser = new ExcelParser();
        $this->pdfParser = new PDFParser();
    }

    /**
     * Analyze uploaded file and return AI-powered analysis
     *
     * @param string $filePath
     * @return array
     * @throws \Exception
     */
    public function analyzeFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \Exception('File not found: ' . $filePath);
        }

        // Detect file type
        $fileType = $this->detectFileType($filePath);

        Log::info('Analyzing file', [
            'path' => $filePath,
            'type' => $fileType,
        ]);

        // Parse file based on type
        $parsedData = match($fileType) {
            'excel' => $this->excelParser->parse($filePath),
            'pdf' => $this->pdfParser->parse($filePath),
            default => throw new \Exception('Unsupported file type: ' . $fileType),
        };

        // Send to DeepSeek for AI analysis
        $aiAnalysis = $this->deepSeek->analyzeFileStructure($parsedData);

        // Merge parsed data with AI analysis
        return array_merge($parsedData, [
            'ai_analysis' => $aiAnalysis,
            'file_type' => $fileType,
            'file_path' => $filePath,
        ]);
    }

    /**
     * Detect file type from extension and mime type
     *
     * @param string $filePath
     * @return string
     */
    protected function detectFileType(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeType = mime_content_type($filePath);

        if (in_array($extension, ['xlsx', 'xls']) || 
            str_contains($mimeType, 'spreadsheet')) {
            return 'excel';
        }

        if ($extension === 'pdf' || $mimeType === 'application/pdf') {
            return 'pdf';
        }

        throw new \Exception('Unsupported file type: ' . $extension);
    }

    /**
     * Get suggested mapping from AI analysis
     *
     * @param array $analysis
     * @return array
     */
    public function getSuggestedMapping(array $analysis): array
    {
        $aiAnalysis = $analysis['ai_analysis'] ?? [];
        return $aiAnalysis['column_mapping'] ?? [];
    }

    /**
     * Get supplier info from AI analysis
     *
     * @param array $analysis
     * @return array|null
     */
    public function getSupplierInfo(array $analysis): ?array
    {
        $aiAnalysis = $analysis['ai_analysis'] ?? [];
        $supplier = $aiAnalysis['supplier'] ?? null;

        if ($supplier && !empty($supplier['name'])) {
            return $supplier;
        }

        return null;
    }

    /**
     * Get document type from AI analysis
     *
     * @param array $analysis
     * @return string
     */
    public function getDocumentType(array $analysis): string
    {
        $aiAnalysis = $analysis['ai_analysis'] ?? [];
        return $aiAnalysis['document_type'] ?? 'Unknown';
    }

    /**
     * Get products count from AI analysis
     *
     * @param array $analysis
     * @return int
     */
    public function getProductsCount(array $analysis): int
    {
        $aiAnalysis = $analysis['ai_analysis'] ?? [];
        return $aiAnalysis['products_count'] ?? count($analysis['all_rows'] ?? []);
    }

    /**
     * Get suggested tags from AI analysis
     *
     * @param array $analysis
     * @return array
     */
    public function getSuggestedTags(array $analysis): array
    {
        $aiAnalysis = $analysis['ai_analysis'] ?? [];
        return $aiAnalysis['suggested_tags'] ?? [];
    }

    /**
     * Get currency from AI analysis
     *
     * @param array $analysis
     * @return string
     */
    public function getCurrency(array $analysis): string
    {
        $aiAnalysis = $analysis['ai_analysis'] ?? [];
        return $aiAnalysis['currency'] ?? 'USD';
    }

    /**
     * Get start row for data from AI analysis
     *
     * @param array $analysis
     * @return int
     */
    public function getStartRow(array $analysis): int
    {
        $aiAnalysis = $analysis['ai_analysis'] ?? [];
        return $aiAnalysis['start_row'] ?? 2;
    }

    /**
     * Validate and adjust mapping based on user input
     *
     * @param array $analysis
     * @param array $userMapping
     * @return array
     */
    public function adjustMapping(array $analysis, array $userMapping): array
    {
        $suggestedMapping = $this->getSuggestedMapping($analysis);

        // Merge user adjustments with suggested mapping
        foreach ($userMapping as $column => $field) {
            if (isset($suggestedMapping[$column])) {
                $suggestedMapping[$column]['field'] = $field;
                $suggestedMapping[$column]['user_adjusted'] = true;
            } else {
                $suggestedMapping[$column] = [
                    'field' => $field,
                    'confidence' => 1.0,
                    'user_adjusted' => true,
                ];
            }
        }

        return $suggestedMapping;
    }
}

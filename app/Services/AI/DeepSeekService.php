<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepSeekService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.deepseek.com/v1';
    protected string $model = 'deepseek-chat';
    
    public function __construct()
    {
        // Use DEEP_SEEK or DEEP_SEEK_2 from environment
        $this->apiKey = config('services.deepseek.api_key') 
            ?? env('DEEP_SEEK') 
            ?? env('DEEP_SEEK_2');
            
        if (empty($this->apiKey)) {
            throw new \Exception('DeepSeek API key not configured');
        }
    }

    /**
     * Send a chat completion request to DeepSeek
     *
     * @param array $messages Array of message objects with 'role' and 'content'
     * @param array $options Additional options (temperature, max_tokens, etc.)
     * @return array Response from DeepSeek API
     * @throws \Exception
     */
    public function chat(array $messages, array $options = []): array
    {
        $payload = array_merge([
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 4000,
        ], $options);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout(120)
            ->post($this->baseUrl . '/chat/completions', $payload);

            if (!$response->successful()) {
                throw new \Exception('DeepSeek API error: ' . $response->body());
            }

            $data = $response->json();

            // Log usage for monitoring
            if (isset($data['usage'])) {
                Log::info('DeepSeek API usage', [
                    'prompt_tokens' => $data['usage']['prompt_tokens'] ?? 0,
                    'completion_tokens' => $data['usage']['completion_tokens'] ?? 0,
                    'total_tokens' => $data['usage']['total_tokens'] ?? 0,
                ]);
            }

            return $data;

        } catch (\Throwable $e) {
            Log::error('DeepSeek API request failed', [
                'error' => $e->getMessage(),
                'messages' => $messages,
            ]);
            throw $e;
        }
    }

    /**
     * Analyze Excel/PDF file structure and suggest mapping
     *
     * @param array $fileData Structured data from file parser
     * @return array Analysis result with suggested mapping
     */
    public function analyzeFileStructure(array $fileData): array
    {
        $prompt = $this->buildAnalysisPrompt($fileData);

        $messages = [
            [
                'role' => 'system',
                'content' => 'You are an expert data analyst specializing in product catalogs, proforma invoices, and supplier documents. Your task is to analyze file structures and suggest optimal field mappings for product import. Always respond in valid JSON format.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ];

        $response = $this->chat($messages, [
            'temperature' => 0.3, // Lower temperature for more consistent output
            'response_format' => ['type' => 'json_object'], // Request JSON response
        ]);

        $content = $response['choices'][0]['message']['content'] ?? '{}';
        
        try {
            return json_decode($content, true) ?? [];
        } catch (\Throwable $e) {
            Log::error('Failed to parse DeepSeek JSON response', [
                'content' => $content,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Build analysis prompt from file data
     *
     * @param array $fileData
     * @return string
     */
    protected function buildAnalysisPrompt(array $fileData): string
    {
        $fileType = $fileData['type'] ?? 'unknown';
        $headers = $fileData['headers'] ?? [];
        $sampleRows = $fileData['sample_rows'] ?? [];
        $metadata = $fileData['metadata'] ?? [];
        $hasImages = $fileData['has_images'] ?? false;

        $prompt = "Analyze this {$fileType} file and provide a structured analysis.\n\n";
        
        $prompt .= "**File Metadata:**\n";
        $prompt .= json_encode($metadata, JSON_PRETTY_PRINT) . "\n\n";
        
        $prompt .= "**Headers/Columns:**\n";
        $prompt .= json_encode($headers, JSON_PRETTY_PRINT) . "\n\n";
        
        $prompt .= "**Sample Data (first 5 rows):**\n";
        $prompt .= json_encode(array_slice($sampleRows, 0, 5), JSON_PRETTY_PRINT) . "\n\n";
        
        if ($hasImages) {
            $prompt .= "**Note:** This file contains embedded images.\n\n";
        }

        $prompt .= "Please analyze and provide a JSON response with this exact structure:\n";
        $prompt .= <<<'JSON'
{
  "document_type": "Proforma Invoice|Product Catalog|Price List|Other",
  "confidence": 0.95,
  "supplier": {
    "name": "Supplier name if found",
    "country": "Country code if found",
    "email": "Email if found",
    "phone": "Phone if found"
  },
  "products_count": 70,
  "has_images": true,
  "currency": "USD",
  "start_row": 7,
  "column_mapping": {
    "A": {
      "field": "sku",
      "confidence": 0.95,
      "label": "Model NO",
      "description": "Product SKU or model number"
    },
    "B": {
      "field": "name",
      "confidence": 0.98,
      "label": "PRODUCT",
      "description": "Product name"
    }
  },
  "suggested_tags": ["Electronics", "Fitness Equipment"],
  "notes": "Additional observations or recommendations"
}
JSON;

        $prompt .= "\n\n**Field mapping options:**\n";
        $prompt .= "- sku: Product code/SKU\n";
        $prompt .= "- name: Product name\n";
        $prompt .= "- description: Product description\n";
        $prompt .= "- price: Unit price\n";
        $prompt .= "- photo: Product photo column\n";
        $prompt .= "- gross_weight: Weight in kg\n";
        $prompt .= "- net_weight: Net weight in kg\n";
        $prompt .= "- dimensions: Product dimensions\n";
        $prompt .= "- moq: Minimum order quantity\n";
        $prompt .= "- lead_time_days: Lead time in days\n";
        $prompt .= "- hs_code: HS code\n";
        $prompt .= "- brand: Brand name\n";
        $prompt .= "- model_number: Model number\n";
        $prompt .= "- certifications: Certifications\n";
        $prompt .= "- carton_length: Carton length in cm\n";
        $prompt .= "- carton_width: Carton width in cm\n";
        $prompt .= "- carton_height: Carton height in cm\n";
        $prompt .= "- carton_weight: Carton weight in kg\n";
        $prompt .= "- carton_cbm: Carton volume in CBM\n";
        $prompt .= "- pcs_per_carton: Pieces per carton\n";

        return $prompt;
    }

    /**
     * Extract product data from unstructured text (for PDFs)
     *
     * @param string $text Raw text from PDF
     * @return array Structured product data
     */
    public function extractProductsFromText(string $text): array
    {
        $messages = [
            [
                'role' => 'system',
                'content' => 'You are an expert at extracting structured product data from unstructured text. Extract all products with their details and return as JSON array.'
            ],
            [
                'role' => 'user',
                'content' => "Extract all products from this text and return as JSON array:\n\n" . $text
            ]
        ];

        $response = $this->chat($messages, [
            'temperature' => 0.2,
            'response_format' => ['type' => 'json_object'],
        ]);

        $content = $response['choices'][0]['message']['content'] ?? '{"products":[]}';
        
        try {
            $data = json_decode($content, true);
            return $data['products'] ?? [];
        } catch (\Throwable $e) {
            Log::error('Failed to extract products from text', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get embedding for text (for future similarity matching)
     *
     * @param string $text
     * @return array
     */
    public function getEmbedding(string $text): array
    {
        // DeepSeek doesn't have embeddings API yet
        // This is a placeholder for future implementation
        throw new \Exception('Embeddings not yet supported by DeepSeek');
    }
}

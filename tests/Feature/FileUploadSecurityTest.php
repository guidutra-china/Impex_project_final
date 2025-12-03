<?php

namespace Tests\Feature;

use App\Services\FileUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * File Upload Security Test
 * 
 * Tests the security mechanisms of the FileUploadService to ensure
 * that only safe files are accepted and dangerous files are rejected.
 */
class FileUploadSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected FileUploadService $uploadService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uploadService = new FileUploadService();
        Storage::fake('private');
    }

    /**
     * Test accepting valid PDF file
     */
    public function test_accepts_valid_pdf_file(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

        $result = $this->uploadService->upload($file, 'documents', 'test');

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['path']);
        $this->assertNull($result['error']);
    }

    /**
     * Test accepting valid Excel file
     */
    public function test_accepts_valid_excel_file(): void
    {
        $file = UploadedFile::fake()->create(
            'data.xlsx',
            2048,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

        $result = $this->uploadService->upload($file, 'spreadsheets', 'imports');

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['path']);
    }

    /**
     * Test rejecting PHP file
     */
    public function test_rejects_php_file(): void
    {
        $file = UploadedFile::fake()->create('malicious.php', 512, 'application/x-php');

        $result = $this->uploadService->upload($file, 'documents', 'test');

        $this->assertFalse($result['success']);
        $this->assertNull($result['path']);
        $this->assertStringContainsString('not allowed', $result['error']);
    }

    /**
     * Test rejecting executable file
     */
    public function test_rejects_executable_file(): void
    {
        $file = UploadedFile::fake()->create('malware.exe', 1024, 'application/x-msdownload');

        $result = $this->uploadService->upload($file, 'documents', 'test');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not allowed', $result['error']);
    }

    /**
     * Test rejecting file exceeding size limit
     */
    public function test_rejects_file_exceeding_size_limit(): void
    {
        // Create a file larger than 10MB limit for documents
        $file = UploadedFile::fake()->create('large.pdf', 11 * 1024 * 1024, 'application/pdf');

        $result = $this->uploadService->upload($file, 'documents', 'test');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('exceeds maximum size', $result['error']);
    }

    /**
     * Test rejecting file with wrong MIME type
     */
    public function test_rejects_file_with_wrong_mime_type(): void
    {
        // Create a file with wrong MIME type
        $file = UploadedFile::fake()->create('image.txt', 1024, 'text/plain');

        $result = $this->uploadService->upload($file, 'images', 'test');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not allowed', $result['error']);
    }

    /**
     * Test rejecting invalid category
     */
    public function test_rejects_invalid_category(): void
    {
        $file = UploadedFile::fake()->create('file.pdf', 1024, 'application/pdf');

        $result = $this->uploadService->upload($file, 'invalid_category', 'test');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid file category', $result['error']);
    }

    /**
     * Test filename sanitization
     */
    public function test_sanitizes_filename(): void
    {
        $file = UploadedFile::fake()->create('../../etc/passwd.pdf', 1024, 'application/pdf');

        $result = $this->uploadService->upload($file, 'documents', 'test');

        // Should reject due to path traversal attempt
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid filename', $result['error']);
    }

    /**
     * Test file with special characters in name
     */
    public function test_handles_special_characters_in_filename(): void
    {
        $file = UploadedFile::fake()->create('document_2024-01-01.pdf', 1024, 'application/pdf');

        $result = $this->uploadService->upload($file, 'documents', 'test');

        $this->assertTrue($result['success']);
        // Filename should be sanitized to UUID
        $this->assertStringContainsString('.pdf', $result['path']);
    }

    /**
     * Test multiple file uploads
     */
    public function test_multiple_file_uploads(): void
    {
        $files = [
            UploadedFile::fake()->create('doc1.pdf', 1024, 'application/pdf'),
            UploadedFile::fake()->create('doc2.pdf', 1024, 'application/pdf'),
            UploadedFile::fake()->create('doc3.pdf', 1024, 'application/pdf'),
        ];

        $results = [];
        foreach ($files as $file) {
            $results[] = $this->uploadService->upload($file, 'documents', 'test');
        }

        // All should succeed
        foreach ($results as $result) {
            $this->assertTrue($result['success']);
        }

        // All should have different paths
        $paths = array_map(fn($r) => $r['path'], $results);
        $this->assertEquals(3, count(array_unique($paths)));
    }

    /**
     * Test file deletion
     */
    public function test_can_delete_uploaded_file(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');
        $result = $this->uploadService->upload($file, 'documents', 'test');

        $this->assertTrue($result['success']);

        // Delete the file
        $deleted = $this->uploadService->delete($result['path']);
        $this->assertTrue($deleted);
    }

    /**
     * Test deleting non-existent file
     */
    public function test_handles_deleting_non_existent_file(): void
    {
        $deleted = $this->uploadService->delete('non/existent/file.pdf');
        $this->assertFalse($deleted);
    }

    /**
     * Test accepting valid image file
     */
    public function test_accepts_valid_image_file(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

        $result = $this->uploadService->upload($file, 'images', 'photos');

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['path']);
    }

    /**
     * Test rejecting oversized image
     */
    public function test_rejects_oversized_image(): void
    {
        // Create a 6MB image (exceeds 5MB limit)
        $file = UploadedFile::fake()->create('large.jpg', 6 * 1024 * 1024, 'image/jpeg');

        $result = $this->uploadService->upload($file, 'images', 'photos');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('exceeds maximum size', $result['error']);
    }

    /**
     * Test CSV file upload
     */
    public function test_accepts_csv_file(): void
    {
        $file = UploadedFile::fake()->create('data.csv', 1024, 'text/csv');

        $result = $this->uploadService->upload($file, 'spreadsheets', 'imports');

        $this->assertTrue($result['success']);
    }

    /**
     * Test storing file in private storage
     */
    public function test_stores_file_in_private_storage(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

        $result = $this->uploadService->upload($file, 'documents', 'test');

        $this->assertTrue($result['success']);
        // File should be stored in private disk
        Storage::disk('private')->assertExists($result['path']);
    }
}

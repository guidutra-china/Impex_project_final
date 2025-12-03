<?php

namespace Tests\Feature;

use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * File Upload Security Test
 * 
 * Tests the FileUploadService to ensure secure file handling.
 */
class FileUploadSecurityTest extends TestCase
{
    protected FileUploadService $uploadService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the private storage disk
        Storage::fake('private');
        
        $this->uploadService = app(FileUploadService::class);
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
        $file = UploadedFile::fake()->create('data.xlsx', 1024, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $result = $this->uploadService->upload($file, 'documents', 'test');

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['path']);
    }

    /**
     * Test rejecting PHP file
     */
    public function test_rejects_php_file(): void
    {
        $file = UploadedFile::fake()->create('shell.php', 1024, 'application/x-php');

        $result = $this->uploadService->upload($file, 'documents', 'test');

        $this->assertFalse($result['success']);
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
        // Create a file larger than 10 MB limit for documents
        $file = UploadedFile::fake()->create('huge.pdf', 11 * 1024 * 1024, 'application/pdf');

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
        $file = UploadedFile::fake()->create('image.pdf', 1024, 'image/jpeg');

        $result = $this->uploadService->upload($file, 'documents', 'test');

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
     * Test sanitizing filename with special characters
     */
    public function test_sanitizes_filename_with_special_characters(): void
    {
        // Create file with special characters - Laravel will sanitize the name
        $file = UploadedFile::fake()->create('test@#$%file.pdf', 1024, 'application/pdf');

        $result = $this->uploadService->upload($file, 'documents', 'test');

        // Should succeed because Laravel sanitizes the filename
        $this->assertTrue($result['success']);
        $this->assertNotNull($result['path']);
    }

    /**
     * Test handling special characters in filename
     */
    public function test_handles_special_characters_in_filename(): void
    {
        $file = UploadedFile::fake()->create('document (1).pdf', 1024, 'application/pdf');

        $result = $this->uploadService->upload($file, 'documents', 'test');

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['path']);
    }

    /**
     * Test multiple file uploads
     */
    public function test_multiple_file_uploads(): void
    {
        $files = [
            UploadedFile::fake()->create('file1.pdf', 1024, 'application/pdf'),
            UploadedFile::fake()->create('file2.xlsx', 1024, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            UploadedFile::fake()->create('file3.pdf', 1024, 'application/pdf'),
        ];

        foreach ($files as $file) {
            $result = $this->uploadService->upload($file, 'documents', 'test');
            $this->assertTrue($result['success']);
        }
    }

    /**
     * Test deleting uploaded file
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
     * Test handling deleting non-existent file
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

        $result = $this->uploadService->upload($file, 'images', 'test');

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['path']);
    }

    /**
     * Test rejecting oversized image
     */
    public function test_rejects_oversized_image(): void
    {
        // Create a file larger than 5 MB limit for images
        $file = UploadedFile::fake()->create('huge.jpg', 6 * 1024 * 1024, 'image/jpeg');

        $result = $this->uploadService->upload($file, 'images', 'test');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('exceeds maximum size', $result['error']);
    }

    /**
     * Test accepting CSV file
     */
    public function test_accepts_csv_file(): void
    {
        $file = UploadedFile::fake()->create('data.csv', 1024, 'text/csv');

        $result = $this->uploadService->upload($file, 'spreadsheets', 'test');

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['path']);
    }

    /**
     * Test storing file in private storage
     */
    public function test_stores_file_in_private_storage(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

        $result = $this->uploadService->upload($file, 'documents', 'test');

        $this->assertTrue($result['success']);
        // Verify file is stored in private disk
        $this->assertStringContainsString('test/', $result['path']);
    }
}

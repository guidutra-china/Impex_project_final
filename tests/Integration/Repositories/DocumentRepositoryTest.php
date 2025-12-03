<?php

namespace Tests\Integration\Repositories;

use App\Models\Document;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Repositories\DocumentRepository;
use Tests\TestCase;

class DocumentRepositoryTest extends TestCase
{
    private DocumentRepository $repository;
    private User $user;
    private Product $product;
    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = app(DocumentRepository::class);
        
        $this->user = User::factory()->create();
        $this->product = Product::factory()->for($this->user)->create();
        $this->supplier = Supplier::factory()->for($this->user)->create();
    }

    // ===== TESTES CRUD =====

    /** @test */
    public function it_can_find_document_by_id()
    {
        $document = Document::factory()->create();
        
        $found = $this->repository->findById($document->id);
        
        expect($found)->not->toBeNull();
        expect($found->id)->toBe($document->id);
    }

    /** @test */
    public function it_returns_null_when_document_not_found()
    {
        $found = $this->repository->findById(99999);
        
        expect($found)->toBeNull();
    }

    /** @test */
    public function it_can_get_all_documents()
    {
        Document::factory(3)->create();
        
        $documents = $this->repository->all();
        
        expect($documents->count())->toBeGreaterThanOrEqual(3);
    }

    /** @test */
    public function it_can_create_document()
    {
        $data = [
            'name' => 'Test Document',
            'type' => 'specification',
            'file_path' => '/documents/test.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'created_by' => $this->user->id,
        ];
        
        $document = $this->repository->create($data);
        
        expect($document)->toBeInstanceOf(Document::class);
        expect($document->type)->toBe('specification');
    }

    /** @test */
    public function it_can_update_document()
    {
        $document = Document::factory()->create();
        
        $updated = $this->repository->update($document->id, [
            'name' => 'Updated Document',
        ]);
        
        expect($updated)->toBeTrue();
        expect($document->fresh()->name)->toBe('Updated Document');
    }

    /** @test */
    public function it_can_delete_document()
    {
        $document = Document::factory()->create();
        
        $deleted = $this->repository->delete($document->id);
        
        expect($deleted)->toBeTrue();
        expect(Document::find($document->id))->toBeNull();
    }

    // ===== TESTES DE FILTROS =====

    /** @test */
    public function it_can_get_documents_by_type()
    {
        Document::factory(2)->create(['type' => 'specification']);
        Document::factory(1)->create(['type' => 'manual']);
        
        $specs = $this->repository->getByType('specification');
        
        expect($specs->count())->toBeGreaterThanOrEqual(2);
        expect($specs->every(fn($d) => $d->type === 'specification'))->toBeTrue();
    }

    // ===== TESTES DE QUERIES ESPECÍFICAS =====

    /** @test */
    public function it_can_get_product_documents_query()
    {
        $query = $this->repository->getProductDocumentsQuery($this->product->id);
        
        expect($query)->not->toBeNull();
    }

    /** @test */
    public function it_can_get_supplier_documents_query()
    {
        $query = $this->repository->getSupplierDocumentsQuery($this->supplier->id);
        
        expect($query)->not->toBeNull();
    }

    /** @test */
    public function it_can_get_product_photos_query()
    {
        $query = $this->repository->getProductPhotosQuery($this->product->id);
        
        expect($query)->not->toBeNull();
    }

    /** @test */
    public function it_can_get_supplier_photos_query()
    {
        $query = $this->repository->getSupplierPhotosQuery($this->supplier->id);
        
        expect($query)->not->toBeNull();
    }

    // ===== TESTES DE BUSCA =====

    /** @test */
    public function it_can_search_documents()
    {
        $document = Document::factory()->create(['name' => 'UNIQUE-DOCUMENT-12345']);
        
        $results = $this->repository->searchDocuments('UNIQUE-DOCUMENT');
        
        expect($results->pluck('id')->contains($document->id))->toBeTrue();
    }

    // ===== TESTES DE ESTATÍSTICAS =====

    /** @test */
    public function it_can_get_statistics()
    {
        Document::factory(5)->create();
        
        $stats = $this->repository->getStatistics();
        
        expect($stats)->toHaveKeys([
            'total_documents',
            'total_size',
            'average_size',
        ]);
        expect($stats['total_documents'])->toBeGreaterThanOrEqual(5);
    }

    // ===== TESTES DE EDGE CASES =====

    /** @test */
    public function it_handles_empty_results_gracefully()
    {
        $results = $this->repository->getByType('non_existent_type');
        
        expect($results)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
        expect($results->count())->toBe(0);
    }

    /** @test */
    public function it_can_count_documents_by_type()
    {
        Document::factory(3)->create(['type' => 'specification']);
        
        $count = $this->repository->countByType('specification');
        
        expect($count)->toBeGreaterThanOrEqual(3);
    }

    /** @test */
    public function it_validates_required_fields_on_create()
    {
        $this->expectException(\Exception::class);
        
        $this->repository->create([
            'name' => 'Test',
        ]);
    }

    /** @test */
    public function it_can_get_query_builder()
    {
        $query = $this->repository->getQuery();
        
        expect($query)->not->toBeNull();
        expect($query->count())->toBeGreaterThanOrEqual(0);
    }
}

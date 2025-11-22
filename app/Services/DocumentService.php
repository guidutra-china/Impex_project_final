<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DocumentService
{
    /**
     * Upload de documento
     */
    public function uploadDocument(array $data, UploadedFile $file): Document
    {
        // Gerar nome único
        $filename = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('documents/' . $data['document_type'], $filename);

        // Criar documento
        $document = Document::create([
            'document_type' => $data['document_type'],
            'related_type' => $data['related_type'] ?? null,
            'related_id' => $data['related_id'] ?? null,
            'document_number' => $data['document_number'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'issue_date' => $data['issue_date'] ?? null,
            'expiry_date' => $data['expiry_date'] ?? null,
            'status' => $data['status'] ?? 'draft',
            'is_public' => $data['is_public'] ?? false,
            'notes' => $data['notes'] ?? null,
            'uploaded_by' => auth()->id(),
        ]);

        return $document;
    }

    /**
     * Criar nova versão de documento
     */
    public function createVersion(Document $document, UploadedFile $file, string $changeNotes = null): void
    {
        // Salvar versão atual
        $currentVersion = $document->versions()->count() + 1;

        $document->versions()->create([
            'version_number' => $currentVersion - 1,
            'file_path' => $document->file_path,
            'file_name' => $document->file_name,
            'file_size' => $document->file_size,
            'change_notes' => 'Original version',
            'uploaded_by' => $document->uploaded_by,
        ]);

        // Upload novo arquivo
        $filename = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('documents/' . $document->document_type, $filename);

        // Atualizar documento
        $document->update([
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);

        // Criar registro de versão
        $document->versions()->create([
            'version_number' => $currentVersion,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'change_notes' => $changeNotes,
            'uploaded_by' => auth()->id(),
        ]);
    }

    /**
     * Buscar documentos por relacionamento
     */
    public function getDocumentsByRelation(string $relatedType, int $relatedId): Collection
    {
        return Document::where('related_type', $relatedType)
            ->where('related_id', $relatedId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Verificar documentos expirados
     */
    public function getExpiringDocuments(int $days = 30): Collection
    {
        return Document::where('status', 'valid')
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays($days)])
            ->get();
    }

    /**
     * Download de documento
     */
    public function downloadDocument(Document $document)
    {
        return Storage::download($document->file_path, $document->file_name);
    }
}
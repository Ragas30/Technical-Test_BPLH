<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Document\ReplaceDocumentRequest;
use App\Http\Requests\Document\StoreDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Services\DocumentService;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    private const DOCUMENT_EXAMPLE = [
        'id' => '4a1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d',
        'project_id' => '9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d',
        'name' => 'dokumen-teknis.pdf',
        'original_name' => 'Dokumen Teknis.pdf',
        'mime_type' => 'application/pdf',
        'extension' => 'pdf',
        'size' => 204800,
        'uploader' => ['id' => '9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d', 'name' => 'Budi Santoso', 'email' => 'budi@docflow.test'],
        'created_at' => '2026-08-01T09:05:00+07:00',
        'updated_at' => '2026-08-01T09:05:00+07:00',
    ];

    public function __construct(private readonly DocumentService $documentService) {}

    #[Response(200, 'Daftar dokumen milik project.', examples: [['data' => [self::DOCUMENT_EXAMPLE]]])]
    public function index(Project $project): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [ProjectDocument::class, $project]);

        return DocumentResource::collection($this->documentService->listForProject($project->id));
    }

    #[Response(201, 'Dokumen berhasil diunggah (multiple upload).', examples: [['data' => [self::DOCUMENT_EXAMPLE]]])]
    public function store(StoreDocumentRequest $request, Project $project): JsonResponse
    {
        $this->authorize('create', [ProjectDocument::class, $project]);

        return DocumentResource::collection($this->documentService->upload($project, $request->toDTO(), $request->user()))
            ->response()
            ->setStatusCode(201);
    }

    #[Response(200, 'Dokumen berhasil diganti.', examples: [['data' => self::DOCUMENT_EXAMPLE]])]
    public function replace(ReplaceDocumentRequest $request, ProjectDocument $document): DocumentResource
    {
        $this->authorize('update', $document);

        return DocumentResource::make($this->documentService->replace($document, $request->toDTO()));
    }

    #[Response(200, 'Dokumen berhasil dihapus.', examples: [['message' => 'Dokumen berhasil dihapus.']])]
    public function destroy(ProjectDocument $document): JsonResponse
    {
        $this->authorize('delete', $document);

        $this->documentService->delete($document);

        return response()->json(['message' => 'Dokumen berhasil dihapus.']);
    }

    #[Response(200, 'Berkas dokumen diunduh.', mediaType: 'application/octet-stream', type: 'string', format: 'binary')]
    public function download(ProjectDocument $document): StreamedResponse
    {
        $this->authorize('view', $document);

        return Storage::disk(config('documents.disk'))->download($document->path, $document->original_name);
    }

    #[Response(200, 'Pratinjau berkas dokumen.', mediaType: 'application/octet-stream', type: 'string', format: 'binary')]
    public function preview(ProjectDocument $document): StreamedResponse
    {
        $this->authorize('view', $document);

        return Storage::disk(config('documents.disk'))->response($document->path, $document->original_name);
    }
}

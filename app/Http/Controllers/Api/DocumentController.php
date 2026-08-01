<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Document\ReplaceDocumentRequest;
use App\Http\Requests\Document\StoreDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(private readonly DocumentService $documentService) {}

    public function index(Project $project): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [ProjectDocument::class, $project]);

        return DocumentResource::collection($this->documentService->listForProject($project->id));
    }

    public function store(StoreDocumentRequest $request, Project $project): JsonResponse
    {
        $this->authorize('create', [ProjectDocument::class, $project]);

        return DocumentResource::collection($this->documentService->upload($project, $request->toDTO(), $request->user()))
            ->response()
            ->setStatusCode(201);
    }

    public function replace(ReplaceDocumentRequest $request, ProjectDocument $document): DocumentResource
    {
        $this->authorize('update', $document);

        return DocumentResource::make($this->documentService->replace($document, $request->toDTO()));
    }

    public function destroy(ProjectDocument $document): JsonResponse
    {
        $this->authorize('delete', $document);

        $this->documentService->delete($document);

        return response()->json(['message' => 'Dokumen berhasil dihapus.']);
    }

    public function download(ProjectDocument $document): StreamedResponse
    {
        $this->authorize('view', $document);

        return Storage::disk(config('documents.disk'))->download($document->path, $document->original_name);
    }

    public function preview(ProjectDocument $document): StreamedResponse
    {
        $this->authorize('view', $document);

        return Storage::disk(config('documents.disk'))->response($document->path, $document->original_name);
    }
}

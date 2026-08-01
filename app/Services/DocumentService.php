<?php

namespace App\Services;

use App\DTO\Document\ReplaceDocumentDTO;
use App\DTO\Document\UploadDocumentDTO;
use App\Enums\ActivityAction;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\User;
use App\Repositories\ActivityLogRepository;
use App\Repositories\DocumentRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DocumentService
{
    public function __construct(
        private readonly DocumentRepository $documentRepository,
        private readonly ActivityLogRepository $activityLogRepository,
    ) {}

    public function listForProject(string $projectId): Collection
    {
        return $this->documentRepository->forProject($projectId);
    }

    public function find(string $id): ProjectDocument
    {
        return $this->documentRepository->findOrFail($id)->load('uploader:id,name,email');
    }

    /**
     * @param  array<int, UploadDocumentDTO>  $dtos
     */
    public function upload(Project $project, array $dtos, User $user): Collection
    {
        return DB::transaction(function () use ($project, $dtos, $user): Collection {
            $documents = collect($dtos)
                ->map(fn (UploadDocumentDTO $dto): ProjectDocument => $this->storeFile($project, $dto->file, $user));

            $this->logActivity($project, ActivityAction::DocumentUploaded, $documents->count().' dokumen diunggah ke project '.$project->title.'.');

            return $documents;
        });
    }

    public function replace(ProjectDocument $document, ReplaceDocumentDTO $dto): ProjectDocument
    {
        return DB::transaction(function () use ($document, $dto): ProjectDocument {
            $this->storeFileReplacement($document, $dto->file);

            $this->logActivity(
                $document->project,
                ActivityAction::DocumentUploaded,
                'Dokumen '.$document->original_name.' pada project '.$document->project->title.' diperbarui.',
            );

            return $document->fresh()->load('uploader:id,name,email');
        });
    }

    public function delete(ProjectDocument $document): void
    {
        DB::transaction(function () use ($document): void {
            Storage::disk(config('documents.disk'))->delete($document->path);

            $this->documentRepository->delete($document->id);

            $this->logActivity(
                $document->project,
                ActivityAction::DocumentDeleted,
                'Dokumen '.$document->original_name.' pada project '.$document->project->title.' dihapus.',
            );
        });
    }

    private function storeFile(Project $project, UploadedFile $file, User $user): ProjectDocument
    {
        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $directory = config('documents.directory').'/'.$project->id;
        $storedName = Str::uuid().'.'.$extension;

        $path = $file->storeAs($directory, $storedName, config('documents.disk'));

        if ($path === false) {
            throw ValidationException::withMessages([
                'documents' => ['Gagal menyimpan berkas. Silakan coba lagi.'],
            ]);
        }

        return $this->documentRepository->create([
            'project_id' => $project->id,
            'uploaded_by' => $user->id,
            'name' => $originalName,
            'original_name' => $originalName,
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'extension' => $extension,
            'size' => $file->getSize(),
        ]);
    }

    private function storeFileReplacement(ProjectDocument $document, UploadedFile $file): void
    {
        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $directory = dirname($document->path);
        $storedName = Str::uuid().'.'.$extension;

        $path = $file->storeAs($directory, $storedName, config('documents.disk'));

        if ($path === false) {
            throw ValidationException::withMessages([
                'file' => ['Gagal menyimpan berkas. Silakan coba lagi.'],
            ]);
        }

        Storage::disk(config('documents.disk'))->delete($document->path);

        $this->documentRepository->update($document->id, [
            'name' => $originalName,
            'original_name' => $originalName,
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'extension' => $extension,
            'size' => $file->getSize(),
        ]);
    }

    private function logActivity(Project $project, ActivityAction $action, string $description): void
    {
        $this->activityLogRepository->create([
            'user_id' => auth()->id(),
            'project_id' => $project->id,
            'action' => $action,
            'description' => $description,
            'properties' => ['project_number' => $project->project_number],
        ]);
    }
}

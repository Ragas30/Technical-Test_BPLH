<?php

namespace App\Services;

use App\DTO\Document\ReplaceDocumentDTO;
use App\DTO\Document\UploadDocumentDTO;
use App\Enums\ActivityAction;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\User;
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
        private readonly ActivityLogService $activityLogService,
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
            $attributes = collect($dtos)
                ->map(fn (UploadDocumentDTO $dto): array => $this->storeFileAttributes($project, $dto->file, $user));

            $this->documentRepository->insertMany($attributes->all());

            $documents = $this->documentRepository->findManyWithUploader($attributes->pluck('id')->all());

            $this->activityLogService->record(ActivityAction::DocumentUploaded, $documents->count().' dokumen diunggah ke project '.$project->title.'.', project: $project);

            return $documents;
        });
    }

    public function replace(ProjectDocument $document, ReplaceDocumentDTO $dto): ProjectDocument
    {
        $document->loadMissing('project');

        return DB::transaction(function () use ($document, $dto): ProjectDocument {
            $this->storeFileReplacement($document, $dto->file);

            $this->activityLogService->record(
                ActivityAction::DocumentUploaded,
                'Dokumen '.$document->original_name.' pada project '.$document->project->title.' diperbarui.',
                project: $document->project,
            );

            return $document->fresh()->load('uploader:id,name,email');
        });
    }

    public function delete(ProjectDocument $document): void
    {
        $document->loadMissing('project');

        DB::transaction(function () use ($document): void {
            Storage::disk(config('documents.disk'))->delete($document->path);

            $this->documentRepository->delete($document->id);

            $this->activityLogService->record(
                ActivityAction::DocumentDeleted,
                'Dokumen '.$document->original_name.' pada project '.$document->project->title.' dihapus.',
                project: $document->project,
            );
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function storeFileAttributes(Project $project, UploadedFile $file, User $user): array
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

        return [
            'id' => (string) Str::uuid(),
            'project_id' => $project->id,
            'uploaded_by' => $user->id,
            'name' => $originalName,
            'original_name' => $originalName,
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'extension' => $extension,
            'size' => $file->getSize(),
        ];
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
}

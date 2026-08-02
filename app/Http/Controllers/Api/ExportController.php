<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Export\IndexProjectExportRequest;
use App\Http\Requests\Export\IndexReviewExportRequest;
use App\Services\ExportService;
use Dedoc\Scramble\Attributes\Response as DocsResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __construct(private readonly ExportService $exportService) {}

    #[DocsResponse(200, 'File Excel (.xlsx) berisi daftar project sesuai filter.', mediaType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', type: 'string', format: 'binary')]
    public function projects(IndexProjectExportRequest $request): StreamedResponse
    {
        return $this->exportService->projectsExcel(
            'projects-'.now()->format('Ymd-His').'.xlsx',
            $request->search(),
            $request->status(),
        );
    }

    #[DocsResponse(200, 'File PDF berisi daftar project sesuai filter.', mediaType: 'application/pdf', type: 'string', format: 'binary')]
    public function projectsPdf(IndexProjectExportRequest $request): Response
    {
        return $this->exportService->projectsPdf(
            'projects-'.now()->format('Ymd-His').'.pdf',
            $request->search(),
            $request->status(),
        );
    }

    #[DocsResponse(200, 'File Excel (.xlsx) berisi daftar review sesuai filter.', mediaType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', type: 'string', format: 'binary')]
    public function reviews(IndexReviewExportRequest $request): StreamedResponse
    {
        return $this->exportService->reviewsExcel(
            'reviews-'.now()->format('Ymd-His').'.xlsx',
            $request->search(),
            $request->status(),
        );
    }

    #[DocsResponse(200, 'File PDF berisi daftar review sesuai filter.', mediaType: 'application/pdf', type: 'string', format: 'binary')]
    public function reviewsPdf(IndexReviewExportRequest $request): Response
    {
        return $this->exportService->reviewsPdf(
            'reviews-'.now()->format('Ymd-His').'.pdf',
            $request->search(),
            $request->status(),
        );
    }
}

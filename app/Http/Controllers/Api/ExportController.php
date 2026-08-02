<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ExportService;
use Dedoc\Scramble\Attributes\Response as DocsResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __construct(private readonly ExportService $exportService) {}

    #[DocsResponse(200, 'File Excel (.xlsx) berisi daftar project sesuai filter.', mediaType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', type: 'string', format: 'binary')]
    public function projects(Request $request): StreamedResponse
    {
        return $this->exportService->projectsExcel(
            'projects-'.now()->format('Ymd-His').'.xlsx',
            $request->query('search'),
            $request->query('status'),
        );
    }

    #[DocsResponse(200, 'File PDF berisi daftar project sesuai filter.', mediaType: 'application/pdf', type: 'string', format: 'binary')]
    public function projectsPdf(Request $request): Response
    {
        return $this->exportService->projectsPdf(
            'projects-'.now()->format('Ymd-His').'.pdf',
            $request->query('search'),
            $request->query('status'),
        );
    }

    #[DocsResponse(200, 'File Excel (.xlsx) berisi daftar review sesuai filter.', mediaType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', type: 'string', format: 'binary')]
    public function reviews(Request $request): StreamedResponse
    {
        return $this->exportService->reviewsExcel(
            'reviews-'.now()->format('Ymd-His').'.xlsx',
            $request->query('search'),
            $request->query('status'),
        );
    }

    #[DocsResponse(200, 'File PDF berisi daftar review sesuai filter.', mediaType: 'application/pdf', type: 'string', format: 'binary')]
    public function reviewsPdf(Request $request): Response
    {
        return $this->exportService->reviewsPdf(
            'reviews-'.now()->format('Ymd-His').'.pdf',
            $request->query('search'),
            $request->query('status'),
        );
    }
}

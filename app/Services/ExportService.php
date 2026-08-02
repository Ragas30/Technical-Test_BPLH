<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Review;
use App\Repositories\ProjectRepository;
use App\Repositories\ReviewRepository;
use Dompdf\Dompdf;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    private const PROJECT_HEADERS = ['Nomor Project', 'Judul', 'Pemilik', 'Status', 'Diajukan', 'Dibuat'];

    private const REVIEW_HEADERS = ['Nomor Project', 'Judul', 'Reviewer', 'Status', 'Mulai', 'Diputuskan', 'Catatan'];

    public function __construct(
        private readonly ProjectRepository $projectRepository,
        private readonly ReviewRepository $reviewRepository,
    ) {}

    public function projectsExcel(string $filename, ?string $search = null, ?string $status = null): StreamedResponse
    {
        return $this->buildExcel($filename, self::PROJECT_HEADERS, fn ($size, $cb) => $this->projectRepository->chunkFiltered($search, $status, $size, $cb), $this->projectRow());
    }

    public function projectsPdf(string $filename, ?string $search = null, ?string $status = null): Response
    {
        return $this->buildPdf($filename, 'Daftar Project', self::PROJECT_HEADERS, $this->projectRows($search, $status));
    }

    public function reviewsExcel(string $filename, ?string $search = null, ?string $status = null): StreamedResponse
    {
        return $this->buildExcel($filename, self::REVIEW_HEADERS, fn ($size, $cb) => $this->reviewRepository->chunkFiltered($search, $status, $size, $cb), $this->reviewRow());
    }

    public function reviewsPdf(string $filename, ?string $search = null, ?string $status = null): Response
    {
        return $this->buildPdf($filename, 'Daftar Review', self::REVIEW_HEADERS, $this->reviewRows($search, $status));
    }

    /**
     * @return \Closure(Project): array<int, mixed>
     */
    private function projectRow(): \Closure
    {
        return fn (Project $project): array => [
            $project->project_number,
            $project->title,
            $project->user?->name ?? '-',
            $project->status->label(),
            $project->submitted_at?->format('d-m-Y H:i') ?? '-',
            $project->created_at?->format('d-m-Y H:i') ?? '-',
        ];
    }

    /**
     * @return \Closure(Review): array<int, mixed>
     */
    private function reviewRow(): \Closure
    {
        return fn (Review $review): array => [
            $review->project?->project_number ?? '-',
            $review->project?->title ?? '-',
            $review->reviewer?->name ?? '-',
            $review->status->label(),
            $review->created_at?->format('d-m-Y H:i') ?? '-',
            $review->reviewed_at?->format('d-m-Y H:i') ?? '-',
            $review->notes ?? '-',
        ];
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function projectRows(?string $search, ?string $status): array
    {
        return $this->projectRepository->filtered($search, $status)
            ->map($this->projectRow())
            ->all();
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function reviewRows(?string $search, ?string $status): array
    {
        return $this->reviewRepository->filtered($search, $status)
            ->map($this->reviewRow())
            ->all();
    }

    /**
     * @param  array<int, string>  $headers
     * @param  callable(int, callable): void  $chunked
     * @param  \Closure(mixed): array<int, mixed>  $row
     */
    private function buildExcel(string $filename, array $headers, callable $chunked, \Closure $row): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->fromArray($headers, null, 'A1');

        $rowNumber = 2;
        $rows = [];
        $flush = function () use (&$rows, $sheet, &$rowNumber): void {
            if ($rows === []) {
                return;
            }

            $sheet->fromArray($rows, null, 'A'.$rowNumber);
            $rowNumber += count($rows);
            $rows = [];
        };

        $chunked(100, function (Collection $models) use (&$rows, $flush, $row): void {
            foreach ($models as $model) {
                $rows[] = $row($model);

                if (count($rows) >= 100) {
                    $flush();
                }
            }
        });

        $flush();

        $headerRange = 'A1:'.$sheet->getHighestDataColumn().'1';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF2F2F2');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        foreach (range('A', $sheet->getHighestDataColumn()) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $sheet->freezePane('A2');

        $writer = new Xlsx($spreadsheet);

        return new StreamedResponse(function () use ($writer): void {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function buildPdf(string $filename, string $title, array $headers, array $rows): Response
    {
        $dompdf = new Dompdf;
        $dompdf->loadHtml(view('exports.table', compact('title', 'headers', 'rows'))->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}

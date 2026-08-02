<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <style>
            body {
                font-family: DejaVu Sans, sans-serif;
                font-size: 11px;
                color: #1f2937;
            }
            h1 {
                font-size: 18px;
                margin: 0 0 4px;
            }
            p {
                margin: 0 0 16px;
                color: #6b7280;
            }
            table {
                width: 100%;
                border-collapse: collapse;
            }
            th {
                background: #f3f4f6;
                text-align: left;
                padding: 8px;
                border: 1px solid #d1d5db;
            }
            td {
                padding: 6px 8px;
                border: 1px solid #d1d5db;
                vertical-align: top;
            }
        </style>
    </head>
    <body>
        <h1>{{ $title }}</h1>
        <p>Dibuat pada {{ now()->format('d-m-Y H:i') }}</p>
        <table>
            <thead>
                <tr>
                    @foreach ($headers as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        @foreach ($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($headers) }}">Tidak ada data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </body>
</html>

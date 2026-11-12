<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Rekap Nilai Capstone Design</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 32px;
            color: #1e293b;
            font-size: 14px;
        }

        .toolbar {
            margin-bottom: 24px;
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            border: none;
        }

        .btn-print {
            background: #4f46e5;
            color: #fff;
        }

        .btn-excel {
            background: #10b981;
            color: #fff;
        }

        .header-box {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        h2 {
            margin: 0 0 6px 0;
            font-size: 20px;
        }

        .meta {
            color: #475569;
            line-height: 1.6;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 12px;
        }

        th {
            background: #f1f5f9;
            color: #0f172a;
            font-weight: 600;
        }

        th,
        td {
            border: 1px solid #cbd5e1;
            padding: 10px 12px;
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .badge {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-sup {
            background: #e0e7ff;
            color: #3730a3;
        }

        .badge-exp {
            background: #fef3c7;
            color: #92400e;
        }

        @media print {
            .toolbar {
                display: none;
            }

            body {
                margin: 16px;
            }
        }
    </style>
</head>

<body>
    <div class="toolbar">
        <button class="btn btn-print" onclick="window.print()">Cetak / Simpan PDF</button>
        <a class="btn btn-excel" href="{{ route('capstone.dosen.scores.export', $group->id) }}">Unduh Excel (.xlsx)</a>
    </div>

    <div class="header-box">
        <h2>Rekap Nilai Capstone Design</h2>
        <div class="meta">
            <strong>Judul:</strong> {{ $group->title ?: 'Belum ditetapkan' }}<br>
            <strong>Pembimbing:</strong> {{ $group->supervisor?->nama_dosen ?? '-' }} &nbsp;|&nbsp;
            <strong>Penguji:</strong> {{ $group->examiner?->nama_dosen ?? '-' }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Mahasiswa</th>
                <th>Komponen Penilaian</th>
                <th>Kategori</th>
                <th>Penilai</th>
                <th class="text-right">Nilai</th>
            </tr>
        </thead>
        <tbody>
            @forelse($group->studentScores as $score)
            @php $cat = strtolower($score->component?->category ?? ''); @endphp
            <tr>
                <td><strong>{{ $score->student?->name }}</strong></td>
                <td>{{ $score->component?->name }}</td>
                <td>
                    <span class="badge {{ $cat === 'pembimbing' ? 'badge-sup' : 'badge-exp' }}">
                        {{ ucfirst($cat) }}
                    </span>
                </td>
                <td>{{ $score->evaluator?->nama_dosen }}</td>
                <td class="text-right"><strong>{{ number_format((float) $score->score, 2) }}</strong></td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align:center; color:#64748b;">Nilai belum tersedia.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
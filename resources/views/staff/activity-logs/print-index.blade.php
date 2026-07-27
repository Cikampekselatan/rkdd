<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Absen Pengajar SKUAD</title>
    <style>
        @page { size: A4 landscape; margin: 12mm; }
        * { box-sizing: border-box; }
        body { color: #111827; font-family: Arial, sans-serif; font-size: 10px; }
        button { margin-bottom: 12px; }
        header { display: flex; justify-content: space-between; gap: 16px; align-items: end; padding-bottom: 10px; margin-bottom: 12px; border-bottom: 3px solid #0f766e; }
        h1 { margin: 0 0 4px; font-size: 18px; text-transform: uppercase; }
        header p { margin: 0; color: #4b5563; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #9ca3af; padding: 6px; vertical-align: top; word-break: break-word; }
        th { background: #ecfdf5; font-size: 9px; text-align: left; text-transform: uppercase; }
        .no { width: 5%; text-align: center; }
        .date { width: 11%; }
        .teacher { width: 15%; }
        .material { width: 20%; }
        .activity { width: 28%; }
        .status { width: 10%; }
        .sign { width: 11%; text-align: center; }
        .sign img { display: block; width: 82px; max-height: 38px; object-fit: contain; margin: 0 auto 4px; }
        .footer { margin-top: 10px; color: #4b5563; }
        @media print { button { display: none; } }
    </style>
</head>
<body>
    <button onclick="window.print()">Cetak</button>
    <header>
        <div>
            <h1>Laporan Absen Pengajar SKUAD</h1>
            <p>{{ $selectedYear?->name ?? 'Semua tahun ajaran' }} · Dicetak {{ now()->translatedFormat('d F Y H:i') }}</p>
        </div>
        <p>{{ number_format($logs->count()) }} data tampil · Maksimal 500 baris</p>
    </header>
    <table>
        <thead>
            <tr>
                <th class="no">No</th>
                <th class="date">Tanggal</th>
                <th class="teacher">Pengajar</th>
                <th class="material">Materi</th>
                <th class="activity">Kegiatan & Penugasan</th>
                <th class="status">Status</th>
                <th class="sign">TTD Pengajar</th>
                <th class="sign">TTD Verifikator</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                <tr>
                    <td class="no">{{ $loop->iteration }}</td>
                    <td class="date">{{ $log->activity_date->translatedFormat('d F Y') }}</td>
                    <td class="teacher">{{ $log->teacher->name }}<br><small>{{ $log->teacher->roles->first()?->slug?->label() }}</small></td>
                    <td class="material">{{ $log->material }}</td>
                    <td class="activity">{!! nl2br(e($log->activities)) !!}<br><strong>Penugasan:</strong> {{ $log->assignment ?: '-' }}</td>
                    <td class="status">{{ $log->status->label() }}@if($log->verifier)<br><small>{{ $log->verifier->name }}</small>@endif</td>
                    <td class="sign">
                        @if($log->signature_path)<img src="{{ route('activity-logs.signature', $log) }}" alt="Tanda tangan pembina">@endif
                        {{ $log->teacher->name }}
                    </td>
                    <td class="sign">
                        @if($log->reviewer_signature_path)<img src="{{ route('activity-logs.signature', [$log, 'kind' => 'reviewer']) }}" alt="Tanda tangan verifikator">@endif
                        {{ $log->verifier?->name ?? '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="height: 42px; text-align: center;">Belum ada absen pengajar untuk filter ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <p class="footer">Dokumen ini dihasilkan dari SKUAD Learning Hub sesuai hak akses pengguna yang mencetak.</p>
</body>
</html>

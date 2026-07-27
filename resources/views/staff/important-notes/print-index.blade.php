<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>CATATAN PENTING ESKUL SKUAD</title>
    <style>
        @page { size: A4 portrait; margin: 14mm; }
        * { box-sizing: border-box; }
        body { color: #111827; font-family: Arial, sans-serif; font-size: 11px; }
        button { margin-bottom: 12px; }
        header { margin-bottom: 14px; text-align: center; }
        h1 { margin: 0; font-size: 18px; letter-spacing: .5px; text-transform: uppercase; }
        header p { margin: 6px 0 0; color: #4b5563; }
        .sheet { position: relative; min-height: 255mm; }
        .watermark { position: absolute; inset: 36mm 0 auto; z-index: -1; color: rgba(15, 118, 110, .05); font-size: 76px; font-weight: 800; letter-spacing: 8px; text-align: center; transform: rotate(-18deg); }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #111827; padding: 7px; vertical-align: top; }
        th { background: #f3f4f6; font-size: 10px; text-align: center; text-transform: uppercase; }
        td { min-height: 42px; line-height: 1.45; }
        .no { width: 7%; text-align: center; }
        .date { width: 16%; text-align: center; }
        .note { width: 27%; }
        .resolution { width: 25%; }
        .sign { width: 12.5%; text-align: center; }
        .sign img { display: block; width: 78px; max-height: 38px; object-fit: contain; margin: 0 auto 4px; }
        .muted { color: #6b7280; }
        .footer { display: flex; justify-content: space-between; margin-top: 10px; color: #4b5563; font-size: 10px; }
        @media print { button { display: none; } }
    </style>
</head>
<body>
    <button onclick="window.print()">Cetak</button>
    <main class="sheet">
        <div class="watermark">SKUAD</div>
        <header>
            <h1>CATATAN PENTING ESKUL SKUAD</h1>
            <p>{{ $selectedYear?->name ?? 'Semua tahun ajaran' }} · Dicetak {{ now()->translatedFormat('d F Y H:i') }}</p>
        </header>
        <table>
            <thead>
                <tr>
                    <th class="no">NO</th>
                    <th class="date">TANGGAL</th>
                    <th class="note">CATATAN KHUSUS</th>
                    <th class="resolution">PENYELESAIAN</th>
                    <th class="sign">PARAF INSTRUKTUR/COACH</th>
                    <th class="sign">PARAF GURU/PEMBINA</th>
                </tr>
            </thead>
            <tbody>
                @forelse($notes as $note)
                    <tr>
                        <td class="no">{{ $loop->iteration }}</td>
                        <td class="date">{{ $note->note_date->translatedFormat('d F Y') }}</td>
                        <td class="note">{!! nl2br(e($note->note)) !!}<br><span class="muted">{{ $note->priority->label() }} · {{ $note->status->label() }}</span></td>
                        <td class="resolution">{!! nl2br(e($note->resolution ?: '-')) !!}</td>
                        <td class="sign">
                            @if($note->coach_initial_path)<img src="{{ route('important-notes.initial', [$note, 'coach']) }}" alt="Paraf coach">@endif
                            {{ $note->coachInitialer?->name ?? '-' }}
                        </td>
                        <td class="sign">
                            @if($note->teacher_initial_path)<img src="{{ route('important-notes.initial', [$note, 'teacher']) }}" alt="Paraf pembina">@endif
                            {{ $note->teacherInitialer?->name ?? '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="height: 48px; text-align: center;">Belum ada catatan penting untuk filter ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="footer">
            <span>Dokumen ini dihasilkan dari SKUAD Learning Hub.</span>
            <span>Maksimal 500 baris per cetak.</span>
        </div>
    </main>
</body>
</html>

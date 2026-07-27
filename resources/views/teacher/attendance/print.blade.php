<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Daftar Hadir {{ $attendanceSession->learningSession->title }}</title>
    <style>
        @page { size: A4 landscape; margin: 12mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font: 11px/1.5 Arial, sans-serif; color: #102033; background: #f8fafc; }
        button { margin: 0 0 10px; padding: 9px 14px; border: 0; border-radius: 999px; background: #0f766e; color: white; font-weight: 700; }
        .sheet { background: white; min-height: 185mm; padding: 18px; border: 1px solid #dbe7ee; border-radius: 22px; position: relative; overflow: hidden; }
        .sheet::before { content: "SKUAD"; position: absolute; right: 24px; top: 70px; font-size: 92px; font-weight: 900; color: rgba(15, 118, 110, .055); letter-spacing: .12em; }
        header { display: grid; grid-template-columns: 1fr auto; gap: 16px; align-items: end; padding-bottom: 14px; border-bottom: 4px solid #0f766e; position: relative; z-index: 1; }
        small { color: #0f766e; text-transform: uppercase; font-weight: 800; letter-spacing: .08em; }
        h1 { margin: 4px 0; font-size: 24px; letter-spacing: -.03em; }
        header p, .meta p { margin: 0; color: #526477; }
        .score { text-align: right; }
        .score strong { display: block; font-size: 34px; color: #0f766e; line-height: 1; }
        .kpis { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; margin: 14px 0; position: relative; z-index: 1; }
        .kpis div { padding: 10px; border: 1px solid #dbe7ee; border-radius: 14px; background: #f8fafc; }
        .kpis b { display: block; font-size: 18px; }
        table { width: 100%; border-collapse: separate; border-spacing: 0; overflow: hidden; border: 1px solid #d6e4ea; border-radius: 16px; position: relative; z-index: 1; }
        th, td { padding: 8px 9px; border-bottom: 1px solid #e4edf2; text-align: left; vertical-align: top; }
        th { background: #e6fffb; color: #0f4f4a; text-transform: uppercase; font-size: 9px; letter-spacing: .06em; }
        tr:nth-child(even) td { background: #fbfdff; }
        tr:last-child td { border-bottom: 0; }
        .status { display: inline-block; padding: 4px 8px; border-radius: 999px; font-weight: 800; font-size: 10px; }
        .present { background: #dcfce7; color: #166534; }
        .late { background: #fef3c7; color: #92400e; }
        .sick { background: #e0f2fe; color: #075985; }
        .permitted { background: #ede9fe; color: #5b21b6; }
        .absent { background: #fee2e2; color: #991b1b; }
        footer { margin-top: 14px; display: flex; justify-content: space-between; gap: 20px; color: #526477; position: relative; z-index: 1; }
        @media print {
            body { background: white; }
            button { display: none; }
            .sheet { border: 0; border-radius: 0; padding: 0; }
        }
    </style>
</head>
<body>
    @php
        $printProgramContext = app(\App\Services\ProgramContextService::class);
        $participantLabel = $printProgramContext->participantLabel(auth()->user());
    @endphp
    <button onclick="window.print()">Cetak / Simpan PDF</button>
    <main class="sheet">
        <header>
            <div>
                <small>Daftar Hadir {{ $participantLabel }} · RKDD Cikampek Selatan</small>
                <h1>{{ $attendanceSession->learningSession->title }}</h1>
                <p>Pertemuan {{ $attendanceSession->learningSession->session_number }} · {{ $attendanceSession->schoolClass->name }} · {{ $attendanceSession->academicYear->name }}</p>
                <p>{{ $attendanceSession->attendance_date->translatedFormat('l, d F Y') }} · Dibuka oleh {{ $attendanceSession->opener?->name ?? 'Sistem' }}</p>
            </div>
            <div class="score">
                <strong>{{ $summary['percentage'] }}%</strong>
                <p>hadir + terlambat</p>
            </div>
        </header>

        <section class="kpis" aria-label="Ringkasan presensi">
            @foreach($statuses as $status)
                <div>
                    <small>{{ $status->label() }}</small>
                    <b>{{ $summary['counts'][$status->value] }}</b>
                </div>
            @endforeach
        </section>

        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama {{ strtolower($participantLabel) }}</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Check-in</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attendanceSession->records as $record)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><strong>{{ $record->student->name }}</strong></td>
                        <td>{{ $record->student->email }}</td>
                        <td><span class="status {{ $record->status->value }}">{{ $record->status->label() }}</span></td>
                        <td>{{ $record->checked_in_at ? $record->checked_in_at->translatedFormat('H:i') : '-' }} {{ $record->check_in_method ? '('.strtoupper($record->check_in_method).')' : '' }}</td>
                        <td>{{ $record->notes ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">Belum ada {{ strtolower($participantLabel) }} pada sesi ini.</td></tr>
                @endforelse
            </tbody>
        </table>

        <footer>
            <span>Status sesi: {{ $attendanceSession->status->label() }}</span>
            <span>Dicetak {{ now()->translatedFormat('d F Y H:i') }}</span>
        </footer>
    </main>
</body>
</html>

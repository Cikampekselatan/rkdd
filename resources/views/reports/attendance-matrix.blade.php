<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Matriks Absensi Ekstrakurikuler</title>
    <style>
        @page { size: A4 landscape; margin: 9mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font: 9px/1.35 "Arial", sans-serif; color: #102033; background: #eef6f8; }
        button { margin: 0 0 10px; padding: 9px 14px; border: 0; border-radius: 999px; background: #0f766e; color: #fff; font-weight: 800; cursor: pointer; }
        .sheet { position: relative; min-height: 190mm; overflow: hidden; background: linear-gradient(135deg, #fff 0%, #f8fffe 100%); border: 1px solid #cfe2e8; border-radius: 24px; padding: 15px; box-shadow: 0 22px 70px rgba(15, 23, 42, .10); }
        .sheet::before { content: "RKDD"; position: absolute; inset: 56px 0 auto 0; text-align: center; font-size: 116px; line-height: 1; letter-spacing: .18em; font-weight: 900; color: rgba(15, 118, 110, .045); transform: translateX(3%); pointer-events: none; }
        .sheet::after { content: ""; position: absolute; right: -60px; bottom: -80px; width: 260px; height: 260px; border-radius: 999px; background: radial-gradient(circle, rgba(20, 184, 166, .16), transparent 68%); pointer-events: none; }
        header { position: relative; z-index: 1; display: grid; grid-template-columns: 1fr auto; gap: 12px; align-items: end; padding-bottom: 11px; border-bottom: 4px solid #0f766e; }
        .eyebrow { margin: 0; color: #0f766e; text-transform: uppercase; font-weight: 900; letter-spacing: .12em; }
        h1 { margin: 3px 0; font-size: 20px; letter-spacing: -.02em; text-transform: uppercase; text-align: left; }
        header p { margin: 0; color: #526477; }
        .meta { text-align: right; }
        .meta strong { display: block; font-size: 25px; color: #0f766e; line-height: 1; }
        .legend { position: relative; z-index: 1; display: flex; flex-wrap: wrap; gap: 6px; justify-content: space-between; align-items: center; margin: 9px 0 10px; }
        .legend-group { display: flex; flex-wrap: wrap; gap: 5px; }
        .pill { display: inline-flex; align-items: center; gap: 4px; padding: 4px 7px; border-radius: 999px; border: 1px solid #dbe7ee; background: #fff; font-weight: 800; }
        .dot { width: 9px; height: 9px; border-radius: 999px; display: inline-block; }
        .present { background: #dcfce7; color: #166534; }
        .late { background: #fef3c7; color: #92400e; }
        .sick { background: #e0f2fe; color: #075985; }
        .permitted { background: #ede9fe; color: #5b21b6; }
        .absent { background: #fee2e2; color: #991b1b; }
        .empty { background: #f8fafc; color: #94a3b8; }
        table { position: relative; z-index: 1; width: 100%; border-collapse: collapse; table-layout: fixed; background: rgba(255,255,255,.91); border: 1px solid #0f172a; }
        th, td { border: 1px solid rgba(15, 23, 42, .72); padding: 3px 4px; vertical-align: middle; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        th { background: #d8e2f3; font-weight: 900; text-align: center; }
        thead .meeting-label { background: #d8e2f3; font-size: 11px; }
        thead .meeting-number { background: #f7ff00; color: #111827; width: 20px; }
        .col-no { width: 28px; text-align: center; }
        .col-name { width: 170px; }
        .col-class { width: 65px; }
        tbody tr:nth-child(even) td:not(.mark) { background: rgba(248,250,252,.86); }
        .mark { width: 20px; height: 18px; text-align: center; font-size: 9px; font-weight: 900; letter-spacing: -.02em; }
        .mark.empty { background: rgba(248,250,252,.62); }
        .mark.present { background: #bbf7d0; }
        .mark.late { background: #fde68a; }
        .mark.sick { background: #bae6fd; }
        .mark.permitted { background: #ddd6fe; }
        .mark.absent { background: #fecaca; }
        footer { position: relative; z-index: 1; display: flex; justify-content: space-between; gap: 12px; margin-top: 9px; color: #526477; }
        @media print {
            body { background: #fff; }
            button { display: none; }
            .sheet { border: 0; border-radius: 0; padding: 0; box-shadow: none; }
        }
    </style>
</head>
<body>
    <button onclick="window.print()">Cetak / Simpan PDF</button>
    <main class="sheet">
        <header>
            <div>
                <p class="eyebrow">Absensi Program</p>
                <h1>Matriks Kehadiran {{ $participantLabel ?? 'Peserta' }}</h1>
                <p>{{ $selectedYear?->name ?? 'Periode tidak tersedia' }} · {{ $selectedClass?->name ?? 'Semua kelompok' }} · {{ $filters['semester'] ? 'Semester '.$filters['semester'] : 'Semua semester' }}</p>
            </div>
            <div class="meta">
                <strong>{{ $activeStudentCount }}</strong>
                <p>{{ strtolower($participantLabel ?? 'peserta') }} aktif · {{ $sessions->count() }} pertemuan</p>
            </div>
        </header>

        <section class="legend">
            <div class="legend-group">
                <span class="pill"><span class="dot present"></span>H Hadir</span>
                <span class="pill"><span class="dot late"></span>T Terlambat</span>
                <span class="pill"><span class="dot sick"></span>S Sakit</span>
                <span class="pill"><span class="dot permitted"></span>I Izin</span>
                <span class="pill"><span class="dot absent"></span>A Alpa</span>
                <span class="pill"><span class="dot empty"></span>- Belum tercatat</span>
            </div>
            <span>Dicetak {{ now()->translatedFormat('d F Y H:i') }}</span>
        </section>

        <table>
            <thead>
                <tr>
                    <th class="col-no" rowspan="2">No.</th>
                    <th class="col-name" rowspan="2">Nama</th>
                    <th class="col-class" rowspan="2">{{ $groupLabel ?? 'Kelompok' }}</th>
                    <th class="meeting-label" colspan="{{ max(1, $sessions->count()) }}">Pertemuan Ke</th>
                </tr>
                <tr>
                    @forelse($sessions as $session)
                        <th class="meeting-number" title="{{ $session->title }}">{{ $session->session_number }}</th>
                    @empty
                        <th class="meeting-number">-</th>
                    @endforelse
                </tr>
            </thead>
            <tbody>
                @forelse($students as $student)
                    @php
                        $membership = $student->classMemberships->first();
                        $studentRecords = $records->get($student->id, collect());
                    @endphp
                    <tr>
                        <td class="col-no">{{ $loop->iteration }}</td>
                        <td class="col-name">{{ $student->name }}</td>
                        <td class="col-class">{{ $membership?->schoolClass?->name ?? '-' }}</td>
                        @forelse($sessions as $session)
                            @php
                                $record = $studentRecords->firstWhere('attendanceSession.learning_session_id', $session->id);
                                $status = $record?->status?->value ?? 'empty';
                                $symbol = match($status) {
                                    'present' => 'H',
                                    'late' => 'T',
                                    'sick' => 'S',
                                    'permitted' => 'I',
                                    'absent' => 'A',
                                    default => '',
                                };
                            @endphp
                            <td class="mark {{ $status }}" title="Pertemuan {{ $session->session_number }} · {{ $session->title }}">{{ $symbol }}</td>
                        @empty
                            <td class="mark empty" title="Belum ada pertemuan"></td>
                        @endforelse
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 3 + max(1, $sessions->count()) }}">Belum ada {{ strtolower($participantLabel ?? 'peserta') }} untuk filter ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <footer>
            <span>Dokumen ini dihasilkan otomatis dari data presensi aplikasi.</span>
            <span>Filter tanggal: {{ $filters['date_from'] ?: 'awal' }} s.d. {{ $filters['date_to'] ?: 'sekarang' }}</span>
        </footer>
    </main>
</body>
</html>

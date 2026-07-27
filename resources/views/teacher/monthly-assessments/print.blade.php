<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Ekspor Asesmen Bulanan RKDD</title>
    <style>
        @page { size: A4 landscape; margin: 11mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #102033; background: #f4f7fb; font: 10px/1.45 Arial, sans-serif; }
        button { margin: 12px; }
        .sheet { min-height: 188mm; padding: 14px; background: #fff; border-radius: 18px; box-shadow: 0 20px 60px rgb(15 23 42 / 14%); }
        header { display: grid; grid-template-columns: minmax(0,1fr) auto; gap: 18px; align-items: end; padding: 18px; color: #fff; background: radial-gradient(circle at 92% 0, rgb(34 211 238 / 35%), transparent 18rem), linear-gradient(135deg, #071827, #0f766e); border-radius: 18px; }
        .brand { display: inline-grid; width: 42px; height: 42px; margin-bottom: 8px; color: #071827; background: linear-gradient(135deg,#facc15,#22d3ee); border-radius: 14px; font-weight: 900; place-items: center; }
        h1 { margin: 0; color: #fff; font-size: 22px; letter-spacing: -.4px; }
        header p, header small { margin: 0; color: rgb(255 255 255 / 78%); }
        .meta { display: grid; gap: 6px; min-width: 210px; padding: 12px; background: rgb(255 255 255 / 12%); border: 1px solid rgb(255 255 255 / 16%); border-radius: 14px; }
        .meta strong { color: #fff; font-size: 18px; }
        .summary { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; margin: 12px 0; }
        .summary article { padding: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; }
        .summary strong { display: block; color: #0f766e; font-size: 18px; }
        .summary span { color: #64748b; font-weight: 700; }
        table { width: 100%; border-collapse: separate; border-spacing: 0; overflow: hidden; border: 1px solid #dbe4ee; border-radius: 14px; }
        th, td { padding: 7px; text-align: left; vertical-align: top; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; word-break: break-word; }
        th { color: #0f172a; background: #e6fffb; font-size: 8.5px; text-transform: uppercase; letter-spacing: .35px; }
        td { background: #fff; }
        tbody tr:nth-child(even) td { background: #f8fafc; }
        tr > *:last-child { border-right: 0; }
        tbody tr:last-child td { border-bottom: 0; }
        .student { width: 14%; }
        .period { width: 9%; }
        .score { width: 7%; text-align: center; }
        .wide { width: 18%; }
        .badge { display: inline-block; padding: 3px 7px; color: #0f766e; background: #ccfbf1; border-radius: 999px; font-weight: 900; white-space: nowrap; }
        .footer { display: flex; justify-content: space-between; gap: 12px; margin-top: 10px; color: #64748b; }
        @media print {
            body { background: #fff; }
            button { display: none; }
            .sheet { padding: 0; border-radius: 0; box-shadow: none; }
        }
    </style>
</head>
<body>
    <button onclick="window.print()">Cetak / Simpan PDF</button>
    <main class="sheet">
        <header>
            <div>
                <span class="brand">S</span>
                <small>RKDD LEARNING HUB · ASESMEN BULANAN</small>
                <h1>Laporan Hasil Asesmen Peserta</h1>
                <p>{{ $academicYear?->name ?? 'Periode tidak tersedia' }} · {{ $class?->name ?? 'Semua kelompok' }} · Semester {{ $semester }}</p>
            </div>
            <div class="meta">
                <span>Total asesmen</span>
                <strong>{{ number_format($assessments->count()) }}</strong>
                <small>Dicetak {{ now()->translatedFormat('d F Y H:i') }}</small>
            </div>
        </header>

        <section class="summary">
            @foreach($components as $component)
                <article><strong>{{ $component['weight'] }}%</strong><span>{{ $component['label'] }}</span></article>
            @endforeach
        </section>

        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th class="student">Peserta</th>
                    <th class="period">Periode</th>
                    <th class="score">Produk</th>
                    <th class="score">Proses</th>
                    <th class="score">Kolab.</th>
                    <th class="score">Presentasi</th>
                    <th class="score">Etika</th>
                    <th class="score">Akhir</th>
                    <th>Level</th>
                    <th class="wide">Kekuatan</th>
                    <th class="wide">Target Perbaikan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($assessments as $assessment)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><strong>{{ $assessment->student->name }}</strong><br><small>{{ $assessment->student->email }}</small></td>
                        <td>{{ $assessment->period_label }}</td>
                        <td class="score">{{ number_format((float) $assessment->product_portfolio_score, 0) }}</td>
                        <td class="score">{{ number_format((float) $assessment->process_creativity_score, 0) }}</td>
                        <td class="score">{{ number_format((float) $assessment->collaboration_responsibility_score, 0) }}</td>
                        <td class="score">{{ number_format((float) $assessment->presentation_communication_score, 0) }}</td>
                        <td class="score">{{ number_format((float) $assessment->ethics_security_reflection_score, 0) }}</td>
                        <td class="score"><strong>{{ number_format((float) $assessment->final_score, 2) }}</strong></td>
                        <td><span class="badge">Level {{ $assessment->achievement_level }}</span><br>{{ \App\Models\MonthlyStudentAssessment::achievementLabel($assessment->achievement_level) }}</td>
                        <td>{{ $assessment->strengths ?: '-' }}</td>
                        <td>{{ $assessment->improvement_targets ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="12" style="padding: 24px; text-align: center;">Belum ada asesmen untuk filter ini.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="footer">
            <span>Dokumen premium ini dihasilkan dari data RKDD Learning Hub.</span>
            <span>Maksimal 500 baris per tampilan cetak.</span>
        </div>
    </main>
</body>
</html>

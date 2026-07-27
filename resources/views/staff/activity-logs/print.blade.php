<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Absen Pengajar {{ $log->log_number }}</title>
    <style>
        @page{size:A4;margin:18mm}body{font-family:Arial,sans-serif;color:#17202a}header{text-align:center;border-bottom:3px solid #123654;margin-bottom:24px}h1{font-size:20px}table{width:100%;border-collapse:collapse}td{padding:10px;border:1px solid #bbb;vertical-align:top}.label{width:25%;font-weight:bold;background:#f4f6f8}.signature{display:grid;grid-template-columns:1fr 1fr;gap:50px;margin-top:45px;text-align:center}.signature img{display:block;width:150px;max-height:75px;object-fit:contain;margin:8px auto}@media print{button{display:none}}
    </style>
</head>
<body>
<button onclick="window.print()">Cetak</button>
<header><h1>ABSEN PENGAJAR SKUAD</h1><p>SMP IT Mentari Ilmu Jatisari · {{ $log->academicYear->name }}</p></header>
@php($creatorRole = $log->teacher->roles->first()?->slug?->label() ?? 'Pengajar')
<table>
    <tr><td class="label">Nomor</td><td>{{ str_pad($log->log_number,3,'0',STR_PAD_LEFT) }}</td></tr>
    <tr><td class="label">Tanggal</td><td>{{ $log->activity_date->translatedFormat('d F Y') }}</td></tr>
    <tr><td class="label">Pengajar</td><td>{{ $log->teacher->name }} · {{ $creatorRole }}</td></tr>
    <tr><td class="label">Materi</td><td>{{ $log->material }}</td></tr>
    <tr><td class="label">Kegiatan</td><td>{!! nl2br(e($log->activities)) !!}</td></tr>
    <tr><td class="label">Penugasan</td><td>{{ $log->assignment ?: '-' }}</td></tr>
    <tr><td class="label">Status</td><td>{{ $log->status->label() }}</td></tr>
</table>
<div class="signature">
    <div>
        <p>{{ $creatorRole }},</p>
        @if($log->signature_path)<img src="{{ route('activity-logs.signature',$log) }}" alt="Tanda tangan pengajar">@else<br><br>@endif
        <strong>{{ $log->teacher->name }}</strong>
    </div>
    <div>
        <p>Guru/Pembina Verifikator,</p>
        @if($log->reviewer_signature_path)<img src="{{ route('activity-logs.signature',[$log,'kind'=>'reviewer']) }}" alt="Tanda tangan verifikator">@else<br><br>@endif
        <strong>{{ $log->verifier?->name ?? 'Belum diverifikasi' }}</strong>
    </div>
</div>
</body>
</html>

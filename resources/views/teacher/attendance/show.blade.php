@extends('layouts.dashboard')

@php
    $attendanceDetailContext = app(\App\Services\ProgramContextService::class);
    $participantLabel = $attendanceDetailContext->participantLabel(auth()->user());
@endphp

@section('title', 'Isi Presensi - RKDD')
@section('breadcrumb', 'Detail presensi')

@section('content')
    <div class="attendance-page attendance-entry-page">
        <div class="attendance-detail-hero">
            <a href="{{ route('teacher.attendance.index', ['academic_year_id' => $attendanceSession->academic_year_id, 'class_id' => $attendanceSession->class_id]) }}" class="attendance-back"><i class="bi bi-arrow-left"></i> Kembali ke rekap</a>
            <div class="d-flex flex-wrap gap-2 justify-content-end mb-3">
                <a class="btn btn-light skuad-touch-button" href="{{ route('teacher.attendance.export.csv', $attendanceSession) }}"><i class="bi bi-filetype-csv"></i> Export CSV</a>
                <a class="btn btn-outline-light skuad-touch-button" href="{{ route('teacher.attendance.print', $attendanceSession) }}" target="_blank"><i class="bi bi-file-earmark-pdf"></i> Cetak / PDF</a>
            </div>
            <div class="attendance-detail-grid">
                <div>
                    <p class="skuad-eyebrow">Pertemuan {{ $attendanceSession->learningSession->session_number }} · {{ $attendanceSession->schoolClass->name }}</p>
                    <h1>{{ $attendanceSession->learningSession->title }}</h1>
                    <p>{{ $attendanceSession->attendance_date->translatedFormat('l, d F Y') }} · Dibuka oleh {{ $attendanceSession->opener?->name ?? 'Sistem' }}</p>
                </div>
                <div class="attendance-live-score"><span>{{ $summary['percentage'] }}%</span><small>hadir + terlambat</small><em class="{{ $attendanceSession->isOpen() ? 'is-open' : 'is-closed' }}">{{ $attendanceSession->status->label() }}</em></div>
            </div>
        </div>

        @if (session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif

        <div class="attendance-count-strip" aria-label="Ringkasan status">
            @foreach ($statuses as $status)
                <div class="attendance-count attendance-tone-{{ $status->value }}"><i class="bi {{ $status->icon() }}"></i><span><strong>{{ $summary['counts'][$status->value] }}</strong><small>{{ $status->label() }}</small></span></div>
            @endforeach
        </div>

        @if ($attendanceSession->isOpen())
            @php($checkInUrl = $attendanceSession->checkInUrl())
            <section class="attendance-checkin-card">
                <div class="attendance-checkin-copy">
                    <span><i class="bi bi-qr-code"></i></span>
                    <div>
                        <p class="skuad-eyebrow">QR presensi {{ strtolower($participantLabel) }}</p>
                        <h2>Scan untuk check-in mandiri</h2>
                        <p>Semua {{ strtolower($participantLabel) }} aktif sudah masuk daftar presensi. Instruktur/guru dapat mengisi manual untuk sakit, izin, atau peserta yang tidak bisa scan QR.</p>
                    </div>
                </div>
                <div class="attendance-checkin-panel">
                    @if ($attendanceSession->hasActiveCheckIn() && $checkInUrl)
                        <div class="attendance-qr-frame">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=12&data={{ rawurlencode($checkInUrl) }}" alt="QR presensi {{ $attendanceSession->learningSession->title }}">
                        </div>
                        <div class="attendance-checkin-meta">
                            <strong>Aktif sampai {{ $attendanceSession->check_in_expires_at?->translatedFormat('H:i') }} WIB</strong>
                            <small>{{ $attendanceSession->records->whereNotNull('checked_in_at')->count() }} dari {{ $attendanceSession->records->count() }} {{ strtolower($participantLabel) }} sudah check-in.</small>
                            <input class="form-control" value="{{ $checkInUrl }}" readonly onclick="this.select()">
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-outline-primary skuad-touch-button" href="{{ $checkInUrl }}" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i> Buka link</a>
                                <form method="POST" action="{{ route('teacher.attendance.check-in.disable', $attendanceSession) }}">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-outline-danger skuad-touch-button" type="submit"><i class="bi bi-slash-circle"></i> Nonaktifkan</button>
                                </form>
                            </div>
                            <small class="text-secondary">Jika gambar QR tidak tampil karena internet lokal, siswa tetap bisa memakai link di atas.</small>
                        </div>
                    @else
                        <div class="attendance-checkin-empty">
                            <i class="bi bi-upc-scan"></i>
                            <strong>QR belum aktif</strong>
                            <small>Aktifkan QR ketika siswa sudah siap scan di kelas.</small>
                        </div>
                    @endif
                    <form method="POST" action="{{ route('teacher.attendance.check-in.enable', $attendanceSession) }}" class="attendance-checkin-form">
                        @csrf @method('PATCH')
                        <label class="form-label" for="minutes">Durasi QR</label>
                        <div class="input-group">
                            <select class="form-select" id="minutes" name="minutes">
                                <option value="15">15 menit</option>
                                <option value="30" selected>30 menit</option>
                                <option value="60">60 menit</option>
                                <option value="90">90 menit</option>
                            </select>
                            <button class="btn btn-primary" type="submit"><i class="bi bi-arrow-clockwise"></i> {{ $attendanceSession->check_in_token_hash ? 'Perbarui QR' : 'Aktifkan QR' }}</button>
                        </div>
                    </form>
                </div>
            </section>

            <div class="attendance-bulk-bar">
                <span>Tandai semua:</span>
                @foreach ($statuses as $status)
                    <button type="button" class="attendance-bulk-button attendance-tone-{{ $status->value }}" data-attendance-mark-all="{{ $status->value }}"><i class="bi {{ $status->icon() }}"></i> {{ $status->label() }}</button>
                @endforeach
            </div>

            <form method="POST" action="{{ route('teacher.attendance.update', $attendanceSession) }}" data-attendance-form>
                @csrf @method('PUT')
                <div class="attendance-student-list">
                    @foreach ($attendanceSession->records as $index => $record)
                        <article class="attendance-entry-card">
                            <div class="attendance-student-identity">
                                <x-ui.avatar :name="$record->student->name" size="md" />
                                <div><span>{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><h2>{{ $record->student->name }}</h2><p>{{ $record->student->email }} @if($record->checked_in_at) Â· Check-in {{ $record->checked_in_at->translatedFormat('H:i') }} @endif</p></div>
                            </div>
                            <input type="hidden" name="records[{{ $index }}][user_id]" value="{{ $record->user_id }}">
                            <div class="attendance-status-options" role="radiogroup" aria-label="Status {{ $record->student->name }}">
                                @foreach ($statuses as $status)
                                    @php($checked = old("records.$index.status", $record->status->value) === $status->value)
                                    <label class="attendance-status-option attendance-tone-{{ $status->value }}">
                                        <input type="radio" name="records[{{ $index }}][status]" value="{{ $status->value }}" @checked($checked) required>
                                        <span><i class="bi {{ $status->icon() }}"></i><small>{{ $status->label() }}</small></span>
                                    </label>
                                @endforeach
                            </div>
                            <div class="attendance-note-field">
                                <label for="note-{{ $record->id }}">Catatan siswa</label>
                                <input class="form-control" id="note-{{ $record->id }}" name="records[{{ $index }}][notes]" value="{{ old("records.$index.notes", $record->notes) }}" maxlength="1000" placeholder="Opsional, misalnya datang pukul 14.10">
                            </div>
                        </article>
                    @endforeach
                </div>
                <div class="attendance-sticky-actions">
                    <div><strong>{{ $attendanceSession->records->count() }} {{ strtolower($participantLabel) }} aktif</strong><small>QR dan input manual dapat digabung sebelum sesi ditutup</small></div>
                    <button class="btn btn-primary skuad-touch-button" type="submit"><i class="bi bi-cloud-check"></i> Simpan presensi</button>
                </div>
            </form>

            <form method="POST" action="{{ route('teacher.attendance.close', $attendanceSession) }}" class="attendance-close-card" onsubmit="return confirm('Tutup sesi presensi? Perubahan berikutnya wajib memakai koreksi ber-audit.')">
                @csrf @method('PATCH')
                <div><i class="bi bi-lock"></i><span><strong>Finalkan presensi</strong><small>Pastikan semua status dan catatan sudah benar.</small></span></div>
                <button class="btn btn-outline-danger skuad-touch-button" type="submit">Tutup sesi</button>
            </form>
        @else
            <div class="attendance-closed-notice"><i class="bi bi-shield-lock-fill"></i><div><strong>Sesi ditutup {{ $attendanceSession->closed_at?->diffForHumans() }}</strong><p>Data final terkunci. Guru masih dapat membuat koreksi dengan alasan; seluruh perubahan disimpan dalam audit.</p></div></div>
            <div class="attendance-student-list">
                @foreach ($attendanceSession->records as $record)
                    <article class="attendance-entry-card attendance-entry-closed">
                        <div class="attendance-student-identity">
                            <x-ui.avatar :name="$record->student->name" size="md" />
                            <div><span>{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><h2>{{ $record->student->name }}</h2><p>{{ $record->notes ?: 'Tanpa catatan' }}</p></div>
                        </div>
                        <span class="attendance-final-status attendance-tone-{{ $record->status->value }}"><i class="bi {{ $record->status->icon() }}"></i> {{ $record->status->label() }}</span>
                        <button class="btn btn-outline-primary skuad-touch-button" type="button" data-bs-toggle="collapse" data-bs-target="#amend-{{ $record->id }}"><i class="bi bi-pencil-square"></i> Koreksi</button>
                        <div class="collapse attendance-amend-form" id="amend-{{ $record->id }}">
                            <form method="POST" action="{{ route('teacher.attendance.records.amend', $record) }}" class="row g-3">
                                @csrf @method('PATCH')
                                <div class="col-md-4"><label class="form-label">Status baru</label><select class="form-select" name="status">@foreach($statuses as $status)<option value="{{ $status->value }}" @selected($record->status === $status)>{{ $status->label() }}</option>@endforeach</select></div>
                                <div class="col-md-8"><label class="form-label">Catatan siswa</label><input class="form-control" name="notes" value="{{ $record->notes }}" maxlength="1000"></div>
                                <div class="col-12"><label class="form-label">Alasan koreksi <span class="text-danger">*</span></label><textarea class="form-control" name="reason" rows="2" minlength="5" maxlength="1000" required placeholder="Jelaskan dasar koreksi agar audit mudah ditinjau."></textarea></div>
                                <div class="col-12 d-flex justify-content-between align-items-center"><small class="text-secondary">{{ $record->logs->count() }} perubahan tercatat</small><button class="btn btn-primary skuad-touch-button" type="submit">Simpan koreksi</button></div>
                            </form>
                            @if ($record->logs->isNotEmpty())
                                <details class="attendance-audit"><summary>Lihat riwayat audit</summary>@foreach($record->logs->sortByDesc('created_at') as $log)<div><strong>{{ $log->actor?->name ?? 'Sistem' }}</strong><span>{{ $log->old_status?->label() ?? '-' }} → {{ $log->new_status?->label() ?? '-' }}</span><small>{{ $log->reason ?: 'Penyimpanan presensi' }} · {{ $log->created_at->translatedFormat('d M Y H:i') }}</small></div>@endforeach</details>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
@endsection

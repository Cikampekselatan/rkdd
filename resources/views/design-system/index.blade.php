@extends('layouts.dashboard')

@section('title', 'Design System - SKUAD Learning Hub')
@section('breadcrumb', 'Design System')

@section('content')
    <div class="design-system-page">
        <x-ui.page-header
            eyebrow="Development only"
            title="SKUAD Design System"
            description="Fondasi visual premium yang tenang, konsisten, responsif, dan siap digunakan lintas role."
        >
            <x-slot:actions>
                <x-ui.button variant="outline" icon="bi-funnel" data-bs-toggle="offcanvas" data-bs-target="#filterCanvas">
                    Filter
                </x-ui.button>
                <x-ui.button icon="bi-stars" data-toast-demo>
                    Tampilkan toast
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <div class="row g-3">
            <div class="col-12 col-sm-6 col-xxl-3">
                <x-ui.stat-card label="Siswa aktif" value="128" icon="bi-people" tone="teal" trend="12% bulan ini" />
            </div>
            <div class="col-12 col-sm-6 col-xxl-3">
                <x-ui.stat-card label="Pertemuan selesai" value="18/30" icon="bi-journal-check" tone="navy" trend="3 sesi terbaru" />
            </div>
            <div class="col-12 col-sm-6 col-xxl-3">
                <x-ui.stat-card label="Tugas direvisi" value="24" icon="bi-arrow-repeat" tone="orange" trend="4 perlu perhatian" trend-direction="down" />
            </div>
            <div class="col-12 col-sm-6 col-xxl-3">
                <x-ui.stat-card label="Kehadiran" value="94%" icon="bi-activity" tone="cyan" trend="2,4% lebih baik" />
            </div>
        </div>

        <section class="design-section" aria-labelledby="foundations-title">
            <div class="design-section-header">
                <div>
                    <h2 id="foundations-title">Fondasi visual</h2>
                    <p>Warna utama dan hierarki tipografi SKUAD.</p>
                </div>
                <span class="design-index">01 / FOUNDATIONS</span>
            </div>

            <div class="color-grid mb-4">
                @foreach ([
                    ['Navy 950', '#071827'],
                    ['Navy 900', '#0B2239'],
                    ['Teal 600', '#0F9F96'],
                    ['Teal 500', '#14B8A6'],
                    ['Cyan 400', '#22D3EE'],
                    ['Orange 500', '#F59E0B'],
                ] as [$name, $hex])
                    <div class="color-swatch">
                        <div class="color-swatch-preview" style="--swatch: {{ $hex }}"></div>
                        <div class="color-swatch-copy"><strong>{{ $name }}</strong><code>{{ $hex }}</code></div>
                    </div>
                @endforeach
            </div>

            <div class="row g-4 align-items-end">
                <div class="col-lg-7">
                    <p class="skuad-eyebrow">Display / Plus Jakarta Sans</p>
                    <p class="type-display mb-0">Karya punya cerita.</p>
                </div>
                <div class="col-lg-5">
                    <p class="type-heading mb-2">Belajar digital yang manusiawi.</p>
                    <p class="type-body mb-0">Tipografi tegas pada heading, ringan pada body, dan tetap nyaman dibaca oleh siswa pada layar kecil.</p>
                </div>
            </div>
        </section>

        <section class="design-section" aria-labelledby="actions-title">
            <div class="design-section-header">
                <div>
                    <h2 id="actions-title">Tombol dan badge</h2>
                    <p>Aksi utama selalu jelas, status tetap mudah dipindai.</p>
                </div>
                <span class="design-index">02 / ACTIONS</span>
            </div>

            <div class="component-row mb-4">
                <x-ui.button icon="bi-plus-lg">Tambah data</x-ui.button>
                <x-ui.button variant="secondary" icon="bi-lightning-charge">Aksi cepat</x-ui.button>
                <x-ui.button variant="outline" icon="bi-download">Unduh</x-ui.button>
                <x-ui.button variant="ghost" icon="bi-three-dots">Lainnya</x-ui.button>
                <x-ui.button variant="danger" icon="bi-trash" data-bs-toggle="modal" data-bs-target="#confirmModal">Hapus</x-ui.button>
                <x-ui.button disabled>Simpan</x-ui.button>
            </div>

            <div class="component-row">
                <x-ui.badge variant="success" icon="bi-check-circle-fill">Aktif</x-ui.badge>
                <x-ui.badge variant="warning" icon="bi-clock-fill">Menunggu</x-ui.badge>
                <x-ui.badge variant="danger" icon="bi-exclamation-circle-fill">Terlambat</x-ui.badge>
                <x-ui.badge variant="info" icon="bi-send-fill">Dipublikasikan</x-ui.badge>
                <x-ui.badge variant="premium" icon="bi-stars">Kreator mandiri</x-ui.badge>
                <x-ui.badge>Arsip</x-ui.badge>
            </div>
        </section>

        <section class="design-section" aria-labelledby="forms-title">
            <div class="design-section-header">
                <div>
                    <h2 id="forms-title">Form controls</h2>
                    <p>Field setinggi 48 piksel dengan state bantuan, valid, invalid, dan disabled.</p>
                </div>
                <span class="design-index">03 / FORMS</span>
            </div>

            <form class="row g-3" novalidate>
                <div class="col-md-6">
                    <label class="form-label" for="studentName">Nama lengkap</label>
                    <input class="form-control" id="studentName" value="Nadia Putri Ramadhani">
                    <div class="form-text">Gunakan nama sesuai data sekolah.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="studentClass">Kelas</label>
                    <select class="form-select" id="studentClass">
                        <option>Kelas 7A</option>
                        <option selected>Kelas 8A</option>
                        <option>Kelas 9A</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="studentEmail">Email siswa</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input class="form-control is-valid" id="studentEmail" value="nadia@gmail.com">
                    </div>
                    <div class="valid-feedback d-block">Domain email diizinkan.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="joinCode">Kode pendaftaran</label>
                    <input class="form-control is-invalid" id="joinCode" value="SKUAD-2026-LAMA">
                    <div class="invalid-feedback">Kode sudah kedaluwarsa.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="disabledField">Tahun ajaran</label>
                    <input class="form-control" id="disabledField" value="2026/2027" disabled>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="d-flex flex-wrap gap-4 pb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="agreeRules" checked>
                            <label class="form-check-label" for="agreeRules">Setuju aturan</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="activeSwitch" checked>
                            <label class="form-check-label" for="activeSwitch">Status aktif</label>
                        </div>
                    </div>
                </div>
            </form>
        </section>

        <section class="design-section" aria-labelledby="cards-title">
            <div class="design-section-header">
                <div>
                    <h2 id="cards-title">Cards dan avatar</h2>
                    <p>Kontainer modular dengan radius lembut dan informasi berlapis.</p>
                </div>
                <span class="design-index">04 / SURFACES</span>
            </div>

            <div class="row g-3">
                <div class="col-lg-7">
                    <article class="skuad-card h-100">
                        <div class="skuad-card-header">
                            <div>
                                <x-ui.badge variant="info">Pertemuan 18</x-ui.badge>
                                <h3 class="h4 fw-bold mt-3 mb-1">Bercerita melalui fotografi</h3>
                                <p class="text-secondary mb-0">Komposisi, cahaya, dan etika mengambil gambar.</p>
                            </div>
                            <button class="skuad-icon-button" aria-label="Menu pertemuan"><i class="bi bi-three-dots"></i></button>
                        </div>
                        <div class="skuad-card-body">
                            <div class="progress mb-3" role="progressbar" aria-label="Progress materi" aria-valuenow="72" aria-valuemin="0" aria-valuemax="100" style="height: 8px;">
                                <div class="progress-bar bg-success" style="width: 72%"></div>
                            </div>
                            <div class="d-flex flex-wrap justify-content-between gap-3 small text-secondary">
                                <span><i class="bi bi-clock me-1"></i> 90 menit</span>
                                <span><i class="bi bi-people me-1"></i> 32 siswa</span>
                                <strong class="text-success">72% selesai</strong>
                            </div>
                        </div>
                    </article>
                </div>
                <div class="col-lg-5">
                    <div class="example-panel">
                        <p class="skuad-eyebrow">Avatar scale</p>
                        <div class="avatar-row mb-4">
                            <x-ui.avatar name="Nadia Putri" size="xs" />
                            <x-ui.avatar name="Fajar Ramadhan" size="sm" status="online" />
                            <x-ui.avatar name="Andi Apriandi" size="md" status="busy" />
                            <x-ui.avatar name="SKUAD Coach" size="lg" />
                        </div>
                        <x-ui.skeleton :lines="3" avatar />
                    </div>
                </div>
            </div>
        </section>

        <section class="design-section" aria-labelledby="table-title">
            <div class="design-section-header">
                <div>
                    <h2 id="table-title">Premium data table</h2>
                    <p>Tabel desktop berubah menjadi kartu ringkas pada ponsel.</p>
                </div>
                <span class="design-index">05 / DATA</span>
            </div>

            <div class="skuad-table-wrap">
                <div class="table-responsive">
                    <table class="table skuad-table">
                        <thead>
                            <tr><th>Siswa</th><th>Kelas</th><th>Progress</th><th>Status</th><th class="text-end">Aksi</th></tr>
                        </thead>
                        <tbody>
                            @foreach ([
                                ['Nadia Putri', '8A', '24/30', 'Aktif', 'success'],
                                ['Fajar Ramadhan', '8A', '19/30', 'Perlu revisi', 'warning'],
                                ['Salma Azzahra', '7A', '27/30', 'Aktif', 'success'],
                            ] as [$name, $class, $progress, $status, $tone])
                                <tr>
                                    <td><div class="d-flex align-items-center gap-2"><x-ui.avatar :name="$name" size="sm" /><div><strong class="d-block">{{ $name }}</strong><small class="text-secondary">{{ str($name)->slug('.') }}@gmail.com</small></div></div></td>
                                    <td>{{ $class }}</td>
                                    <td><strong>{{ $progress }}</strong></td>
                                    <td><x-ui.badge :variant="$tone">{{ $status }}</x-ui.badge></td>
                                    <td class="text-end"><button class="skuad-icon-button" aria-label="Lihat {{ $name }}"><i class="bi bi-arrow-up-right"></i></button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="skuad-mobile-list">
                @foreach ([
                    ['Nadia Putri', '8A', '24/30', 'Aktif', 'success'],
                    ['Fajar Ramadhan', '8A', '19/30', 'Perlu revisi', 'warning'],
                    ['Salma Azzahra', '7A', '27/30', 'Aktif', 'success'],
                ] as [$name, $class, $progress, $status, $tone])
                    <article class="student-mobile-card">
                        <div class="student-mobile-card-header"><x-ui.avatar :name="$name" size="sm" /><div class="flex-grow-1"><strong class="d-block">{{ $name }}</strong><small class="text-secondary">Kelas {{ $class }}</small></div><x-ui.badge :variant="$tone">{{ $status }}</x-ui.badge></div>
                        <div class="student-mobile-card-meta"><div><small>Progress</small><strong>{{ $progress }}</strong></div><div><small>Kehadiran</small><strong>94%</strong></div></div>
                        <x-ui.button variant="outline" icon="bi-arrow-up-right">Lihat detail</x-ui.button>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="design-section" aria-labelledby="states-title">
            <div class="design-section-header">
                <div>
                    <h2 id="states-title">Empty dan loading states</h2>
                    <p>Setiap halaman memberikan konteks dan langkah berikutnya.</p>
                </div>
                <span class="design-index">06 / STATES</span>
            </div>

            <div class="row g-3">
                <div class="col-lg-7">
                    <div class="skuad-card h-100">
                        <x-ui.empty-state title="Belum ada karya pilihan" description="Karya yang sudah dinilai dan disetujui dapat ditampilkan sebagai portofolio unggulan." icon="bi-images">
                            <x-slot:action><x-ui.button icon="bi-plus-lg">Tambah karya</x-ui.button></x-slot:action>
                        </x-ui.empty-state>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="skuad-card h-100 p-4">
                        <p class="skuad-eyebrow">Skeleton list</p>
                        <div class="d-grid gap-4 mt-3">
                            <x-ui.skeleton :lines="3" avatar />
                            <x-ui.skeleton :lines="2" avatar />
                            <x-ui.skeleton :lines="3" avatar />
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('overlays')
    <div class="offcanvas offcanvas-end" tabindex="-1" id="filterCanvas" aria-labelledby="filterCanvasLabel">
        <div class="offcanvas-header border-bottom">
            <div><p class="skuad-eyebrow mb-1">Filter data</p><h2 class="offcanvas-title h5 fw-bold" id="filterCanvasLabel">Saring siswa</h2></div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
        </div>
        <div class="offcanvas-body">
            <div class="mb-3"><label class="form-label" for="filterClass">Kelas</label><select class="form-select" id="filterClass"><option>Semua kelas</option><option>7A</option><option>8A</option></select></div>
            <div class="mb-3"><label class="form-label" for="filterStatus">Status</label><select class="form-select" id="filterStatus"><option>Semua status</option><option>Aktif</option><option>Onboarding</option></select></div>
            <div class="mb-3"><label class="form-label" for="filterInterest">Minat</label><input class="form-control" id="filterInterest" placeholder="Contoh: fotografi"></div>
        </div>
        <div class="p-3 border-top d-grid gap-2">
            <x-ui.button data-bs-dismiss="offcanvas">Terapkan filter</x-ui.button>
            <x-ui.button variant="ghost" data-bs-dismiss="offcanvas">Reset</x-ui.button>
        </div>
    </div>

    <div class="modal fade skuad-modal" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body p-4 p-md-5 text-center">
                    <span class="skuad-modal-icon mb-3"><i class="bi bi-trash3"></i></span>
                    <h2 class="h4 fw-bold" id="confirmModalLabel">Hapus data ini?</h2>
                    <p class="text-secondary">Tindakan ini tidak dapat dibatalkan. Pastikan data yang dipilih sudah benar.</p>
                    <div class="d-grid d-sm-flex justify-content-sm-center gap-2 mt-4">
                        <x-ui.button variant="outline" data-bs-dismiss="modal">Batal</x-ui.button>
                        <x-ui.button variant="danger" data-confirm-action>Ya, hapus</x-ui.button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="toast-container position-fixed top-0 end-0 p-3 mt-5">
        <div class="toast skuad-toast" id="demoToast" role="status" aria-live="polite" aria-atomic="true">
            <div class="toast-body d-flex align-items-start gap-3 p-3">
                <span class="skuad-toast-icon"><i class="bi bi-check-lg"></i></span>
                <div class="flex-grow-1"><strong class="d-block">Perubahan tersimpan</strong><small class="text-secondary">Design token berhasil diperbarui.</small></div>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Tutup"></button>
            </div>
        </div>
    </div>
@endpush

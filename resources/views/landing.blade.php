@extends('layouts.app')

@section('title', 'RKDD Cikampek Selatan - Ruang Tumbuh Generasi Digital')

@php
    $weeklyHighlights = $weeklyHighlights ?? collect();
    $monthlyHighlights = $monthlyHighlights ?? collect();
    $slides = $slides ?? collect();
    $knowledgeResources = $knowledgeResources ?? collect();
    $publicPrograms = $publicPrograms ?? collect();
    $agreementRules = $agreementRules ?? [];
    $mainDomain = 'http://digicomciksel.com';
    $fallbackPrograms = [
        ['name' => 'SKUAD Digital', 'type' => 'Sekolah', 'icon' => 'bi-stars', 'description' => 'Ruang siswa belajar literasi digital, karya kreatif, AI, portofolio, dan etika berkarya.'],
        ['name' => 'Konten Kreator', 'type' => 'Komunitas', 'icon' => 'bi-camera-reels', 'description' => 'Pelatihan storytelling, desain visual, video pendek, dan publikasi konten bermanfaat.'],
        ['name' => 'Jurnalis Digital', 'type' => 'Komunitas/sekolah', 'icon' => 'bi-newspaper', 'description' => 'Belajar menulis, meliput, memotret, dan menyusun kabar baik dari lingkungan sekitar.'],
        ['name' => 'Affiliate & UMKM', 'type' => 'Warga/UMKM', 'icon' => 'bi-shop-window', 'description' => 'Pendampingan promosi produk, katalog digital, konten jualan, dan peluang ekonomi kreatif.'],
    ];
@endphp

@section('content')
<div class="landing-page public-dashboard rkdd-landing rkdd-premium-home">
    <section class="landing-hero public-hero rkdd-hero">
        <div class="container">
            <div class="public-hero-grid">
                <div class="public-hero-copy">
                    <p class="landing-kicker"><span></span> Ruang Komunitas Digital Desa</p>
                    <h1>Ruang tumbuh generasi digital Cikampek Selatan.</h1>
                    <p class="landing-lead">RKDD adalah tempat belajar, berkarya, berbagi ilmu, dan membuka peluang digital untuk siswa, komunitas, warga, UMKM, dan mitra. Dari desa, karya baik bisa tumbuh, terdokumentasi, dan terlihat.</p>
                    <div class="landing-hero-actions">
                        <a class="btn btn-skuad btn-lg" href="{{ $mainDomain }}">Kunjungi Digicom Ciksel <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
                        <a class="btn btn-skuad-outline btn-lg" href="{{ route('knowledge.index') }}">Masuk Ruang Ilmu <i class="bi bi-book" aria-hidden="true"></i></a>
                        <a class="btn btn-skuad-outline btn-lg" href="{{ route('best-works.index') }}">Lihat Karya Terbaik <i class="bi bi-stars" aria-hidden="true"></i></a>
                    </div>
                    <div class="landing-trust" aria-label="Dampak RKDD">
                        <div><strong>{{ max($publicPrograms->count(), 4) }}</strong><span>Program digital</span></div>
                        <div><strong>{{ $knowledgeResources->count() }}</strong><span>Konten Ruang Ilmu</span></div>
                        <div><strong>{{ $weeklyHighlights->count() + $monthlyHighlights->count() }}</strong><span>Karya pilihan</span></div>
                    </div>
                </div>

                <div class="public-stage rkdd-photo-stage" aria-label="Media utama RKDD">
                    @if($profileVideo)
                        <article class="rkdd-hero-video-card">
                            <div class="rkdd-hero-video-frame">
                                @if($profileVideo->youtubeEmbedUrl())
                                    <iframe src="{{ $profileVideo->youtubeEmbedUrl() }}" title="{{ $profileVideo->title }}" allowfullscreen loading="lazy"></iframe>
                                @else
                                    <video src="{{ $profileVideo->video_url }}" controls preload="metadata" @if($profileVideo->thumbnail_url) poster="{{ $profileVideo->thumbnail_url }}" @endif></video>
                                @endif
                            </div>
                            <div class="rkdd-hero-video-copy">
                                <small>Video profil RKDD</small>
                                <h2>{{ $profileVideo->title }}</h2>
                                <p>{{ $profileVideo->description ?: 'Kenali RKDD Cikampek Selatan melalui cerita, kegiatan, dan karya yang sedang bertumbuh.' }}</p>
                            </div>
                        </article>
                    @elseif($slides->isNotEmpty())
                        <div id="rkddActivityCarousel" class="carousel slide rkdd-activity-carousel" data-bs-ride="carousel">
                            <div class="carousel-inner">
                                @foreach($slides as $slide)
                                    <article class="carousel-item @if($loop->first) active @endif">
                                        <img src="{{ $slide->image_url }}" alt="{{ $slide->title }}">
                                        <div class="rkdd-carousel-caption">
                                            <small>{{ $slide->eyebrow ?: 'Kegiatan RKDD' }}</small>
                                            <h2>{{ $slide->title }}</h2>
                                            @if($slide->description)<p>{{ $slide->description }}</p>@endif
                                            @if($slide->cta_url && $slide->cta_label)<a href="{{ $slide->cta_url }}" target="_blank" rel="noopener">{{ $slide->cta_label }} <i class="bi bi-arrow-up-right"></i></a>@endif
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                            <button class="carousel-control-prev" type="button" data-bs-target="#rkddActivityCarousel" data-bs-slide="prev" aria-label="Foto sebelumnya"><span class="carousel-control-prev-icon" aria-hidden="true"></span></button>
                            <button class="carousel-control-next" type="button" data-bs-target="#rkddActivityCarousel" data-bs-slide="next" aria-label="Foto berikutnya"><span class="carousel-control-next-icon" aria-hidden="true"></span></button>
                        </div>
                    @else
                        <article class="public-stage-main">
                            <div class="visual-topline"><span>Gerakan digital</span><b>RKDD Ciksel</b></div>
                            <div class="public-score-ring"><span>∞</span><small>karya</small></div>
                            <h2>Belajar bukan hanya duduk mendengar, tapi mencoba, membuat, dan berbagi manfaat.</h2>
                            <div class="visual-modules"><span><i class="bi bi-brush"></i> Kreatif</span><span><i class="bi bi-camera-video"></i> Media</span><span><i class="bi bi-cpu"></i> AI</span><span><i class="bi bi-shop"></i> UMKM</span></div>
                        </article>
                    @endif
                </div>
            </div>
        </div>
    </section>

    @if($profileVideo && $slides->isNotEmpty())
        <section class="rkdd-activity-strip" aria-labelledby="rkdd-activity-strip-title">
            <div class="container">
                <div class="landing-section-heading">
                    <div><p class="landing-section-number">Foto Kegiatan</p><h2 id="rkdd-activity-strip-title">Jejak kegiatan RKDD yang terus bergerak.</h2></div>
                    <p>Foto kegiatan dapat diganti oleh Super Admin agar beranda selalu menampilkan suasana terbaru dari proses belajar dan berkarya.</p>
                </div>
                <div id="rkddActivityStripCarousel" class="carousel slide rkdd-activity-carousel rkdd-activity-carousel-wide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        @foreach($slides as $slide)
                            <article class="carousel-item @if($loop->first) active @endif">
                                <img src="{{ $slide->image_url }}" alt="{{ $slide->title }}">
                                <div class="rkdd-carousel-caption">
                                    <small>{{ $slide->eyebrow ?: 'Kegiatan RKDD' }}</small>
                                    <h2>{{ $slide->title }}</h2>
                                    @if($slide->description)<p>{{ $slide->description }}</p>@endif
                                    @if($slide->cta_url && $slide->cta_label)<a href="{{ $slide->cta_url }}" target="_blank" rel="noopener">{{ $slide->cta_label }} <i class="bi bi-arrow-up-right"></i></a>@endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#rkddActivityStripCarousel" data-bs-slide="prev" aria-label="Foto sebelumnya"><span class="carousel-control-prev-icon" aria-hidden="true"></span></button>
                    <button class="carousel-control-next" type="button" data-bs-target="#rkddActivityStripCarousel" data-bs-slide="next" aria-label="Foto berikutnya"><span class="carousel-control-next-icon" aria-hidden="true"></span></button>
                </div>
            </div>
        </section>
    @endif

    <section class="public-onboarding" id="program-rkdd">
        <div class="container">
            <div class="landing-section-heading">
                <div><p class="landing-section-number">01 / Program RKDD</p><h2>Banyak pintu belajar, satu ruang tumbuh bersama.</h2></div>
                <p>Setiap program dapat punya warna, peserta, sekolah/lembaga, kelompok, materi, presensi, tugas, asesmen, karya, dan laporan sendiri.</p>
            </div>
            <div class="public-step-grid">
                @forelse($publicPrograms as $program)
                    <article style="--program-color: {{ $program->primary_color }}">
                        <b>{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</b>
                        <i class="bi bi-diagram-3" aria-hidden="true"></i>
                        <h3>{{ $program->name }}</h3>
                        <small>{{ $program->type }} · {{ $program->batches_count }} periode</small>
                        <p>{{ $program->description ?: 'Program digital RKDD yang siap dikelola sesuai kebutuhan sekolah, komunitas, atau warga.' }}</p>
                    </article>
                @empty
                    @foreach($fallbackPrograms as $program)
                        <article>
                            <b>{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</b>
                            <i class="bi {{ $program['icon'] }}" aria-hidden="true"></i>
                            <h3>{{ $program['name'] }}</h3>
                            <small>{{ $program['type'] }}</small>
                            <p>{{ $program['description'] }}</p>
                        </article>
                    @endforeach
                @endforelse
            </div>
        </div>
    </section>

    <section class="landing-manifesto public-manifesto">
        <div class="container">
            <p class="landing-section-number">02 / Mengapa RKDD?</p>
            <div class="landing-manifesto-grid">
                <h2>Teknologi menjadi dekat ketika dipakai untuk belajar, berkarya, dan menyelesaikan kebutuhan nyata.</h2>
                <div>
                    <p><strong>RKDD Cikampek Selatan</strong> tidak hanya mengenalkan aplikasi digital. RKDD membangun kebiasaan berpikir kreatif, bekerja rapi, berani mencoba, dan menggunakan teknologi secara jujur serta bermanfaat.</p>
                    <p>Di dalamnya ada pendampingan, dokumentasi proses, ruang diskusi, bacaan, video tutorial, penilaian, portofolio, dan panggung karya terbaik untuk memberi motivasi.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="public-highlights rkdd-knowledge-preview" id="ruang-ilmu">
        <div class="container">
            <div class="landing-section-heading">
                <div><p class="landing-section-number">04 / Ruang Ilmu</p><h2>Bacaan dan video tutorial yang membantu peserta bertumbuh.</h2></div>
                <p>Ruang Ilmu berisi eBook, artikel, panduan, dan video tutorial pilihan. Super Admin dapat mengisinya dari URL agar bahan belajar publik terus segar.</p>
            </div>
            <div class="rkdd-knowledge-grid">
                @forelse($knowledgeResources as $resource)
                    @include('knowledge._card', ['resource' => $resource])
                @empty
                    <article class="public-highlight-empty"><i class="bi bi-book"></i><h3>Ruang Ilmu segera diisi.</h3><p>Super Admin dapat menambahkan bacaan, eBook, panduan, dan video tutorial dari URL.</p></article>
                @endforelse
            </div>
            <div class="mt-4"><a class="landing-text-link" href="{{ route('knowledge.index') }}">Buka semua Ruang Ilmu <i class="bi bi-arrow-right"></i></a></div>
        </div>
    </section>

    <section class="public-highlights" id="hasil-terbaik">
        <div class="container">
            <div class="landing-section-heading">
                <div><p class="landing-section-number">05 / Karya Terbaik</p><h2>Karya peserta adalah bukti bahwa belajar bisa menghasilkan dampak.</h2></div>
                <p>Instruktur/coach dan Super Admin dapat memilih karya terbaik dari berbagai program agar menjadi motivasi untuk peserta lain.</p>
            </div>
            <div class="public-highlight-board">
                <section>
                    <div class="public-highlight-title"><span>Minggu ini</span><a href="{{ route('best-works.index') }}">Halaman karya terbaik <i class="bi bi-arrow-right"></i></a></div>
                    <div class="public-highlight-grid">@forelse($weeklyHighlights as $highlight) @include('showcase-highlights._public-card', ['highlight' => $highlight]) @empty <article class="public-highlight-empty"><i class="bi bi-stars"></i><h3>Karya pilihan minggu ini segera hadir.</h3><p>Instruktur dapat menambahkan URL karya terbaik dari dashboard.</p></article> @endforelse</div>
                </section>
                <section>
                    <div class="public-highlight-title"><span>Bulan ini</span><a href="{{ route('student.register') }}">Gabung program <i class="bi bi-arrow-right"></i></a></div>
                    <div class="public-highlight-grid">@forelse($monthlyHighlights as $highlight) @include('showcase-highlights._public-card', ['highlight' => $highlight]) @empty <article class="public-highlight-empty"><i class="bi bi-trophy"></i><h3>Karya pilihan bulan ini belum dikurasi.</h3><p>Showcase akan menjadi ruang apresiasi peserta lintas kegiatan digital.</p></article> @endforelse</div>
                </section>
            </div>
        </div>
    </section>

    <section class="landing-journey">
        <div class="container">
            <div class="landing-journey-grid">
                <div class="journey-copy"><p class="landing-section-number">06 / Alur Bergabung</p><h2>Dari ingin tahu, menjadi peserta, lalu berkarya.</h2><p>RKDD menjaga alur agar peserta tidak sekadar masuk, tetapi punya profil, kelompok, pembelajaran, presensi, tugas, penilaian, dan portofolio.</p><a href="{{ route('student.register') }}" class="landing-text-link">Mulai pendaftaran peserta <i class="bi bi-arrow-right"></i></a></div>
                <ol class="journey-list">
                    <li><b>01</b><div><small>Pilih program</small><h3>Peserta bergabung sesuai kegiatan</h3><p>Program bisa untuk sekolah, komunitas, warga, UMKM, atau pelatihan khusus.</p></div></li>
                    <li><b>02</b><div><small>Dapatkan kode</small><h3>Kode menjaga peserta masuk ke program yang benar</h3><p>Admin/pembina membagikan kode sesuai kelompok dan periode kegiatan.</p></div></li>
                    <li><b>03</b><div><small>Belajar dan hadir</small><h3>Materi, diskusi, dan presensi tercatat</h3><p>QR presensi ditampilkan di ruang kegiatan agar kehadiran valid.</p></div></li>
                    <li><b>04</b><div><small>Berkarya</small><h3>Hasil terbaik bisa tampil untuk publik</h3><p>Karya peserta menjadi motivasi dan bukti dampak RKDD.</p></div></li>
                </ol>
            </div>
        </div>
    </section>

    <section class="public-rules" id="aturan-peserta">
        <div class="container">
            <div class="landing-section-heading"><div><p class="landing-section-number">07 / Aturan Peserta</p><h2>Ruang digital yang baik perlu etika, tanggung jawab, dan rasa aman.</h2></div><p>Aturan dasar dapat dibaca sejak awal agar peserta memahami cara berkomunikasi, menggunakan AI, menjaga privasi, dan mempublikasikan karya.</p></div>
            <div class="public-rules-grid">
                @foreach($agreementRules as $rule)
                    <article class="public-rule-card"><i class="bi {{ $rule['icon'] }}"></i><div><h3>{{ $rule['title'] }}</h3><p>{{ $rule['summary'] }}</p></div><button class="landing-text-link public-rule-link" type="button" data-bs-toggle="modal" data-bs-target="#publicRuleModal{{ $loop->iteration }}">Baca lengkap <i class="bi bi-arrow-up-right"></i></button></article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="landing-cta public-cta">
        <div class="container">
            <div class="landing-cta-card">
                <div><p>Kolaborasi RKDD</p><h2>Sekolah, komunitas, warga, dan mitra bisa tumbuh bersama.</h2></div>
                <div class="landing-cta-actions"><a class="btn btn-light btn-lg" href="{{ $mainDomain }}">Menuju Digicom Ciksel</a><a class="btn btn-skuad-outline btn-lg" href="{{ route('student.register') }}">Gabung program</a><span>Ruang Komunitas Digital Desa<br>Cikampek Selatan</span></div>
            </div>
        </div>
    </section>
</div>

@foreach($agreementRules as $rule)
    <div class="modal fade skuad-modal" id="publicRuleModal{{ $loop->iteration }}" tabindex="-1" aria-labelledby="publicRuleModal{{ $loop->iteration }}Label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header"><div><p class="skuad-eyebrow mb-1">Aturan peserta RKDD</p><h2 class="modal-title h4 fw-bold" id="publicRuleModal{{ $loop->iteration }}Label">{{ $rule['title'] }}</h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                <div class="modal-body"><p class="text-secondary">{{ $rule['summary'] }}</p><div class="agreement-rule-sections">@foreach($rule['sections'] as $section)<article><h3>{{ $section['heading'] }}</h3><p>{{ $section['body'] }}</p></article>@endforeach</div></div>
                <div class="modal-footer"><button type="button" class="btn btn-skuad" data-bs-dismiss="modal">Saya mengerti</button></div>
            </div>
        </div>
    </div>
@endforeach
@endsection

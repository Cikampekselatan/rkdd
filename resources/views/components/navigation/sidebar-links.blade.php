@php
    $navigationUser = auth()->user();
    $navigationProgramContext = app(\App\Services\ProgramContextService::class);
    $navigationParticipantLabel = $navigationProgramContext->participantLabel($navigationUser);
    $navigationGroupLabel = $navigationProgramContext->groupLabel($navigationUser);
@endphp

<nav class="skuad-nav" aria-label="Navigasi dashboard">
    <p class="skuad-nav-label">Workspace</p>

    @if ($navigationUser->hasRole(\App\Enums\RoleSlug::SuperAdmin))
        <a class="skuad-nav-link {{ request()->routeIs('super-admin.dashboard') ? 'active' : '' }}" href="{{ route('super-admin.dashboard') }}">
            <i class="bi bi-grid-1x2" aria-hidden="true"></i><span>Dashboard</span>
        </a>
        <a class="skuad-nav-link {{ request()->routeIs('super-admin.programs.*') || request()->routeIs('super-admin.institutions.*') || request()->routeIs('super-admin.program-batches.*') || request()->routeIs('super-admin.portfolio-work-types.*') ? 'active' : '' }}" href="{{ route('super-admin.programs.index') }}">
            <i class="bi bi-diagram-3" aria-hidden="true"></i><span>Program RKDD</span>
        </a>
        <a class="skuad-nav-link {{ request()->routeIs('super-admin.landing-slides.*') || request()->routeIs('super-admin.knowledge-resources.*') || request()->routeIs('super-admin.profile-video.*') ? 'active' : '' }}" href="{{ route('super-admin.knowledge-resources.index') }}">
            <i class="bi bi-gem" aria-hidden="true"></i><span>Konten Beranda</span>
        </a>
        <a class="skuad-nav-link {{ request()->routeIs('super-admin.design-system') ? 'active' : '' }}" href="{{ route('super-admin.design-system') }}">
            <i class="bi bi-palette2" aria-hidden="true"></i><span>Design System</span>
        </a>
    @else
        <a class="skuad-nav-link {{ request()->routeIs($navigationUser->dashboardRouteName()) ? 'active' : '' }}" href="{{ route($navigationUser->dashboardRouteName()) }}">
            <i class="bi bi-grid-1x2" aria-hidden="true"></i><span>Dashboard</span>
        </a>
    @endif

    @if ($navigationUser->hasAnyRole([\App\Enums\RoleSlug::SuperAdmin, \App\Enums\RoleSlug::Admin]))
        <p class="skuad-nav-label mt-4">Master data</p>
        @if ($navigationUser->hasRole(\App\Enums\RoleSlug::SuperAdmin))
            <a class="skuad-nav-link {{ request()->routeIs('super-admin.program-batches.*') ? 'active' : '' }}" href="{{ route('super-admin.program-batches.index') }}">
                <i class="bi bi-calendar3" aria-hidden="true"></i><span>Periode Program</span>
            </a>
        @else
            <a class="skuad-nav-link {{ request()->routeIs('admin.academic-years.*') ? 'active' : '' }}" href="{{ route('admin.academic-years.index') }}">
                <i class="bi bi-calendar3" aria-hidden="true"></i><span>Tahun Ajaran</span>
            </a>
        @endif
        <a class="skuad-nav-link {{ request()->routeIs('admin.classes.*') ? 'active' : '' }}" href="{{ route('admin.classes.index') }}">
            <i class="bi bi-people" aria-hidden="true"></i><span>{{ $navigationGroupLabel }}</span>
        </a>
        <a class="skuad-nav-link {{ request()->routeIs('admin.staff.*') ? 'active' : '' }}" href="{{ route('admin.staff.index') }}">
            <i class="bi bi-person-badge" aria-hidden="true"></i><span>Staff</span>
        </a>
        <a class="skuad-nav-link {{ request()->routeIs('admin.students.*') ? 'active' : '' }}" href="{{ route('admin.students.index') }}">
            <i class="bi bi-people" aria-hidden="true"></i><span>{{ $navigationParticipantLabel }}</span>
        </a>
        <a class="skuad-nav-link {{ request()->routeIs('admin.registration-codes.*') ? 'active' : '' }}" href="{{ route('admin.registration-codes.index') }}">
            <i class="bi bi-key" aria-hidden="true"></i><span>Kode Pendaftaran</span>
        </a>
        <a class="skuad-nav-link {{ request()->routeIs('documents.*') ? 'active' : '' }}" href="{{ route('documents.index') }}">
            <i class="bi bi-folder2-open" aria-hidden="true"></i><span>Document Center</span>
        </a>
        <a class="skuad-nav-link {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}" href="{{ route('activity-logs.index') }}">
            <i class="bi bi-journal-check" aria-hidden="true"></i><span>Absen Pengajar</span>
        </a>
        <a class="skuad-nav-link {{ request()->routeIs('important-notes.*') ? 'active' : '' }}" href="{{ route('important-notes.index') }}">
            <i class="bi bi-exclamation-diamond" aria-hidden="true"></i><span>Catatan Penting</span>
        </a>
        <a class="skuad-nav-link {{ request()->routeIs('activity-documentations.*') ? 'active' : '' }}" href="{{ route('activity-documentations.index') }}">
            <i class="bi bi-camera" aria-hidden="true"></i><span>Dokumentasi Kegiatan</span>
        </a>
        @unless ($navigationUser->hasRole(\App\Enums\RoleSlug::Teacher))
            <a class="skuad-nav-link {{ request()->routeIs('interactions.discussions.*') ? 'active' : '' }}" href="{{ route('interactions.discussions.index') }}">
                <i class="bi bi-chat-square-text" aria-hidden="true"></i><span>Diskusi</span>
            </a>
        @endunless
        <a class="skuad-nav-link {{ request()->routeIs('showcase-highlights.*') ? 'active' : '' }}" href="{{ route('showcase-highlights.index') }}">
            <i class="bi bi-stars" aria-hidden="true"></i><span>Showcase Karya</span>
        </a>
    @elseif ($navigationUser->hasAnyRole([\App\Enums\RoleSlug::Teacher, \App\Enums\RoleSlug::Coach]))
        <p class="skuad-nav-label mt-4">Pembinaan</p>
        <a class="skuad-nav-link {{ request()->routeIs('admin.students.*') ? 'active' : '' }}" href="{{ route('admin.students.index') }}">
            <i class="bi bi-people" aria-hidden="true"></i><span>{{ $navigationParticipantLabel }}</span>
        </a>
        <a class="skuad-nav-link {{ request()->routeIs('teacher.project-groups.*') || request()->routeIs('teacher.group-projects.*') ? 'active' : '' }}" href="{{ route('teacher.project-groups.index') }}">
            <i class="bi bi-diagram-3" aria-hidden="true"></i><span>Kelompok Proyek</span>
        </a>
        <a class="skuad-nav-link {{ request()->routeIs('teacher.learning.*') ? 'active' : '' }}" href="{{ route('teacher.learning.index') }}">
            <i class="bi bi-journal-richtext" aria-hidden="true"></i><span>Pembelajaran</span>
        </a>
        <a class="skuad-nav-link {{ request()->routeIs('teacher.attendance.*') ? 'active' : '' }}" href="{{ route('teacher.attendance.index') }}">
            <i class="bi bi-calendar2-check" aria-hidden="true"></i><span>Presensi</span>
        </a>
        <a class="skuad-nav-link {{ request()->routeIs('teacher.assignments.*') || request()->routeIs('teacher.submissions.*') ? 'active' : '' }}" href="{{ route('teacher.assignments.index') }}">
            <i class="bi bi-clipboard-check" aria-hidden="true"></i><span>Tugas</span>
        </a>
        <a class="skuad-nav-link {{ request()->routeIs('teacher.rubrics.*') || request()->routeIs('teacher.grades.*') ? 'active' : '' }}" href="{{ route('teacher.rubrics.index') }}">
            <i class="bi bi-ui-checks-grid" aria-hidden="true"></i><span>Rubrik & Nilai</span>
        </a>
        <a class="skuad-nav-link {{ request()->routeIs('teacher.monthly-assessments.*') ? 'active' : '' }}" href="{{ route('teacher.monthly-assessments.index') }}">
            <i class="bi bi-calendar2-range" aria-hidden="true"></i><span>Asesmen Bulanan</span>
        </a>
        <a class="skuad-nav-link {{ request()->routeIs('teacher.portfolio.*') ? 'active' : '' }}" href="{{ route('teacher.portfolio.index') }}">
            <i class="bi bi-images" aria-hidden="true"></i><span>Portofolio {{ $navigationParticipantLabel }}</span>
        </a>
        <a class="skuad-nav-link {{ request()->routeIs('interactions.announcements.*') || request()->routeIs('teacher.announcements.*') ? 'active' : '' }}" href="{{ route('interactions.announcements.index') }}">
            <i class="bi bi-megaphone" aria-hidden="true"></i><span>Pengumuman</span>
        </a>
        <a class="skuad-nav-link {{ request()->routeIs('documents.*') ? 'active' : '' }}" href="{{ route('documents.index') }}">
            <i class="bi bi-folder2-open" aria-hidden="true"></i><span>Document Center</span>
        </a>
        <a class="skuad-nav-link {{ request()->routeIs('interactions.discussions.*') ? 'active' : '' }}" href="{{ route('interactions.discussions.index') }}">
            <i class="bi bi-chat-square-text" aria-hidden="true"></i><span>Diskusi</span>
        </a>
        <a class="skuad-nav-link {{ request()->routeIs('showcase-highlights.*') ? 'active' : '' }}" href="{{ route('showcase-highlights.index') }}">
            <i class="bi bi-stars" aria-hidden="true"></i><span>Showcase Karya</span>
        </a>
        <a class="skuad-nav-link {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}" href="{{ route('activity-logs.index') }}">
            <i class="bi bi-journal-check" aria-hidden="true"></i><span>Absen Pengajar</span>
        </a>
        <a class="skuad-nav-link {{ request()->routeIs('important-notes.*') ? 'active' : '' }}" href="{{ route('important-notes.index') }}">
            <i class="bi bi-exclamation-diamond" aria-hidden="true"></i><span>Catatan Penting</span>
        </a>
        <a class="skuad-nav-link {{ request()->routeIs('activity-documentations.*') ? 'active' : '' }}" href="{{ route('activity-documentations.index') }}">
            <i class="bi bi-camera" aria-hidden="true"></i><span>Dokumentasi Kegiatan</span>
        </a>
    @elseif ($navigationUser->hasRole(\App\Enums\RoleSlug::Student))
        <p class="skuad-nav-label mt-4">Ruang belajar</p>
        <a class="skuad-nav-link {{ request()->routeIs('student.programs.*') ? 'active' : '' }}" href="{{ route('student.programs.index') }}">
            <i class="bi bi-diagram-3" aria-hidden="true"></i><span>Program Saya</span>
        </a>
        <a class="skuad-nav-link {{ request()->routeIs('student.learning.*') ? 'active' : '' }}" href="{{ route('student.learning.index') }}">
            <i class="bi bi-journal-richtext" aria-hidden="true"></i><span>Lanjut Belajar</span>
        </a>
        <a class="skuad-nav-link {{ request()->routeIs('student.assignments.*') ? 'active' : '' }}" href="{{ route('student.assignments.index') }}">
            <i class="bi bi-clipboard-check" aria-hidden="true"></i><span>Tugas Saya</span>
        </a>
        <a class="skuad-nav-link {{ request()->routeIs('student.project-groups.*') ? 'active' : '' }}" href="{{ route('student.project-groups.index') }}">
            <i class="bi bi-diagram-3" aria-hidden="true"></i><span>Kelompok Saya</span>
        </a>
        <a class="skuad-nav-link {{ request()->routeIs('student.grades.*') ? 'active' : '' }}" href="{{ route('student.grades.index') }}">
            <i class="bi bi-award" aria-hidden="true"></i><span>Nilai Saya</span>
        </a>
        <a class="skuad-nav-link {{ request()->routeIs('student.portfolio.*') ? 'active' : '' }}" href="{{ route('student.portfolio.index') }}">
            <i class="bi bi-briefcase" aria-hidden="true"></i><span>Portofolio</span>
        </a>
        <a class="skuad-nav-link {{ request()->routeIs('interactions.announcements.*') ? 'active' : '' }}" href="{{ route('interactions.announcements.index') }}">
            <i class="bi bi-megaphone" aria-hidden="true"></i><span>Pengumuman</span>
        </a>
        <a class="skuad-nav-link {{ request()->routeIs('interactions.discussions.*') ? 'active' : '' }}" href="{{ route('interactions.discussions.index') }}">
            <i class="bi bi-chat-square-dots" aria-hidden="true"></i><span>Diskusi Program</span>
        </a>
        <a class="skuad-nav-link {{ request()->routeIs('student.documents.*') ? 'active' : '' }}" href="{{ route('student.documents.index') }}">
            <i class="bi bi-folder2-open" aria-hidden="true"></i><span>Dokumen</span>
        </a>
        <a class="skuad-nav-link {{ request()->routeIs('student.attendance.*') ? 'active' : '' }}" href="{{ route('student.attendance.index') }}">
            <i class="bi bi-calendar2-heart" aria-hidden="true"></i><span>Kehadiran</span>
        </a>
    @elseif ($navigationUser->hasRole(\App\Enums\RoleSlug::Principal))
        <p class="skuad-nav-label mt-4">Sumber daya</p>
        <a class="skuad-nav-link {{ request()->routeIs('documents.*') ? 'active' : '' }}" href="{{ route('documents.index') }}">
            <i class="bi bi-folder2-open" aria-hidden="true"></i><span>Document Center</span>
        </a>
        <a class="skuad-nav-link {{ request()->routeIs('interactions.discussions.*') ? 'active' : '' }}" href="{{ route('interactions.discussions.index') }}">
            <i class="bi bi-chat-square-text" aria-hidden="true"></i><span>Diskusi</span>
        </a>
        <a class="skuad-nav-link {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}" href="{{ route('activity-logs.index') }}">
            <i class="bi bi-journal-check" aria-hidden="true"></i><span>Absen Pengajar</span>
        </a>
        <a class="skuad-nav-link {{ request()->routeIs('important-notes.*') ? 'active' : '' }}" href="{{ route('important-notes.index') }}">
            <i class="bi bi-exclamation-diamond" aria-hidden="true"></i><span>Catatan Penting</span>
        </a>
        <a class="skuad-nav-link {{ request()->routeIs('activity-documentations.*') ? 'active' : '' }}" href="{{ route('activity-documentations.index') }}">
            <i class="bi bi-camera" aria-hidden="true"></i><span>Dokumentasi Kegiatan</span>
        </a>
    @endif

    @if($navigationUser->isStaff())
        <p class="skuad-nav-label mt-4">Analitik</p>
        <a class="skuad-nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">
            <i class="bi bi-bar-chart-line" aria-hidden="true"></i><span>Laporan</span>
        </a>
    @endif

    <p class="skuad-nav-label mt-4">Akses cepat</p>
    <a class="skuad-nav-link {{ request()->routeIs('account.profile-photo.*') ? 'active' : '' }}" href="{{ route('account.profile-photo.edit') }}">
        <i class="bi bi-person-circle" aria-hidden="true"></i><span>Profil Saya</span>
    </a>
    <a class="skuad-nav-link" href="{{ route('home') }}">
        <i class="bi bi-house" aria-hidden="true"></i><span>Beranda Publik</span>
    </a>
</nav>

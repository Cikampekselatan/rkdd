<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#071827">
        <meta name="description" content="Design system SKUAD Learning Hub.">
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <title>@yield('title', config('app.name'))</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="skuad-dashboard-body">
        <a class="skip-link" href="#main-content">Lewati ke konten utama</a>
        @php
            $currentUser = auth()->user();
            $currentRole = $currentUser->roles->first()?->name ?? 'Staff';
            $isStudent = $currentUser->hasRole(\App\Enums\RoleSlug::Student);
            $programContext = app(\App\Services\ProgramContextService::class);
            $availableProgramBatches = $programContext->availableBatches($currentUser);
            $activeProgramBatch = $programContext->activeBatch($currentUser);
            $notificationScope = app(\App\Services\NotificationProgramScope::class);
            $headerNotifications = $notificationScope->apply($currentUser->notifications(), $currentUser)->latest()->limit(5)->get();
            $unreadNotificationCount = $notificationScope->apply($currentUser->unreadNotifications(), $currentUser)->count();
            $participantLabel = $programContext->participantLabel($currentUser);
            $groupLabel = $programContext->groupLabel($currentUser);
            $dashboardProgramTheme = $activeProgramBatch?->program
                ?? \App\Models\Program::query()->where('slug', 'skuad')->first()
                ?? \App\Models\Program::query()->where('is_active', true)->orderBy('id')->first();
            $dashboardThemeStyle = $dashboardProgramTheme
                ? '--dashboard-primary: '.$dashboardProgramTheme->primary_color.'; --dashboard-secondary: '.$dashboardProgramTheme->secondary_color.'; --dashboard-accent: '.$dashboardProgramTheme->accent_color.';'
                : '';
        @endphp
        <div class="skuad-app-shell" style="{{ $dashboardThemeStyle }}">
            <aside class="skuad-sidebar d-none d-xl-flex" data-sidebar>
                <div class="skuad-sidebar-header">
                    <a class="skuad-brand" href="{{ route('home') }}" aria-label="{{ $dashboardProgramTheme?->name ?? 'RKDD Cikampek Selatan' }}">
                        <span class="brand-mark" aria-hidden="true">@if($dashboardProgramTheme?->logo_path)<img src="{{ route('program.assets', [$dashboardProgramTheme, 'logo', 'v' => $dashboardProgramTheme->updated_at?->timestamp]) }}" alt="">@else{{ str($dashboardProgramTheme?->name ?? 'RKDD')->substr(0, 1)->upper() }}@endif</span>
                        <span class="skuad-brand-copy">
                            <strong>{{ $dashboardProgramTheme?->name ?? 'RKDD' }}</strong>
                            <small>Program aktif</small>
                        </span>
                    </a>
                    <button class="skuad-icon-button" type="button" data-sidebar-toggle aria-label="Ciutkan sidebar" aria-expanded="true">
                        <i class="bi bi-layout-sidebar-inset" aria-hidden="true"></i>
                    </button>
                </div>

                <x-navigation.sidebar-links />

                <div class="skuad-sidebar-footer">
                    <a href="{{ route('account.profile-photo.edit') }}" aria-label="Ubah foto profil">
                        <x-ui.avatar :name="$currentUser->name" :user="$currentUser" size="sm" status="online" />
                    </a>
                    <div class="skuad-user-copy">
                        <strong>{{ $currentUser->name }}</strong>
                        <small><a href="{{ route('account.profile-photo.edit') }}">Profil Saya</a> · {{ $currentRole }}</small>
                    </div>
                    <form class="ms-auto" method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="skuad-icon-button" type="submit" aria-label="Keluar">
                            <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                        </button>
                    </form>
                </div>
            </aside>

            <div class="offcanvas offcanvas-start skuad-offcanvas" tabindex="-1" id="tabletSidebar" aria-labelledby="tabletSidebarLabel">
                <div class="offcanvas-header">
                    <a class="skuad-brand" href="{{ route('home') }}">
                        <span class="brand-mark" aria-hidden="true">@if($dashboardProgramTheme?->logo_path)<img src="{{ route('program.assets', [$dashboardProgramTheme, 'logo', 'v' => $dashboardProgramTheme->updated_at?->timestamp]) }}" alt="">@else{{ str($dashboardProgramTheme?->name ?? 'RKDD')->substr(0, 1)->upper() }}@endif</span>
                        <span class="skuad-brand-copy"><strong>{{ $dashboardProgramTheme?->name ?? 'RKDD' }}</strong><small>Program aktif</small></span>
                    </a>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
                </div>
                <div class="offcanvas-body"><x-navigation.sidebar-links /></div>
            </div>

            <div class="skuad-main">
                <header class="skuad-topbar">
                    <div class="d-flex align-items-center gap-3 min-w-0">
                        <button class="skuad-icon-button d-xl-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#tabletSidebar" aria-controls="tabletSidebar" aria-label="Buka navigasi">
                            <i class="bi bi-list" aria-hidden="true"></i>
                        </button>
                        <nav aria-label="Breadcrumb" class="d-none d-sm-block">
                            <ol class="breadcrumb skuad-breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="{{ route($currentUser->dashboardRouteName()) }}">{{ $currentRole }}</a></li>
                                <li class="breadcrumb-item active" aria-current="page">@yield('breadcrumb', 'Workspace')</li>
                            </ol>
                        </nav>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        @if($availableProgramBatches->isNotEmpty())
                            <form class="program-context-switcher d-none d-lg-flex" method="POST" action="{{ route('program-context.update') }}">
                                @csrf
                                @method('PUT')
                                <label for="program_batch_id">Program aktif</label>
                                <select id="program_batch_id" name="program_batch_id" onchange="this.form.submit()" aria-label="Pilih program aktif">
                                    @foreach($availableProgramBatches as $batch)
                                        <option value="{{ $batch->id }}" @selected($activeProgramBatch?->id === $batch->id)>{{ $batch->program->name }} · {{ $batch->institution->name }} · {{ $batch->period_label }}</option>
                                    @endforeach
                                </select>
                            </form>
                        @endif
                        @unless ($isStudent)
                            <button class="skuad-icon-button d-none d-md-inline-grid" type="button" aria-label="Cari">
                                <i class="bi bi-search" aria-hidden="true"></i>
                            </button>
                        @endunless
                        <div class="dropdown">
                            <button class="skuad-icon-button position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ $unreadNotificationCount ? 'Notifikasi, '.$unreadNotificationCount.' belum dibaca' : 'Notifikasi, tidak ada notifikasi baru' }}">
                                <i class="bi bi-bell" aria-hidden="true"></i>
                                @if($unreadNotificationCount)<span class="skuad-notification-dot"></span>@endif
                            </button>
                            <div class="dropdown-menu dropdown-menu-end skuad-notification-menu p-0">
                                <div class="skuad-notification-header">
                                    <div><strong>Notifikasi</strong><small>{{ $unreadNotificationCount }} belum dibaca</small></div>
                                    @if($unreadNotificationCount)<form method="POST" action="{{ route('interactions.notifications.read-all') }}">@csrf<button class="btn btn-link btn-sm">Tandai dibaca</button></form>@endif
                                </div>
                                @forelse($headerNotifications as $notification)
                                    @php
                                        $notificationIcon = match($notification->data['kind'] ?? '') {
                                            'announcement' => 'megaphone',
                                            'discussion_reply' => 'chat-dots',
                                            'assignment', 'assignment_revision' => 'clipboard-check',
                                            'submission' => 'send-check',
                                            'grade', 'monthly_assessment', 'remedial_completed' => 'award',
                                            'portfolio_pending', 'portfolio_review', 'portfolio_featured' => 'images',
                                            'teacher_log_review', 'teacher_log_verified', 'teacher_log_rejected' => 'journal-check',
                                            'important_note_review', 'important_note_initialed', 'important_note_verified' => 'exclamation-diamond',
                                            default => 'bell',
                                        };
                                        $notificationProgram = $notification->data['program_context'] ?? $notification->data['program_name'] ?? null;
                                    @endphp
                                    <a class="skuad-notification-item {{ $notification->read_at ? '' : 'unread' }}" href="{{ route('interactions.notifications.read',$notification->id) }}">
                                        <span class="skuad-notification-icon bg-info-subtle text-info"><i class="bi bi-{{ $notificationIcon }}"></i></span>
                                        <span>
                                            <strong>{{ $notification->data['title']??'Aktivitas baru' }}</strong>
                                            <small>@if($notificationProgram)<span>{{ $notificationProgram }}</span> &middot; @endif{{ $notification->data['body'] ?? $notification->created_at->diffForHumans() }}</small>
                                        </span>
                                    </a>
                                @empty
                                    <div class="p-4 text-center text-secondary small">Belum ada notifikasi baru.</div>
                                @endforelse
                            </div>
                        </div>
                        <a href="{{ route('account.profile-photo.edit') }}" class="d-none d-sm-inline-grid text-decoration-none" aria-label="Profil saya">
                            <x-ui.avatar :name="$currentUser->name" :user="$currentUser" size="sm" status="online" />
                        </a>
                    </div>
                </header>

                <main id="main-content" class="skuad-content">
                    @yield('content')
                </main>
            </div>
        </div>

        @if ($isStudent)
            <nav class="skuad-bottom-nav d-md-none" aria-label="Navigasi ponsel {{ strtolower($participantLabel) }}" style="--skuad-nav-items: 5">
                <a class="{{ request()->routeIs('student.dashboard') ? 'active' : '' }}" href="{{ route('student.dashboard') }}"><i class="bi bi-house"></i><span>Beranda</span></a>
                <a class="{{ request()->routeIs('student.programs.*') ? 'active' : '' }}" href="{{ route('student.programs.index') }}"><i class="bi bi-diagram-3"></i><span>Program</span></a>
                <a class="{{ request()->routeIs('student.learning.*') ? 'active' : '' }}" href="{{ route('student.learning.index') }}"><i class="bi bi-journal-richtext"></i><span>Belajar</span></a>
                <a class="{{ request()->routeIs('student.assignments.*') ? 'active' : '' }}" href="{{ route('student.assignments.index') }}"><i class="bi bi-clipboard-check"></i><span>Tugas</span></a>
                <a class="{{ request()->routeIs('student.portfolio.*') ? 'active' : '' }}" href="{{ route('student.portfolio.index') }}"><i class="bi bi-briefcase"></i><span>Karya</span></a>
            </nav>
        @elseif ($currentUser->hasAnyRole([\App\Enums\RoleSlug::Teacher, \App\Enums\RoleSlug::Coach]))
            <nav class="skuad-bottom-nav d-md-none" aria-label="Navigasi ponsel pembinaan" style="--skuad-nav-items: 5">
                <a class="{{ request()->routeIs('teacher.dashboard') || request()->routeIs('coach.dashboard') ? 'active' : '' }}" href="{{ route($currentUser->dashboardRouteName()) }}"><i class="bi bi-house" aria-hidden="true"></i><span>Beranda</span></a>
                <a class="{{ request()->routeIs('admin.students.*') ? 'active' : '' }}" href="{{ route('admin.students.index') }}"><i class="bi bi-people" aria-hidden="true"></i><span>{{ $participantLabel }}</span></a>
                <a class="{{ request()->routeIs('teacher.learning.*') ? 'active' : '' }}" href="{{ route('teacher.learning.index') }}"><i class="bi bi-journal-richtext" aria-hidden="true"></i><span>Belajar</span></a>
                <a class="{{ request()->routeIs('teacher.assignments.*') || request()->routeIs('teacher.submissions.*') ? 'active' : '' }}" href="{{ route('teacher.assignments.index') }}"><i class="bi bi-clipboard-check" aria-hidden="true"></i><span>Tugas</span></a>
                <a class="{{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}"><i class="bi bi-bar-chart-line" aria-hidden="true"></i><span>Laporan</span></a>
            </nav>
        @elseif ($currentUser->hasAnyRole([\App\Enums\RoleSlug::SuperAdmin, \App\Enums\RoleSlug::Admin]))
            <nav class="skuad-bottom-nav d-md-none" aria-label="Navigasi ponsel admin" style="--skuad-nav-items: 6">
                <a class="{{ request()->routeIs('super-admin.dashboard') || request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route($currentUser->dashboardRouteName()) }}"><i class="bi bi-house" aria-hidden="true"></i><span>Beranda</span></a>
                <a class="{{ request()->routeIs('admin.students.*') ? 'active' : '' }}" href="{{ route('admin.students.index') }}"><i class="bi bi-people" aria-hidden="true"></i><span>{{ $participantLabel }}</span></a>
                <a class="{{ request()->routeIs('admin.classes.*') ? 'active' : '' }}" href="{{ route('admin.classes.index') }}"><i class="bi bi-people" aria-hidden="true"></i><span>{{ $groupLabel }}</span></a>
                <a class="{{ request()->routeIs('documents.*') ? 'active' : '' }}" href="{{ route('documents.index') }}"><i class="bi bi-folder2-open" aria-hidden="true"></i><span>Dokumen</span></a>
                <a class="{{ request()->routeIs('activity-logs.*') || request()->routeIs('important-notes.*') || request()->routeIs('activity-documentations.*') ? 'active' : '' }}" href="{{ route('important-notes.index') }}"><i class="bi bi-exclamation-diamond" aria-hidden="true"></i><span>Laporan</span></a>
                <a class="{{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}"><i class="bi bi-bar-chart-line" aria-hidden="true"></i><span>Laporan</span></a>
            </nav>
        @else
            <nav class="skuad-bottom-nav d-md-none" aria-label="Navigasi ponsel kepala sekolah" style="--skuad-nav-items: 5">
                <a class="{{ request()->routeIs('principal.dashboard') ? 'active' : '' }}" href="{{ route('principal.dashboard') }}"><i class="bi bi-house" aria-hidden="true"></i><span>Beranda</span></a>
                <a class="{{ request()->routeIs('documents.*') ? 'active' : '' }}" href="{{ route('documents.index') }}"><i class="bi bi-folder2-open" aria-hidden="true"></i><span>Dokumen</span></a>
                <a class="{{ request()->routeIs('activity-logs.*') ? 'active' : '' }}" href="{{ route('activity-logs.index') }}"><i class="bi bi-journal-check" aria-hidden="true"></i><span>Absen</span></a>
                <a class="{{ request()->routeIs('important-notes.*') || request()->routeIs('activity-documentations.*') ? 'active' : '' }}" href="{{ route('activity-documentations.index') }}"><i class="bi bi-camera" aria-hidden="true"></i><span>Dok</span></a>
                <a class="{{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}"><i class="bi bi-bar-chart-line" aria-hidden="true"></i><span>Laporan</span></a>
            </nav>
        @endif

        @stack('overlays')
    </body>
</html>

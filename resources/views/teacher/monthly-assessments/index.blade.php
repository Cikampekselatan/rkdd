@extends('layouts.dashboard')

@section('title', 'Asesmen Bulanan Siswa')
@section('breadcrumb', 'Asesmen bulanan')

@section('content')
    <div class="grading-page monthly-assessment-page">
        <x-ui.page-header
            eyebrow="Penilaian berkelanjutan"
            title="Asesmen bulanan siswa"
            description="Rekap perkembangan siswa per bulan untuk semester 1 dan 2 sesuai dokumen asesmen SKUAD."
        >
            <x-slot:actions>
                @if($academicYear && $class)
                    <x-ui.button :href="route('teacher.monthly-assessments.export.csv', request()->query())" variant="outline" icon="bi-filetype-csv">Export CSV</x-ui.button>
                    <x-ui.button :href="route('teacher.monthly-assessments.print', request()->query())" variant="outline" icon="bi-file-earmark-pdf" target="_blank">Cetak / PDF</x-ui.button>
                    <x-ui.button :href="route('teacher.monthly-assessments.create', ['academic_year_id' => $academicYear->id, 'class_id' => $class->id, 'semester' => $semester])" icon="bi-plus-lg">Input asesmen</x-ui.button>
                @endif
            </x-slot:actions>
        </x-ui.page-header>

        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

        <section class="monthly-assessment-framework">
            <div>
                <p class="skuad-eyebrow">Bobot nilai dari dokumen asesmen</p>
                <h2>Komposisi asesmen bulanan</h2>
                <p>Produk, proses, kolaborasi, presentasi, dan etika dinilai sebagai bukti perkembangan, bukan hanya satu tugas.</p>
            </div>
            <div class="monthly-component-grid">
                @foreach($components as $component)
                    <article><strong>{{ $component['weight'] }}%</strong><span>{{ $component['label'] }}</span></article>
                @endforeach
            </div>
        </section>

        <form class="report-filter" method="GET" action="{{ route('teacher.monthly-assessments.index') }}">
            <div>
                <label for="academic_year_id">Tahun ajaran</label>
                <select class="form-select" id="academic_year_id" name="academic_year_id">
                    @foreach($academicYears as $year)
                        <option value="{{ $year->id }}" @selected($academicYear?->id === $year->id)>{{ $year->name }}{{ $year->is_active ? ' · Aktif' : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="class_id">Kelompok</label>
                <select class="form-select" id="class_id" name="class_id">
                    @foreach($classes as $row)
                        <option value="{{ $row->id }}" @selected($class?->id === $row->id)>{{ $row->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="semester">Semester</label>
                <select class="form-select" id="semester" name="semester">
                    <option value="1" @selected($semester === 1)>Semester 1</option>
                    <option value="2" @selected($semester === 2)>Semester 2</option>
                </select>
            </div>
            <button class="btn btn-primary"><i class="bi bi-funnel"></i> Terapkan</button>
        </form>

        @if($academicYear && $class)
            <section class="monthly-period-grid">
                @for($month = 1; $month <= 6; $month++)
                    @php($done = (int) ($completedByMonth[$month] ?? 0))
                    @php($label = \App\Models\MonthlyStudentAssessment::periodLabel($academicYear, $semester, $month))
                    <article>
                        <small>Bulan {{ $month }}</small>
                        <strong>{{ $label }}</strong>
                        <span>{{ $done }} / {{ $activeStudentCount }} siswa</span>
                        <i style="width: {{ $activeStudentCount ? min(100, ($done / $activeStudentCount) * 100) : 0 }}%"></i>
                    </article>
                @endfor
            </section>
        @endif

        <section class="monthly-assessment-list">
            <div class="attendance-section-heading">
                <div><p class="skuad-eyebrow">Rekap semester {{ $semester }}</p><h2>Catatan asesmen</h2></div>
                <span>{{ $assessments->total() }} asesmen</span>
            </div>
            @forelse($assessments as $assessment)
                <a class="monthly-assessment-row" href="{{ route('teacher.monthly-assessments.edit', $assessment) }}">
                    <x-ui.avatar :name="$assessment->student->name" size="sm" />
                    <div>
                        <small>{{ $assessment->period_label }} · {{ $assessment->schoolClass->name }}</small>
                        <h3>{{ $assessment->student->name }}</h3>
                        <p>{{ $assessment->product_summary ?: 'Belum ada ringkasan produk/bukti.' }}</p>
                    </div>
                    <span>Level {{ $assessment->achievement_level }}<small>{{ \App\Models\MonthlyStudentAssessment::achievementLabel($assessment->achievement_level) }}</small></span>
                    <strong>{{ number_format((float) $assessment->final_score, 2) }}</strong>
                    <i class="bi bi-chevron-right"></i>
                </a>
            @empty
                <x-ui.empty-state title="Belum ada asesmen bulanan" description="Pilih kelas dan semester, lalu input asesmen siswa per bulan." icon="bi-calendar2-range" />
            @endforelse
            @if($assessments->hasPages())<div class="mt-4">{{ $assessments->links() }}</div>@endif
        </section>
    </div>
@endsection

@extends('layouts.dashboard')

@section('title','Laporan '.$type->label().' - RKDD')
@section('breadcrumb','Laporan '.$type->label())

@section('content')
<div class="report-page">
    <section class="report-title">
        <div>
            <p class="skuad-eyebrow">Laporan operasional</p>
            <h1>{{ $type->label() }}</h1>
            <span>{{ $type->description() }}</span>
        </div>
        <div>
            <strong>{{ number_format($total) }}</strong>
            <small>baris data</small>
        </div>
    </section>

    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <form class="report-filter" method="GET">
        @if(auth()->user()->hasRole(\App\Enums\RoleSlug::SuperAdmin))
            <div>
                <label for="program_batch_id">Program</label>
                <select class="form-select" id="program_batch_id" name="program_batch_id">
                    @foreach($programBatches as $batch)
                        <option value="{{ $batch->id }}" @selected((int)($filters['program_batch_id'] ?? 0) === $batch->id)>{{ $batch->program?->name }} · {{ $batch->institution?->name }} · {{ $batch->period_label }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div>
            <label for="year">{{ $filters['period_label'] ?? 'Periode Program' }}</label>
            <select class="form-select" id="year" name="year">@foreach($years as $year)<option value="{{ $year->id }}" @selected((int)$filters['year']===$year->id)>{{ $year->name }}{{ $year->is_active?' · Aktif':'' }}</option>@endforeach</select>
        </div>
        <div>
            <label for="class">{{ $filters['group_label'] ?? 'Kelompok' }}</label>
            <select class="form-select" id="class" name="class"><option value="">Semua kelompok</option>@foreach($classes as $class)<option value="{{ $class->id }}" @selected((int)$filters['class']===$class->id)>{{ $class->name }}</option>@endforeach</select>
        </div>
        <div>
            <label for="semester">Semester</label>
            <select class="form-select" id="semester" name="semester"><option value="">Semua</option><option value="1" @selected((int)$filters['semester']===1)>Semester 1</option><option value="2" @selected((int)$filters['semester']===2)>Semester 2</option></select>
        </div>
        <div><label for="date_from">Dari tanggal</label><input class="form-control" type="date" id="date_from" name="date_from" value="{{ $filters['date_from'] }}"></div>
        <div><label for="date_to">Sampai tanggal</label><input class="form-control" type="date" id="date_to" name="date_to" value="{{ $filters['date_to'] }}"></div>
        <button class="btn btn-primary"><i class="bi bi-funnel"></i> Terapkan</button>
    </form>

    <div class="report-toolbar">
        <a class="btn btn-light" href="{{ route('reports.index') }}"><i class="bi bi-arrow-left"></i> Pusat laporan</a>
        <span class="d-flex flex-wrap gap-2">
            @if($type === \App\Enums\ReportType::Attendance)<a class="btn btn-primary" target="_blank" href="{{ route('reports.matrix',[$type->value]+request()->query()) }}"><i class="bi bi-grid-3x3-gap"></i> Matriks Kehadiran</a>@endif
            <a class="btn btn-outline-primary" href="{{ route('reports.export.csv',[$type->value]+request()->query()) }}"><i class="bi bi-filetype-csv"></i> Export CSV</a>
            <a class="btn btn-outline-primary" target="_blank" href="{{ route('reports.print',[$type->value]+request()->query()) }}"><i class="bi bi-printer"></i> Cetak A4</a>
        </span>
    </div>

    <div class="report-table-wrap">
        <table class="report-table">
            <thead><tr><th>No.</th>@foreach($columns as $label)<th>{{ $label }}</th>@endforeach</tr></thead>
            <tbody>
                @forelse($items as $index=>$row)
                    <tr><td data-label="No.">{{ $items->firstItem()+$index }}</td>@foreach($columns as $key=>$label)<td data-label="{{ $label }}">{{ $row[$key] }}</td>@endforeach</tr>
                @empty
                    <tr><td colspan="{{ count($columns)+1 }}"><div class="report-empty"><i class="bi bi-inbox"></i><h2>Tidak ada data</h2><p>Ubah filter atau pilih tahun ajaran lain.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $items->links() }}
</div>
@endsection

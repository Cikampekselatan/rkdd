<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\RemedialStatus;
use App\Enums\ReportType;
use App\Enums\RoleSlug;
use App\Models\ActivityDocumentation;
use App\Models\Assignment;
use App\Models\Grade;
use App\Models\ImportantNote;
use App\Models\LearningSession;
use App\Models\MonthlyStudentAssessment;
use App\Models\PortfolioItem;
use App\Models\StudentOnboardingResponse;
use App\Models\Submission;
use App\Models\TeacherActivityLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function build(ReportType $type, array $filters, User $viewer, bool $print = false): array
    {
        [$query, $columns, $transform] = match ($type) {
            ReportType::Students => $this->students($filters), ReportType::Onboarding => $this->onboarding($filters), ReportType::Attendance => $this->attendance($filters), ReportType::TeacherLogs => $this->teacherLogs($filters), ReportType::Assignments => $this->assignments($filters), ReportType::Lateness => $this->lateness($filters), ReportType::Grades => $this->grades($filters), ReportType::MonthlyAssessments => $this->monthlyAssessments($filters), ReportType::Remedial => $this->remedial($filters), ReportType::Portfolio => $this->portfolio($filters), ReportType::ActivityDocumentations => $this->activityDocumentations($filters), ReportType::Notes => $this->notes($filters), ReportType::Sessions => $this->sessions($filters), ReportType::Documents => $this->documents($filters, $viewer),
        };
        if ($print) {
            $total = (clone $query)->count();
            $items = $query->limit(1000)->get()->map($transform);
        } else {
            $items = $query->paginate(25)->withQueryString()->through($transform);
            $total = $items->total();
        }

        return ['type' => $type, 'columns' => $columns, 'items' => $items, 'total' => $total, 'truncated' => $print && $total > $items->count(), 'filters' => $filters];
    }

    public function export(ReportType $type, array $filters, User $viewer): array
    {
        [$query, $columns, $transform] = match ($type) {
            ReportType::Students => $this->students($filters), ReportType::Onboarding => $this->onboarding($filters), ReportType::Attendance => $this->attendance($filters), ReportType::TeacherLogs => $this->teacherLogs($filters), ReportType::Assignments => $this->assignments($filters), ReportType::Lateness => $this->lateness($filters), ReportType::Grades => $this->grades($filters), ReportType::MonthlyAssessments => $this->monthlyAssessments($filters), ReportType::Remedial => $this->remedial($filters), ReportType::Portfolio => $this->portfolio($filters), ReportType::ActivityDocumentations => $this->activityDocumentations($filters), ReportType::Notes => $this->notes($filters), ReportType::Sessions => $this->sessions($filters), ReportType::Documents => $this->documents($filters, $viewer),
        };
        $total = (clone $query)->count();
        $items = $query->limit(5000)->get()->map($transform);

        return ['columns' => $columns, 'items' => $items, 'total' => $total, 'truncated' => $total > $items->count()];
    }

    private function students(array $f): array
    {
        $participant = $f['participant_label'] ?? 'Peserta';
        $group = $f['group_label'] ?? 'Kelompok';

        $q = User::query()
            ->whereHas('roles', fn ($query) => $query->where('slug', RoleSlug::Student->value))
            ->whereHas('classMemberships', function ($query) use ($f): void {
                $query->where('academic_year_id', $f['year'])
                    ->when($f['program_batch_id'] ?? null, fn ($item, $id) => $item->where('program_batch_id', $id))
                    ->when($f['class'] ?? null, fn ($item, $id) => $item->where('class_id', $id))
                    ->when($f['date_from'] ?? null, fn ($item, $date) => $item->where('joined_at', '>=', Carbon::parse($date)->startOfDay()))
                    ->when($f['date_to'] ?? null, fn ($item, $date) => $item->where('joined_at', '<=', Carbon::parse($date)->endOfDay()));
            })
            ->with([
                'studentProfile.schoolClass:id,name',
                'classMemberships' => fn ($query) => $query->where('academic_year_id', $f['year'])->when($f['program_batch_id'] ?? null, fn ($item, $id) => $item->where('program_batch_id', $id))->with('schoolClass:id,name'),
            ])
            ->orderBy('name');

        return [$q, ['name' => 'Nama', 'number' => 'Nomor '.$participant, 'class' => $group, 'user_status' => 'Status Akun', 'membership' => 'Keanggotaan', 'joined' => 'Tanggal Bergabung'], fn ($u) => ['name' => $u->name, 'number' => $u->studentProfile?->student_number ?? '-', 'class' => $u->studentProfile?->schoolClass?->name ?? '-', 'user_status' => ucfirst($u->status->value), 'membership' => ucfirst($u->classMemberships->first()?->status ?? '-'), 'joined' => $u->classMemberships->first()?->joined_at?->format('d/m/Y') ?? '-']];
    }

    private function onboarding(array $f): array
    {
        $group = $f['group_label'] ?? 'Kelompok';

        $q = StudentOnboardingResponse::query()->with(['user:id,name,email,status', 'registrationCode.schoolClass:id,name'])->whereHas('registrationCode', fn ($x) => $x->where('academic_year_id', $f['year'])->when($f['program_batch_id'] ?? null, fn ($y, $id) => $y->where('program_batch_id', $id))->when($f['class'] ?? null, fn ($y, $id) => $y->where('class_id', $id)))->when($f['date_from'] ?? null, fn ($x, $d) => $x->whereDate('created_at', '>=', $d))->when($f['date_to'] ?? null, fn ($x, $d) => $x->whereDate('created_at', '<=', $d))->latest();

        return [$q, ['name' => 'Nama', 'email' => 'Email', 'class' => $group, 'step' => 'Langkah', 'status' => 'Status', 'updated' => 'Diperbarui'], fn ($r) => ['name' => $r->user->name, 'email' => $r->user->email, 'class' => $r->registrationCode?->schoolClass?->name ?? '-', 'step' => $r->current_step.'/5', 'status' => $r->completed_at ? 'Selesai' : 'Belum selesai', 'updated' => $r->updated_at->format('d/m/Y H:i')]];
    }

    private function attendance(array $f): array
    {
        $participant = $f['participant_label'] ?? 'Peserta';
        $group = $f['group_label'] ?? 'Kelompok';

        $q = DB::table('attendance_sessions as sessions')
            ->join('class_students as memberships', function ($join): void {
                $join->on('memberships.academic_year_id', '=', 'sessions.academic_year_id')
                    ->on('memberships.class_id', '=', 'sessions.class_id')
                    ->where('memberships.status', 'active');
            })
            ->join('users as students', 'students.id', '=', 'memberships.user_id')
            ->join('classes as classes', 'classes.id', '=', 'sessions.class_id')
            ->join('learning_sessions as learning_sessions', 'learning_sessions.id', '=', 'sessions.learning_session_id')
            ->leftJoin('attendance_records as records', function ($join): void {
                $join->on('records.attendance_session_id', '=', 'sessions.id')
                    ->on('records.user_id', '=', 'memberships.user_id');
            })
            ->where('sessions.academic_year_id', $f['year'])
            ->when($f['program_batch_id'] ?? null, function ($query, int $id): void {
                $query->where('sessions.program_batch_id', $id)
                    ->where('memberships.program_batch_id', $id);
            })
            ->when($f['class'] ?? null, fn ($query, $id) => $query->where('sessions.class_id', $id))
            ->when($f['semester'] ?? null, fn ($query, $semester) => $query->where('learning_sessions.semester', $semester))
            ->when($f['date_from'] ?? null, fn ($query, $date) => $query->whereDate('sessions.attendance_date', '>=', $date))
            ->when($f['date_to'] ?? null, fn ($query, $date) => $query->whereDate('sessions.attendance_date', '<=', $date))
            ->select([
                'sessions.attendance_date',
                'students.name as student_name',
                'classes.name as class_name',
                'learning_sessions.session_number',
                'learning_sessions.title as session_title',
                'records.status as record_status',
                'records.notes as record_notes',
            ])
            ->orderByDesc('sessions.attendance_date')
            ->orderBy('learning_sessions.session_number')
            ->orderBy('students.name');

        return [$q, ['date' => 'Tanggal', 'student' => $participant, 'class' => $group, 'session' => 'Pertemuan', 'status' => 'Status', 'notes' => 'Catatan'], fn ($r) => ['date' => Carbon::parse($r->attendance_date)->format('d/m/Y'), 'student' => $r->student_name, 'class' => $r->class_name, 'session' => 'P'.$r->session_number.' · '.$r->session_title, 'status' => AttendanceStatus::tryFrom((string) $r->record_status)?->label() ?? 'Belum tercatat', 'notes' => $r->record_notes ?: '-']];
    }

    private function teacherLogs(array $f): array
    {
        $q = TeacherActivityLog::query()->with('teacher:id,name')->where('academic_year_id', $f['year'])->when($f['program_batch_id'] ?? null, fn ($x, $id) => $x->where('program_batch_id', $id))->when($f['date_from'] ?? null, fn ($x, $d) => $x->whereDate('activity_date', '>=', $d))->when($f['date_to'] ?? null, fn ($x, $d) => $x->whereDate('activity_date', '<=', $d))->latest('activity_date');
        if ($f['viewer_is_leadership'] ?? false) {
            $q->where('status', 'verified');
        }

        return [$q, ['number' => 'Nomor', 'date' => 'Tanggal', 'teacher' => 'Pembina', 'material' => 'Materi', 'assignment' => 'Penugasan', 'status' => 'Status'], fn ($r) => ['number' => str_pad($r->log_number, 3, '0', STR_PAD_LEFT), 'date' => $r->activity_date->format('d/m/Y'), 'teacher' => $r->teacher->name, 'material' => $r->material, 'assignment' => $r->assignment ?: '-', 'status' => $r->status->label()]];
    }

    private function assignments(array $f): array
    {
        $group = $f['group_label'] ?? 'Kelompok';

        $q = Assignment::query()->with(['schoolClass:id,name', 'learningSession:id,session_number,title,semester'])->withCount('submissions')->where('academic_year_id', $f['year'])->when($f['program_batch_id'] ?? null, fn ($x, $id) => $x->where('program_batch_id', $id))->when($f['class'] ?? null, fn ($x, $id) => $x->where('class_id', $id))->when($f['semester'] ?? null, fn ($x, $s) => $x->whereHas('learningSession', fn ($y) => $y->where('semester', $s)))->when($f['date_from'] ?? null, fn ($x, $d) => $x->whereDate('due_at', '>=', $d))->when($f['date_to'] ?? null, fn ($x, $d) => $x->whereDate('due_at', '<=', $d))->orderBy('due_at');

        return [$q, ['title' => 'Tugas', 'class' => $group, 'session' => 'Pertemuan', 'due' => 'Tenggat', 'published' => 'Publikasi', 'submissions' => 'Pengumpulan'], fn ($r) => ['title' => $r->title, 'class' => $r->schoolClass->name, 'session' => 'P'.$r->learningSession->session_number, 'due' => $r->due_at->format('d/m/Y H:i'), 'published' => $r->is_published ? 'Published' : 'Draf', 'submissions' => $r->submissions_count]];
    }

    private function lateness(array $f): array
    {
        $participant = $f['participant_label'] ?? 'Peserta';
        $group = $f['group_label'] ?? 'Kelompok';

        $q = Submission::query()->with(['student:id,name', 'assignment.schoolClass:id,name'])->where('status', 'late')->whereHas('assignment', fn ($x) => $x->where('academic_year_id', $f['year'])->when($f['program_batch_id'] ?? null, fn ($y, $id) => $y->where('program_batch_id', $id))->when($f['class'] ?? null, fn ($y, $id) => $y->where('class_id', $id))->when($f['semester'] ?? null, fn ($y, $s) => $y->whereHas('learningSession', fn ($z) => $z->where('semester', $s))))->when($f['date_from'] ?? null, fn ($x, $d) => $x->whereDate('submitted_at', '>=', $d))->when($f['date_to'] ?? null, fn ($x, $d) => $x->whereDate('submitted_at', '<=', $d))->latest('submitted_at');

        return [$q, ['student' => $participant, 'class' => $group, 'assignment' => 'Tugas', 'due' => 'Tenggat', 'submitted' => 'Dikumpulkan', 'revision' => 'Jumlah Revisi'], fn ($r) => ['student' => $r->student->name, 'class' => $r->assignment->schoolClass->name, 'assignment' => $r->assignment->title, 'due' => $r->assignment->due_at->format('d/m/Y H:i'), 'submitted' => $r->submitted_at?->format('d/m/Y H:i') ?? '-', 'revision' => $r->revision_count]];
    }

    private function grades(array $f): array
    {
        $participant = $f['participant_label'] ?? 'Peserta';
        $group = $f['group_label'] ?? 'Kelompok';

        $q = Grade::query()->with(['submission.student:id,name', 'submission.assignment.schoolClass:id,name'])->where('is_published', true)->whereHas('submission.assignment', fn ($x) => $x->where('academic_year_id', $f['year'])->when($f['program_batch_id'] ?? null, fn ($y, $id) => $y->where('program_batch_id', $id))->when($f['class'] ?? null, fn ($y, $id) => $y->where('class_id', $id))->when($f['semester'] ?? null, fn ($y, $s) => $y->whereHas('learningSession', fn ($z) => $z->where('semester', $s))))->when($f['date_from'] ?? null, fn ($x, $d) => $x->whereDate('published_at', '>=', $d))->when($f['date_to'] ?? null, fn ($x, $d) => $x->whereDate('published_at', '<=', $d))->latest('published_at');

        return [$q, ['student' => $participant, 'class' => $group, 'assignment' => 'Tugas', 'score' => 'Nilai', 'level' => 'Level', 'published' => 'Dipublikasikan'], fn ($r) => ['student' => $r->submission->student->name, 'class' => $r->submission->assignment->schoolClass->name, 'assignment' => $r->submission->assignment->title, 'score' => number_format((float) $r->total_score, 2), 'level' => $r->achievement_level, 'published' => $r->published_at?->format('d/m/Y') ?? '-']];
    }

    private function remedial(array $f): array
    {
        $participant = $f['participant_label'] ?? 'Peserta';
        $group = $f['group_label'] ?? 'Kelompok';

        [$q] = $this->grades($f);
        $q->where('remedial_status', '!=', RemedialStatus::None);

        return [$q, ['student' => $participant, 'class' => $group, 'assignment' => 'Tugas', 'score' => 'Nilai', 'status' => 'Status Remedial', 'due' => 'Tenggat'], fn ($r) => ['student' => $r->submission->student->name, 'class' => $r->submission->assignment->schoolClass->name, 'assignment' => $r->submission->assignment->title, 'score' => number_format((float) $r->total_score, 2), 'status' => $r->remedial_status->label(), 'due' => $r->remedial_due_at?->format('d/m/Y H:i') ?? '-']];
    }

    private function monthlyAssessments(array $f): array
    {
        $participant = $f['participant_label'] ?? 'Peserta';
        $group = $f['group_label'] ?? 'Kelompok';

        $q = MonthlyStudentAssessment::query()
            ->with(['student:id,name', 'schoolClass:id,name', 'assessor:id,name'])
            ->where('academic_year_id', $f['year'])
            ->when($f['program_batch_id'] ?? null, fn ($x, $id) => $x->where('program_batch_id', $id))
            ->when($f['class'] ?? null, fn ($x, $id) => $x->where('class_id', $id))
            ->when($f['semester'] ?? null, fn ($x, $s) => $x->where('semester', $s))
            ->when($f['date_from'] ?? null, fn ($x, $d) => $x->whereDate('assessed_at', '>=', $d))
            ->when($f['date_to'] ?? null, fn ($x, $d) => $x->whereDate('assessed_at', '<=', $d))
            ->latest('assessed_at');

        return [$q, ['student' => $participant, 'class' => $group, 'period' => 'Periode', 'semester' => 'Semester', 'score' => 'Nilai Akhir', 'level' => 'Level', 'published' => 'Publikasi', 'assessor' => 'Penilai'], fn ($r) => ['student' => $r->student?->name ?? '-', 'class' => $r->schoolClass?->name ?? '-', 'period' => $r->period_label, 'semester' => $r->semester, 'score' => number_format((float) $r->final_score, 2), 'level' => MonthlyStudentAssessment::achievementLabel((int) $r->achievement_level), 'published' => $r->is_published ? ($r->published_at?->format('d/m/Y') ?? 'Ya') : 'Draf', 'assessor' => $r->assessor?->name ?? '-']];
    }

    private function portfolio(array $f): array
    {
        $participant = $f['participant_label'] ?? 'Peserta';
        $group = $f['group_label'] ?? 'Kelompok';

        $q = PortfolioItem::query()->with(['owner:id,name', 'schoolClass:id,name'])->where('academic_year_id', $f['year'])->when($f['program_batch_id'] ?? null, fn ($x, $id) => $x->where('program_batch_id', $id))->when($f['class'] ?? null, fn ($x, $id) => $x->where('class_id', $id))->when($f['semester'] ?? null, fn ($x, $s) => $x->whereHas('submission.assignment.learningSession', fn ($y) => $y->where('semester', $s)))->when($f['date_from'] ?? null, fn ($x, $d) => $x->whereDate('created_at', '>=', $d))->when($f['date_to'] ?? null, fn ($x, $d) => $x->whereDate('created_at', '<=', $d))->latest();

        return [$q, ['student' => $participant, 'class' => $group, 'title' => 'Karya', 'type' => 'Jenis', 'visibility' => 'Visibilitas', 'approval' => 'Approval', 'featured' => 'Featured'], fn ($r) => ['student' => $r->owner->name, 'class' => $r->schoolClass->name, 'title' => $r->title, 'type' => $r->workTypeLabel(), 'visibility' => $r->visibility->label(), 'approval' => $r->approval_status->label(), 'featured' => $r->is_featured ? 'Ya' : 'Tidak']];
    }

    private function notes(array $f): array
    {
        $q = ImportantNote::query()->with('creator:id,name')->where('academic_year_id', $f['year'])->when($f['program_batch_id'] ?? null, fn ($x, $id) => $x->where('program_batch_id', $id))->when($f['date_from'] ?? null, fn ($x, $d) => $x->whereDate('note_date', '>=', $d))->when($f['date_to'] ?? null, fn ($x, $d) => $x->whereDate('note_date', '<=', $d))->latest('note_date');
        if ($f['viewer_is_leadership'] ?? false) {
            $q->where('status', 'verified');
        }

        return [$q, ['date' => 'Tanggal', 'priority' => 'Prioritas', 'note' => 'Catatan', 'resolution' => 'Penyelesaian', 'status' => 'Status', 'creator' => 'Pembuat'], fn ($r) => ['date' => $r->note_date->format('d/m/Y'), 'priority' => $r->priority->label(), 'note' => $r->note, 'resolution' => $r->resolution ?: '-', 'status' => $r->status->label(), 'creator' => $r->creator?->name ?? '-']];
    }

    private function activityDocumentations(array $f): array
    {
        $q = ActivityDocumentation::query()
            ->with(['creator:id,name', 'academicYear:id,name'])
            ->where('academic_year_id', $f['year'])
            ->when($f['program_batch_id'] ?? null, fn ($x, $id) => $x->where('program_batch_id', $id))
            ->when($f['date_from'] ?? null, fn ($x, $d) => $x->whereDate('activity_date', '>=', $d))
            ->when($f['date_to'] ?? null, fn ($x, $d) => $x->whereDate('activity_date', '<=', $d))
            ->latest('activity_date');

        return [$q, ['date' => 'Tanggal', 'title' => 'Judul', 'creator' => 'Pembuat', 'photo' => 'Foto', 'resource_url' => 'URL Dokumentasi', 'video_url' => 'URL Video'], fn ($r) => ['date' => $r->activity_date->format('d/m/Y'), 'title' => $r->title, 'creator' => $r->creator?->name ?? '-', 'photo' => $r->photo_path ? 'Ada' : '-', 'resource_url' => $r->resource_url ?: '-', 'video_url' => $r->video_url ?: '-']];
    }

    private function sessions(array $f): array
    {
        $q = LearningSession::query()->with('module:id,module_number,title')->where('academic_year_id', $f['year'])->when($f['program_batch_id'] ?? null, fn ($x, $id) => $x->where('program_batch_id', $id))->when($f['semester'] ?? null, fn ($x, $s) => $x->where('semester', $s))->when($f['date_from'] ?? null, fn ($x, $d) => $x->whereDate('scheduled_at', '>=', $d))->when($f['date_to'] ?? null, fn ($x, $d) => $x->whereDate('scheduled_at', '<=', $d))->orderBy('session_number');

        return [$q, ['number' => 'Pertemuan', 'module' => 'Modul', 'title' => 'Judul', 'semester' => 'Semester', 'status' => 'Status', 'scheduled' => 'Jadwal', 'published' => 'Publikasi'], fn ($r) => ['number' => $r->session_number, 'module' => 'M'.$r->module->module_number.' · '.$r->module->title, 'title' => $r->title, 'semester' => $r->semester, 'status' => $r->status->label(), 'scheduled' => $r->scheduled_at?->format('d/m/Y H:i') ?? '-', 'published' => $r->published_at?->format('d/m/Y H:i') ?? '-']];
    }

    private function documents(array $f, User $viewer): array
    {
        $q = app(DocumentAccessService::class)->queryFor($viewer)->where('academic_year_id', $f['year'])->when($f['program_batch_id'] ?? null, fn ($x, $id) => $x->where('program_batch_id', $id))->when($f['semester'] ?? null, fn ($x, $s) => $x->where('semester', $s))->when($f['date_from'] ?? null, fn ($x, $d) => $x->whereDate('published_at', '>=', $d))->when($f['date_to'] ?? null, fn ($x, $d) => $x->whereDate('published_at', '<=', $d))->latest('published_at');

        return [$q, ['title' => 'Dokumen', 'category' => 'Kategori', 'audience' => 'Audience', 'semester' => 'Semester', 'status' => 'Status', 'published' => 'Publikasi'], fn ($r) => ['title' => $r->title, 'category' => $r->category->label(), 'audience' => $r->audience->label(), 'semester' => $r->semester ?: '-', 'status' => $r->is_active ? 'Aktif' : 'Arsip', 'published' => $r->published_at?->format('d/m/Y H:i') ?? '-']];
    }
}

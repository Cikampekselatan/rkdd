<?php

namespace App\Services;

use App\Enums\ImportantNoteStatus;
use App\Enums\RoleSlug;
use App\Models\ImportantNote;
use App\Models\User;
use App\Notifications\SkuadActivityNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ImportantNoteService
{
    /** @param array<string, mixed> $data */
    public function save(?ImportantNote $note, array $data, User $actor): ImportantNote
    {
        $created = false;
        $savedNote = DB::transaction(function () use ($actor, $data, $note, &$created): ImportantNote {
            if (! $note) {
                $data['program_batch_id'] ??= app(ProgramContextService::class)->activeBatchId($actor);
                $note = ImportantNote::query()->create([...$data, 'created_by' => $actor->id, 'updated_by' => $actor->id]);
                $created = true;
                $this->audit($note, $actor, 'created', null, $note->status);
            } else {
                $old = $note->status;
                $note->update([...$data, 'program_batch_id' => $note->program_batch_id ?? $data['program_batch_id'] ?? app(ProgramContextService::class)->activeBatchId($actor), 'updated_by' => $actor->id]);
                $this->audit($note, $actor, 'updated', $old, $note->status);
            }

            return $note->refresh();
        });
        if ($created && $actor->hasRole(RoleSlug::Coach)) {
            $this->notifyTeachersToInitial($savedNote, $actor);
        }

        return $savedNote;
    }

    public function sign(ImportantNote $note, ?UploadedFile $file, ?string $drawnInitial, User $actor): ImportantNote
    {
        return DB::transaction(function () use ($actor, $drawnInitial, $file, $note): ImportantNote {
            $teacher = $actor->hasRole(RoleSlug::Teacher);
            $prefix = $teacher ? 'teacher' : 'coach';
            $oldPath = $note->{$prefix.'_initial_path'};
            $path = $this->initialPath($file, $drawnInitial, $prefix, $actor);
            $note->update([$prefix.'_initial_path' => $path, $prefix.'_initialed_by' => $actor->id, $prefix.'_initialed_at' => now()]);
            if ($oldPath) {
                Storage::disk('local')->delete($oldPath);
            }
            $this->audit($note, $actor, $prefix.'_initialed', $note->status, $note->status);
            if ($note->teacher_initial_path && $note->coach_initial_path) {
                $old = $note->status;
                $note->update(['status' => ImportantNoteStatus::Verified, 'verified_at' => now()]);
                $this->audit($note, $actor, 'verified', $old, $note->status);
            }

            return $note->refresh()->load('creator');
        });
        $note->creator?->notify(new SkuadActivityNotification(
            'important_note_initialed',
            $actor->hasRole(RoleSlug::Teacher) ? 'Catatan penting diparaf Guru/Pembina' : 'Catatan penting diparaf Instruktur/Coach',
            route('important-notes.show', $note),
            $note->note_date->translatedFormat('d F Y'),
            ['important_note_id' => $note->id, ...AnnouncementService::programMeta($note->program_batch_id)],
        ));
        if ($note->status === ImportantNoteStatus::Verified) {
            $this->notifyLeadershipVerified($note);
        }

        return $note;
    }

    private function initialPath(?UploadedFile $file, ?string $drawnInitial, string $prefix, User $actor): string
    {
        if ($file) {
            return $file->store('important-note-initials/'.$prefix.'/'.$actor->id, 'local');
        }

        $encoded = substr((string) $drawnInitial, strlen('data:image/png;base64,'));
        $binary = base64_decode($encoded, true);
        abort_if($binary === false, 422, 'Paraf langsung tidak valid.');

        $path = 'important-note-initials/'.$prefix.'/'.$actor->id.'/drawn-initial-'.now()->format('YmdHis').'-'.bin2hex(random_bytes(4)).'.png';
        Storage::disk('local')->put($path, $binary);

        return $path;
    }

    private function audit(ImportantNote $note, User $actor, string $event, ?ImportantNoteStatus $old, ImportantNoteStatus $new): void
    {
        $note->audits()->create(['user_id' => $actor->id, 'event' => $event, 'old_status' => $old, 'new_status' => $new]);
    }

    private function notifyTeachersToInitial(ImportantNote $note, User $actor): void
    {
        User::query()
            ->whereKeyNot($actor->id)
            ->where('status', 'active')
            ->whereHas('roles', fn ($query) => $query->where('slug', RoleSlug::Teacher->value))
            ->get()
            ->each
            ->notify(new SkuadActivityNotification(
                'important_note_review',
                'Catatan penting menunggu paraf',
                route('important-notes.show', $note),
                $actor->name.' · '.$note->note_date->translatedFormat('d F Y'),
                ['important_note_id' => $note->id, ...AnnouncementService::programMeta($note->program_batch_id)],
            ));
    }

    private function notifyLeadershipVerified(ImportantNote $note): void
    {
        User::query()
            ->where('status', 'active')
            ->whereHas('roles', fn ($query) => $query->whereIn('slug', [RoleSlug::SuperAdmin->value, RoleSlug::Admin->value, RoleSlug::Principal->value]))
            ->get()
            ->each
            ->notify(new SkuadActivityNotification(
                'important_note_verified',
                'Catatan penting terverifikasi',
                route('important-notes.show', $note),
                $note->note_date->translatedFormat('d F Y'),
                ['important_note_id' => $note->id, ...AnnouncementService::programMeta($note->program_batch_id)],
            ));
    }
}

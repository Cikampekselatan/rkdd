<?php

namespace App\Services;

use App\Enums\PortfolioApprovalStatus;
use App\Enums\PortfolioVisibility;
use App\Enums\RoleSlug;
use App\Models\PortfolioItem;
use App\Models\Submission;
use App\Models\User;
use App\Notifications\SkuadActivityNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PortfolioService
{
    public function save(?PortfolioItem $item, array $data, User $owner, array $files): PortfolioItem
    {
        $newPaths = [];
        $obsoletePaths = [];

        try {
            return DB::transaction(function () use ($item, $data, $owner, $files, &$newPaths, &$obsoletePaths): PortfolioItem {
                unset($data['thumbnail'], $data['initial_file'], $data['final_file']);

                if ($data['source_type'] === 'graded') {
                    $submission = Submission::with(['assignment', 'versions'])->findOrFail($data['submission_id']);
                    $data['academic_year_id'] = $submission->assignment->academic_year_id;
                    $data['program_batch_id'] = $submission->assignment->program_batch_id;
                    $data['class_id'] = $submission->assignment->class_id;
                    $data['initial_submission_version_id'] = $submission->versions->whereNotNull('submitted_at')->first()?->id;
                    $data['final_submission_version_id'] = $submission->versions->firstWhere('version_number', $submission->current_version_number)?->id;

                    if ($item?->source_type === 'independent') {
                        foreach (['initial_file_path', 'final_file_path'] as $column) {
                            if ($item->{$column}) {
                                $obsoletePaths[] = $item->{$column};
                            }
                        }
                        $data = [...$data, 'initial_file_path' => null, 'initial_original_name' => null, 'final_file_path' => null, 'final_original_name' => null, 'initial_url' => null, 'final_url' => null];
                    }
                } else {
                    $owner->loadMissing('studentProfile.schoolClass');
                    $activeMembership = app(ProgramContextService::class)->studentActiveMembership($owner);
                    $schoolClass = $activeMembership?->schoolClass ?? $owner->studentProfile->schoolClass;
                    $data['academic_year_id'] = $activeMembership?->academic_year_id ?? $schoolClass->academic_year_id;
                    $data['program_batch_id'] = $activeMembership?->program_batch_id ?? $owner->studentProfile?->program_batch_id ?? $schoolClass->program_batch_id;
                    $data['class_id'] = $activeMembership?->class_id ?? $owner->studentProfile->class_id;
                    $data['submission_id'] = null;
                    $data['initial_submission_version_id'] = null;
                    $data['final_submission_version_id'] = null;
                }

                $visibility = PortfolioVisibility::from($data['visibility']);
                $data['approval_status'] = $visibility->requiresApproval() ? PortfolioApprovalStatus::Pending : PortfolioApprovalStatus::NotRequired;
                $data['approved_by'] = null;
                $data['approved_at'] = null;
                $data['approval_note'] = null;
                $data['is_featured'] = false;

                foreach (['thumbnail', 'initial_file', 'final_file'] as $key) {
                    $file = $files[$key] ?? null;
                    if (! $file instanceof UploadedFile) {
                        continue;
                    }

                    $column = $key.'_path';
                    if ($item?->{$column}) {
                        $obsoletePaths[] = $item->{$column};
                    }

                    $path = $file->storeAs('portfolios/'.$owner->id, Str::uuid().'.'.$file->guessExtension(), 'local');
                    $newPaths[] = $path;
                    $data[$column] = $path;

                    if ($key !== 'thumbnail') {
                        $data[str_replace('_file', '_original_name', $key)] = $file->getClientOriginalName();
                    }
                }

                $event = $item ? 'updated' : 'created';
                $item ??= new PortfolioItem(['user_id' => $owner->id]);
                $item->fill([...$data, 'slug' => Str::slug($data['title']).'-'.($item->id ?: Str::lower(Str::random(6)))])->save();
                $item->audits()->create(['user_id' => $owner->id, 'event' => $event, 'context' => ['visibility' => $item->visibility->value]]);
                if ($item->approval_status === PortfolioApprovalStatus::Pending && $owner->hasRole(RoleSlug::Student)) {
                    $this->notifyPortfolioNeedsReview($item, $owner);
                }

                foreach (array_unique($obsoletePaths) as $path) {
                    Storage::disk('local')->delete($path);
                }

                return $item->refresh();
            });
        } catch (\Throwable $exception) {
            foreach ($newPaths as $path) {
                Storage::disk('local')->delete($path);
            }

            throw $exception;
        }
    }

    public function review(PortfolioItem $item, string $decision, ?string $note, User $actor): PortfolioItem
    {
        $item->update([
            'approval_status' => $decision,
            'approval_note' => $note,
            'approved_by' => $actor->id,
            'approved_at' => $decision === 'approved' ? now() : null,
            'is_featured' => $decision === 'approved' ? $item->is_featured : false,
        ]);
        $item->audits()->create(['user_id' => $actor->id, 'event' => $decision, 'context' => ['note' => $note]]);
        $item->loadMissing('owner');
        $item->owner?->notify(new SkuadActivityNotification(
            'portfolio_review',
            $decision === PortfolioApprovalStatus::Approved->value ? 'Portofolio disetujui' : 'Portofolio perlu revisi',
            route('student.portfolio.show', $item),
            $item->title,
            ['portfolio_item_id' => $item->id, 'decision' => $decision, ...AnnouncementService::programMeta($item->program_batch_id)],
        ));

        return $item->refresh();
    }

    public function toggleFeatured(PortfolioItem $item, User $actor): PortfolioItem
    {
        $item->update(['is_featured' => ! $item->is_featured]);
        $item->audits()->create(['user_id' => $actor->id, 'event' => $item->is_featured ? 'featured' : 'unfeatured']);
        $item->loadMissing('owner');
        $item->owner?->notify(new SkuadActivityNotification(
            'portfolio_featured',
            $item->is_featured ? 'Karyamu dijadikan unggulan' : 'Status unggulan portofolio diperbarui',
            route('student.portfolio.show', $item),
            $item->title,
            ['portfolio_item_id' => $item->id, ...AnnouncementService::programMeta($item->program_batch_id)],
        ));

        return $item->refresh();
    }

    public function delete(PortfolioItem $item, User $actor): void
    {
        DB::transaction(function () use ($item, $actor): void {
            foreach ([$item->thumbnail_path, $item->initial_file_path, $item->final_file_path] as $path) {
                if ($path) {
                    Storage::disk('local')->delete($path);
                }
            }

            $item->audits()->create(['user_id' => $actor->id, 'event' => 'deleted']);
            $item->delete();
        });
    }

    private function notifyPortfolioNeedsReview(PortfolioItem $item, User $owner): void
    {
        User::query()
            ->whereKeyNot($owner->id)
            ->where('status', 'active')
            ->whereHas('roles', fn ($query) => $query->whereIn('slug', [RoleSlug::Teacher->value, RoleSlug::Coach->value]))
            ->get()
            ->each
            ->notify(new SkuadActivityNotification(
                'portfolio_pending',
                'Portofolio menunggu approval',
                route('teacher.portfolio.show', $item),
                $owner->name.' · '.$item->title,
                ['portfolio_item_id' => $item->id, ...AnnouncementService::programMeta($item->program_batch_id)],
            ));
    }
}

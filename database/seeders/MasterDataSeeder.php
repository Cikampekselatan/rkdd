<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\ProgramBatch;
use App\Models\SchoolClass;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $academicYear = AcademicYear::query()->updateOrCreate(
            ['name' => '2026/2027'],
            [
                'starts_on' => '2026-07-01',
                'ends_on' => '2027-06-30',
                'is_active' => true,
            ],
        );

        AcademicYear::query()->whereKeyNot($academicYear->id)->update(['is_active' => false]);

        $this->call(ProgramFoundationSeeder::class);
        $programBatch = ProgramBatch::query()->where('slug', 'skuad-2026-2027')->first();

        $group = SchoolClass::withTrashed()->firstOrNew([
            'academic_year_id' => $academicYear->id,
            'code' => 'SKUAD-2026',
        ]);
        $groupData = [
            'name' => 'Kelompok SKUAD 2026/2027',
            'grade_level' => null,
            'capacity' => 100,
            'is_active' => true,
        ];

        if ($programBatch && Schema::hasColumn('classes', 'program_batch_id')) {
            $groupData['program_batch_id'] = $programBatch->id;
        }

        $group->fill($groupData);
        $group->deleted_at = null;
        $group->save();

        SchoolClass::query()
            ->where('academic_year_id', $academicYear->id)
            ->whereKeyNot($group->id)
            ->get()
            ->each(function (SchoolClass $oldGroup): void {
                $hasReferences = $oldGroup->studentProfiles()->exists()
                    || $oldGroup->classMemberships()->exists()
                    || $oldGroup->registrationCodes()->exists()
                    || $oldGroup->attendanceSessions()->exists();

                $hasReferences ? $oldGroup->update(['is_active' => false]) : $oldGroup->delete();
            });
    }
}

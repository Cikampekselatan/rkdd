<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramBatch;
use Illuminate\Database\Seeder;

class ProgramFoundationSeeder extends Seeder
{
    public function run(): void
    {
        $academicYear = AcademicYear::query()->where('is_active', true)->first()
            ?? AcademicYear::query()->orderByDesc('starts_on')->first();

        $program = Program::query()->updateOrCreate(
            ['slug' => 'skuad'],
            [
                'name' => 'SKUAD',
                'type' => 'ekstrakurikuler',
                'description' => 'Siswa Kreatif Update Digital sebagai program awal RKDD Cikampek Selatan.',
                'primary_color' => '#0f766e',
                'secondary_color' => '#0f172a',
                'accent_color' => '#f59e0b',
                'is_active' => true,
            ],
        );

        $institution = Institution::query()->updateOrCreate(
            ['slug' => 'rkdd-cikampek-selatan'],
            [
                'name' => 'RKDD Cikampek Selatan',
                'type' => 'rkdd',
                'address' => 'Desa Cikampek Selatan',
                'description' => 'Lembaga default untuk menjaga data SKUAD existing tetap memiliki konteks awal.',
                'is_active' => true,
            ],
        );

        ProgramBatch::query()->updateOrCreate(
            ['slug' => 'skuad-2026-2027'],
            [
                'program_id' => $program->id,
                'institution_id' => $institution->id,
                'name' => 'SKUAD 2026/2027',
                'period_label' => $academicYear?->name ?? '2026/2027',
                'starts_on' => $academicYear?->starts_on ?? '2026-07-01',
                'ends_on' => $academicYear?->ends_on ?? '2027-06-30',
                'audience_type' => 'school',
                'participant_label' => 'Siswa',
                'is_active' => true,
            ],
        );
    }
}

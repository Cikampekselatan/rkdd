<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramBatch;
use App\Models\SchoolClass;
use Database\Seeders\MasterDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MasterDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_data_seeder_is_idempotent_and_keeps_one_active_year(): void
    {
        $this->seed(MasterDataSeeder::class);
        $this->seed(MasterDataSeeder::class);

        $this->assertSame(1, AcademicYear::query()->count());
        $this->assertSame(1, Program::query()->count());
        $this->assertSame(1, Institution::query()->count());
        $this->assertSame(1, ProgramBatch::query()->count());
        $this->assertSame(1, AcademicYear::query()->where('is_active', true)->count());
        $this->assertSame(1, SchoolClass::query()->count());
        $this->assertTrue(Schema::hasColumn('classes', 'program_batch_id'));
        $this->assertTrue(Schema::hasColumn('registration_codes', 'program_batch_id'));
        $this->assertTrue(Schema::hasColumn('assignments', 'program_batch_id'));
        $this->assertDatabaseHas('classes', [
            'code' => 'SKUAD-2026',
            'name' => 'Kelompok SKUAD 2026/2027',
            'grade_level' => null,
            'capacity' => 100,
            'program_batch_id' => ProgramBatch::query()->where('slug', 'skuad-2026-2027')->value('id'),
        ]);
        $this->assertDatabaseHas('programs', [
            'slug' => 'skuad',
            'name' => 'SKUAD',
        ]);
        $this->assertDatabaseHas('institutions', [
            'slug' => 'rkdd-cikampek-selatan',
            'name' => 'RKDD Cikampek Selatan',
        ]);
        $this->assertDatabaseHas('program_batches', [
            'slug' => 'skuad-2026-2027',
            'period_label' => '2026/2027',
            'participant_label' => 'Siswa',
        ]);
    }
}

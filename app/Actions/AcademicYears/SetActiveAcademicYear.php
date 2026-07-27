<?php

namespace App\Actions\AcademicYears;

use App\Models\AcademicYear;
use Illuminate\Support\Facades\DB;

class SetActiveAcademicYear
{
    public function execute(AcademicYear $academicYear): AcademicYear
    {
        return DB::transaction(function () use ($academicYear): AcademicYear {
            AcademicYear::query()->where('is_active', true)->whereKeyNot($academicYear->id)->update(['is_active' => false]);
            $academicYear->update(['is_active' => true]);

            return $academicYear->refresh();
        });
    }
}

<?php

namespace Database\Factories;

use App\Enums\PortfolioApprovalStatus;
use App\Enums\PortfolioVisibility;
use App\Enums\PortfolioWorkType;
use App\Models\AcademicYear;
use App\Models\PortfolioItem;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<PortfolioItem> */ class PortfolioItemFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence(4);

        return ['academic_year_id' => AcademicYear::factory(), 'class_id' => fn ($a) => SchoolClass::factory()->create(['academic_year_id' => $a['academic_year_id']])->id, 'user_id' => User::factory(), 'source_type' => 'independent', 'title' => $title, 'slug' => Str::slug($title).'-'.Str::lower(Str::random(6)), 'work_type' => PortfolioWorkType::Poster, 'description' => fake()->paragraph(), 'reflection' => fake()->paragraph(), 'sources' => null, 'ai_used' => false, 'visibility' => PortfolioVisibility::Private, 'approval_status' => PortfolioApprovalStatus::NotRequired, 'is_featured' => false];
    }
}

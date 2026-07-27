<?php

use App\Enums\PortfolioWorkType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_work_type_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('slug', 140);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['program_id', 'slug']);
        });

        $defaults = collect(PortfolioWorkType::cases())
            ->map(fn (PortfolioWorkType $type, int $index): array => [
                'slug' => $type->value,
                'name' => $type->label(),
                'sort_order' => ($index + 1) * 10,
            ])
            ->all();

        DB::table('programs')->whereNull('deleted_at')->orderBy('id')->get(['id', 'type'])->each(function (object $program) use ($defaults): void {
            $now = now();
            foreach ($defaults as $default) {
                DB::table('portfolio_work_type_options')->updateOrInsert(
                    ['program_id' => $program->id, 'slug' => Str::slug($default['slug'], '_')],
                    [
                        'name' => $default['name'],
                        'sort_order' => $default['sort_order'],
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_work_type_options');
    }
};

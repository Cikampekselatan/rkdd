<?php

use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\SuperAdmin\InstitutionController;
use App\Http\Controllers\SuperAdmin\KnowledgeResourceController;
use App\Http\Controllers\SuperAdmin\LandingCarouselSlideController;
use App\Http\Controllers\SuperAdmin\LandingProfileVideoController;
use App\Http\Controllers\SuperAdmin\PortfolioWorkTypeOptionController;
use App\Http\Controllers\SuperAdmin\ProgramBatchController;
use App\Http\Controllers\SuperAdmin\ProgramController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:super-admin'])->group(function (): void {
    Route::prefix('super-admin')->name('super-admin.')->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::resource('programs', ProgramController::class)->except('show');
        Route::resource('institutions', InstitutionController::class)->except('show');
        Route::resource('program-batches', ProgramBatchController::class)
            ->parameters(['program-batches' => 'program_batch'])
            ->except('show');
        Route::resource('portfolio-work-types', PortfolioWorkTypeOptionController::class)
            ->parameters(['portfolio-work-types' => 'portfolioWorkType'])
            ->except('show');
        Route::resource('landing-slides', LandingCarouselSlideController::class)
            ->parameters(['landing-slides' => 'landing_slide'])
            ->except('show');
        Route::resource('knowledge-resources', KnowledgeResourceController::class)
            ->parameters(['knowledge-resources' => 'knowledge_resource'])
            ->except('show');
        Route::get('profile-video', [LandingProfileVideoController::class, 'edit'])->name('profile-video.edit');
        Route::put('profile-video', [LandingProfileVideoController::class, 'update'])->name('profile-video.update');
    });

    Route::get('/design-system', function () {
        abort_unless(app()->environment(['local', 'testing']), 404);

        return view('design-system.index');
    })->name('super-admin.design-system');
});

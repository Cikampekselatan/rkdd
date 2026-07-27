<?php

use App\Http\Controllers\Teacher\AnnouncementController;
use App\Http\Controllers\Teacher\AssignmentController;
use App\Http\Controllers\Teacher\AttendanceController;
use App\Http\Controllers\Teacher\DashboardController;
use App\Http\Controllers\Teacher\DiscussionModerationController;
use App\Http\Controllers\Teacher\GradeController;
use App\Http\Controllers\Teacher\LearningMaterialController;
use App\Http\Controllers\Teacher\LearningModuleController;
use App\Http\Controllers\Teacher\LearningSessionController;
use App\Http\Controllers\Teacher\MonthlyStudentAssessmentController;
use App\Http\Controllers\Teacher\PortfolioController;
use App\Http\Controllers\Teacher\ProjectGroupController;
use App\Http\Controllers\Teacher\RubricController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:teacher,coach'])->prefix('teacher')->name('teacher.')->group(function (): void {
    Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    Route::get('attendance/{attendance_session}/export/csv', [AttendanceController::class, 'exportCsv'])->name('attendance.export.csv');
    Route::get('attendance/{attendance_session}/print', [AttendanceController::class, 'print'])->name('attendance.print');
    Route::get('attendance/{attendance_session}', [AttendanceController::class, 'show'])->name('attendance.show');
    Route::put('attendance/{attendance_session}', [AttendanceController::class, 'update'])->name('attendance.update');
    Route::patch('attendance/{attendance_session}/check-in', [AttendanceController::class, 'enableCheckIn'])->name('attendance.check-in.enable');
    Route::patch('attendance/{attendance_session}/check-in/disable', [AttendanceController::class, 'disableCheckIn'])->name('attendance.check-in.disable');
    Route::patch('attendance/{attendance_session}/close', [AttendanceController::class, 'close'])->name('attendance.close');
    Route::patch('attendance/records/{attendance_record}/amend', [AttendanceController::class, 'amend'])->name('attendance.records.amend');
    Route::resource('project-groups', ProjectGroupController::class)->except('destroy')->parameters(['project-groups' => 'project_group']);
    Route::post('project-groups/{project_group}/projects', [ProjectGroupController::class, 'storeProject'])->name('project-groups.projects.store');
    Route::get('group-projects/{group_project}/assessment', [ProjectGroupController::class, 'editAssessment'])->name('group-projects.assessment.edit');
    Route::put('group-projects/{group_project}/assessment', [ProjectGroupController::class, 'updateAssessment'])->name('group-projects.assessment.update');
});

Route::middleware(['auth', 'role:teacher,coach'])->prefix('teacher')->name('teacher.')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('learning', [LearningModuleController::class, 'index'])->name('learning.index');
    Route::get('learning/modules/create', [LearningModuleController::class, 'create'])->name('learning.modules.create');
    Route::post('learning/modules', [LearningModuleController::class, 'store'])->name('learning.modules.store');
    Route::get('learning/modules/{learning_module}/edit', [LearningModuleController::class, 'edit'])->name('learning.modules.edit');
    Route::put('learning/modules/{learning_module}', [LearningModuleController::class, 'update'])->name('learning.modules.update');
    Route::delete('learning/modules/{learning_module}', [LearningModuleController::class, 'destroy'])->name('learning.modules.destroy');
    Route::get('learning/sessions/create', [LearningSessionController::class, 'create'])->name('learning.sessions.create');
    Route::post('learning/sessions', [LearningSessionController::class, 'store'])->name('learning.sessions.store');
    Route::get('learning/sessions/{learning_session}/edit', [LearningSessionController::class, 'edit'])->name('learning.sessions.edit');
    Route::put('learning/sessions/{learning_session}', [LearningSessionController::class, 'update'])->name('learning.sessions.update');
    Route::get('learning/sessions/{learning_session}/preview', [LearningSessionController::class, 'preview'])->name('learning.sessions.preview');
    Route::patch('learning/sessions/{learning_session}/publish', [LearningSessionController::class, 'publish'])->name('learning.sessions.publish');
    Route::delete('learning/sessions/{learning_session}', [LearningSessionController::class, 'destroy'])->name('learning.sessions.destroy');
    Route::post('learning/sessions/{learning_session}/materials', [LearningMaterialController::class, 'store'])->name('learning.materials.store');
    Route::get('learning/materials/{learning_material}/edit', [LearningMaterialController::class, 'edit'])->name('learning.materials.edit');
    Route::put('learning/materials/{learning_material}', [LearningMaterialController::class, 'update'])->name('learning.materials.update');
    Route::delete('learning/materials/{learning_material}', [LearningMaterialController::class, 'destroy'])->name('learning.materials.destroy');
    Route::resource('assignments', AssignmentController::class);
    Route::get('submissions/{submission}', [AssignmentController::class, 'submission'])->name('submissions.show');
    Route::patch('submissions/{submission}/review', [AssignmentController::class, 'review'])->name('submissions.review');
    Route::patch('submissions/{submission}/revision', [AssignmentController::class, 'revision'])->name('submissions.revision');
    Route::resource('rubrics', RubricController::class);
    Route::get('monthly-assessments/export/csv', [MonthlyStudentAssessmentController::class, 'exportCsv'])->name('monthly-assessments.export.csv');
    Route::get('monthly-assessments/export/print', [MonthlyStudentAssessmentController::class, 'print'])->name('monthly-assessments.print');
    Route::resource('monthly-assessments', MonthlyStudentAssessmentController::class)
        ->except(['show', 'destroy'])
        ->parameters(['monthly-assessments' => 'monthly_assessment']);
    Route::get('grades/{submission}', [GradeController::class, 'edit'])->name('grades.edit');
    Route::put('grades/{submission}', [GradeController::class, 'update'])->name('grades.update');
    Route::patch('grades/remedial/{grade}/complete', [GradeController::class, 'completeRemedial'])->name('grades.remedial.complete');
    Route::get('portfolio', [PortfolioController::class, 'index'])->name('portfolio.index');
    Route::get('portfolio/{portfolio_item}', [PortfolioController::class, 'show'])->name('portfolio.show');
    Route::patch('portfolio/{portfolio_item}/review', [PortfolioController::class, 'review'])->name('portfolio.review');
    Route::patch('portfolio/{portfolio_item}/feature', [PortfolioController::class, 'feature'])->name('portfolio.feature');
    Route::get('announcements/create', [AnnouncementController::class, 'create'])->name('announcements.create');
    Route::post('announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::get('announcements/{announcement}/edit', [AnnouncementController::class, 'edit'])->name('announcements.edit');
    Route::put('announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
    Route::delete('announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
});

Route::middleware(['auth', 'role:super-admin,admin,teacher,coach'])->prefix('teacher')->name('teacher.')->group(function (): void {
    Route::patch('discussions/{discussion_topic}/moderate', [DiscussionModerationController::class, 'topic'])->name('discussions.moderate');
    Route::patch('discussion-posts/{discussion_post}/moderate', [DiscussionModerationController::class, 'post'])->name('discussion-posts.moderate');
});

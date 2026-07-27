<?php

use App\Http\Controllers\Student\AssignmentController;
use App\Http\Controllers\Student\AttendanceCheckInController;
use App\Http\Controllers\Student\AttendanceController;
use App\Http\Controllers\Student\DashboardController;
use App\Http\Controllers\Student\DocumentResourceController;
use App\Http\Controllers\Student\GradeController;
use App\Http\Controllers\Student\LearningController;
use App\Http\Controllers\Student\PortfolioController;
use App\Http\Controllers\Student\ProgramEnrollmentController;
use App\Http\Controllers\Student\ProjectGroupController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('student')->name('student.')->group(function (): void {
    Route::get('attendance/check-in/{attendance_session}/{token}', [AttendanceCheckInController::class, 'show'])->name('attendance.check-in.show');
    Route::post('attendance/check-in/{attendance_session}/{token}', [AttendanceCheckInController::class, 'store'])->name('attendance.check-in.store');
});

Route::middleware(['auth', 'role:student'])->prefix('student')->name('student.')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('programs', [ProgramEnrollmentController::class, 'index'])->name('programs.index');
    Route::post('programs/join', [ProgramEnrollmentController::class, 'join'])->name('programs.join');
    Route::get('learning', [LearningController::class, 'index'])->name('learning.index');
    Route::get('learning/{learning_session}', [LearningController::class, 'show'])->name('learning.show');
    Route::post('learning/{learning_session}/progress', [LearningController::class, 'updateProgress'])->name('learning.progress');
    Route::get('documents', [DocumentResourceController::class, 'index'])->name('documents.index');
    Route::get('documents/{document_resource}', [DocumentResourceController::class, 'show'])->name('documents.show');
    Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('attendance/scan', [AttendanceCheckInController::class, 'scan'])->name('attendance.scan');
    Route::get('project-groups', [ProjectGroupController::class, 'index'])->name('project-groups.index');
    Route::get('project-groups/{project_group}', [ProjectGroupController::class, 'show'])->name('project-groups.show');
    Route::get('assignments', [AssignmentController::class, 'index'])->name('assignments.index');
    Route::get('assignments/{assignment}', [AssignmentController::class, 'show'])->name('assignments.show');
    Route::post('assignments/{assignment}', [AssignmentController::class, 'save'])->name('assignments.save');
    Route::get('grades', [GradeController::class, 'index'])->name('grades.index');
    Route::get('grades/monthly/{monthlyStudentAssessment}', [GradeController::class, 'monthly'])->name('grades.monthly.show');
    Route::get('grades/group-projects/{groupProjectAssessment}', [GradeController::class, 'groupProject'])->name('grades.group-projects.show');
    Route::get('grades/{grade}', [GradeController::class, 'show'])->name('grades.show');
    Route::post('grades/{grade}/remedial', [GradeController::class, 'remedial'])->name('grades.remedial');
    Route::resource('portfolio', PortfolioController::class)->parameters(['portfolio' => 'portfolio_item']);
    Route::get('portfolio/{portfolio_item}/print', [PortfolioController::class, 'print'])->name('portfolio.print');
});

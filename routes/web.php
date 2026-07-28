<?php

use App\Http\Controllers\Account\ProfilePhotoController;
use App\Http\Controllers\Auth\StudentRegistrationController;
use App\Http\Controllers\Documents\DocumentResourceController;
use App\Http\Controllers\Interaction\AnnouncementController as InteractionAnnouncementController;
use App\Http\Controllers\Interaction\DiscussionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PortfolioAssetController;
use App\Http\Controllers\ProgramContextController;
use App\Http\Controllers\PublicBestWorkController;
use App\Http\Controllers\PublicKnowledgeResourceController;
use App\Http\Controllers\PublicLandingController;
use App\Http\Controllers\PublicPortfolioController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ShowcaseHighlightController;
use App\Http\Controllers\Staff\ActivityDocumentationController;
use App\Http\Controllers\Staff\ImportantNoteController;
use App\Http\Controllers\Staff\TeacherActivityLogController;
use App\Http\Controllers\SubmissionFileController;
use Illuminate\Support\Facades\Route;

Route::get('/', PublicLandingController::class)->name('home');
Route::get('ruang-ilmu', [PublicKnowledgeResourceController::class, 'index'])->name('knowledge.index');
Route::get('karya-terbaik', PublicBestWorkController::class)->name('best-works.index');
Route::get('daftar-siswa', [StudentRegistrationController::class, 'show'])->name('student.register');
Route::post('daftar-siswa', [StudentRegistrationController::class, 'store'])->name('student.register.store');
Route::delete('daftar-siswa', [StudentRegistrationController::class, 'reset'])->name('student.register.reset');
Route::get('showcase', [PublicPortfolioController::class, 'index'])->name('portfolio.public.index');
Route::get('showcase/{portfolio_item}', [PublicPortfolioController::class, 'show'])->name('portfolio.public.show');
Route::get('portfolio-assets/{portfolio_item}/{kind}', PortfolioAssetController::class)->name('portfolio.assets');

Route::middleware(['auth', 'role:super-admin,admin,teacher,coach'])->resource('showcase-highlights', ShowcaseHighlightController::class)->except('show');

Route::middleware('auth')->prefix('account')->name('account.')->group(function (): void {
    Route::get('profile-photo', [ProfilePhotoController::class, 'edit'])->name('profile-photo.edit');
    Route::put('profile-photo', [ProfilePhotoController::class, 'update'])->name('profile-photo.update');
    Route::delete('profile-photo', [ProfilePhotoController::class, 'destroy'])->name('profile-photo.destroy');
});

Route::middleware(['auth', 'role:super-admin,admin,teacher,coach,principal,student'])->put('program-context', [ProgramContextController::class, 'update'])->name('program-context.update');

Route::middleware(['auth', 'role:super-admin,admin,teacher,coach,principal'])->prefix('reports')->name('reports.')->group(function (): void {
    Route::get('/', [ReportController::class, 'index'])->name('index');
    Route::get('{type}/matrix', [ReportController::class, 'matrix'])->name('matrix');
    Route::get('{type}/export.csv', [ReportController::class, 'exportCsv'])->name('export.csv');
    Route::get('{type}', [ReportController::class, 'show'])->name('show');
    Route::get('{type}/print', [ReportController::class, 'print'])->name('print');
});

Route::middleware(['auth', 'role:super-admin,admin,teacher,coach,student,principal'])->prefix('interactions')->name('interactions.')->group(function (): void {
    Route::get('announcements', [InteractionAnnouncementController::class, 'index'])->name('announcements.index');
    Route::get('announcements/{announcement}', [InteractionAnnouncementController::class, 'show'])->name('announcements.show');
    Route::post('announcements/{announcement}/read', [InteractionAnnouncementController::class, 'read'])->name('announcements.read');
    Route::get('discussions', [DiscussionController::class, 'index'])->name('discussions.index');
    Route::post('discussions', [DiscussionController::class, 'store'])->name('discussions.store');
    Route::get('discussions/{discussion_topic}', [DiscussionController::class, 'show'])->name('discussions.show');
    Route::post('discussions/{discussion_topic}/posts', [DiscussionController::class, 'post'])->name('discussions.posts.store');
    Route::post('discussion-posts/{discussion_post}/report', [DiscussionController::class, 'report'])->name('discussions.posts.report');
    Route::get('notifications/{notification}', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
});

Route::middleware(['auth', 'role:super-admin,admin,teacher,coach,principal'])->prefix('documents')->name('documents.')->group(function (): void {
    Route::get('/', [DocumentResourceController::class, 'index'])->name('index');
    Route::get('create', [DocumentResourceController::class, 'create'])->name('create');
    Route::post('/', [DocumentResourceController::class, 'store'])->name('store');
    Route::get('{document_resource}', [DocumentResourceController::class, 'show'])->name('show');
    Route::get('{document_resource}/edit', [DocumentResourceController::class, 'edit'])->name('edit');
    Route::put('{document_resource}', [DocumentResourceController::class, 'update'])->name('update');
    Route::patch('{document_resource}/publish', [DocumentResourceController::class, 'publish'])->name('publish');
    Route::patch('{document_resource}/archive', [DocumentResourceController::class, 'archive'])->name('archive');
    Route::patch('{document_resource}/pin', [DocumentResourceController::class, 'pin'])->name('pin');
    Route::delete('{document_resource}', [DocumentResourceController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth', 'role:teacher,coach,student'])->get('submission-files/{submission_file}', SubmissionFileController::class)->name('submission-files.download');

Route::middleware(['auth', 'role:super-admin,admin,teacher,coach,principal'])->group(function (): void {
    Route::resource('activity-documentations', ActivityDocumentationController::class);
    Route::get('activity-logs/print-report', [TeacherActivityLogController::class, 'printIndex'])->name('activity-logs.print-index');
    Route::resource('activity-logs', TeacherActivityLogController::class)->parameters(['activity-logs' => 'teacher_activity_log'])->except(['destroy']);
    Route::patch('activity-logs/{teacher_activity_log}/review', [TeacherActivityLogController::class, 'review'])->name('activity-logs.review');
    Route::get('activity-logs/{teacher_activity_log}/print', [TeacherActivityLogController::class, 'print'])->name('activity-logs.print');
    Route::get('activity-logs/{teacher_activity_log}/signature', [TeacherActivityLogController::class, 'signature'])->name('activity-logs.signature');
    Route::get('important-notes/print-report', [ImportantNoteController::class, 'printIndex'])->name('important-notes.print-index');
    Route::resource('important-notes', ImportantNoteController::class)->except(['destroy']);
    Route::post('important-notes/{important_note}/sign', [ImportantNoteController::class, 'sign'])->name('important-notes.sign');
    Route::get('important-notes/{important_note}/print', [ImportantNoteController::class, 'print'])->name('important-notes.print');
    Route::get('important-notes/{important_note}/initial/{role}', [ImportantNoteController::class, 'initial'])->name('important-notes.initial');
    Route::view('/design-system', 'design-system.index')
    ->name('design-system');
});


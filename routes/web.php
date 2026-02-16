<?php

declare(strict_types=1);

use App\Http\Controllers\AutoAssignController;
use App\Http\Controllers\CheckConflictsController;
use App\Http\Controllers\ConflictResolutionController;
use App\Http\Controllers\MyAssignmentController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\QualificationController;
use App\Http\Controllers\ResourceAbsenceController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\ResourceQualificationController;
use App\Http\Controllers\ResourceTypeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\TaskAssignmentController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskRequirementController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UtilizationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', fn () => Inertia::render('Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
]))->name('home');

Route::get('dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::resource('resource-types', ResourceTypeController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('roles', RoleController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('permissions', PermissionController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('qualifications', QualificationController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('resources', ResourceController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('resource-absences', ResourceAbsenceController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('resource-qualifications', ResourceQualificationController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('tasks', TaskController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('task-requirements', TaskRequirementController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('task-assignments', TaskAssignmentController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::post('task-assignments/auto-assign', AutoAssignController::class)->name('task-assignments.auto-assign');
    Route::get('my-assignments', [MyAssignmentController::class, 'index'])->name('my-assignments.index');
    Route::put('my-assignments/{my_assignment}', [MyAssignmentController::class, 'update'])->name('my-assignments.update');
    Route::post('check-conflicts', CheckConflictsController::class)->name('check-conflicts');
    Route::post('conflict-resolution', ConflictResolutionController::class)->name('conflict-resolution');
    Route::get('schedule', ScheduleController::class)->name('schedule');
    Route::get('utilization', UtilizationController::class)->name('utilization');
    Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
});

require __DIR__.'/settings.php';

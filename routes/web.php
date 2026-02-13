<?php

declare(strict_types=1);

use App\Http\Controllers\PermissionController;
use App\Http\Controllers\QualificationController;
use App\Http\Controllers\ResourceAbsenceController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\ResourceQualificationController;
use App\Http\Controllers\ResourceTypeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TaskAssignmentController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskRequirementController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', fn () => Inertia::render('Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
]))->name('home');

Route::get('dashboard', fn () => Inertia::render('Dashboard'))->middleware(['auth', 'verified'])->name('dashboard');

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
    Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
});

require __DIR__.'/settings.php';

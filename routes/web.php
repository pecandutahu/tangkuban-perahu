<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Web UI Routes for Payroll
    Route::middleware(['permission:view-payroll'])->group(function () {
        Route::get('/payroll-periods', [\App\Http\Controllers\Web\PayrollController::class, 'index'])->name('payroll.index');
        Route::get('/payroll-periods/{id}', [\App\Http\Controllers\Web\PayrollController::class, 'show'])->name('payroll.show');
        
        // Payroll Operations (Protected by Session Auth & Specific Spatie Permissions)
        Route::post('/payroll-periods/generate', [\App\Http\Controllers\Api\PayrollGenerationController::class, 'generate'])
            ->middleware('permission:generate-payroll');
            
        Route::post('/payroll-periods/{id}/items/{itemId}/regenerate', [\App\Http\Controllers\Api\PayrollGenerationController::class, 'regenerateItem'])
            ->middleware('permission:generate-payroll');
            
        Route::get('/payroll-periods/{id}/import-template', [\App\Http\Controllers\Api\PayrollImportController::class, 'downloadTemplate'])
            ->middleware('permission:edit-payroll');
            
        Route::post('/payroll-periods/{id}/import-variables', [\App\Http\Controllers\Api\PayrollImportController::class, 'import'])
            ->middleware('permission:edit-payroll');
        
        // Status Transitions
        Route::post('/payroll-periods/{id}/mark-as-reviewed', [\App\Http\Controllers\Api\PayrollStatusController::class, 'markAsReviewed'])
            ->middleware('permission:generate-payroll');
            
        Route::post('/payroll-periods/{id}/mark-as-approved', [\App\Http\Controllers\Api\PayrollStatusController::class, 'markAsApproved'])
            ->middleware('permission:approve-payroll');
            
        Route::post('/payroll-periods/{id}/reject-to-draft', [\App\Http\Controllers\Api\PayrollStatusController::class, 'rejectToDraft'])
            ->middleware('permission:approve-payroll');
            
        Route::post('/payroll-periods/{id}/mark-as-rejected', [\App\Http\Controllers\Api\PayrollStatusController::class, 'markAsRejected'])
            ->middleware('permission:approve-payroll');
            
        Route::post('/payroll-periods/{id}/mark-as-paid', [\App\Http\Controllers\Api\PayrollStatusController::class, 'markAsPaid'])
            ->middleware('permission:approve-payroll');
            
        Route::post('/payroll-periods/{id}/mark-as-closed', [\App\Http\Controllers\Api\PayrollStatusController::class, 'markAsClosed'])
            ->middleware('role:Super Admin|HR Admin');
    });

    // Master Data Routes (Protected by Spatie)
    Route::middleware(['permission:view-master-data'])->group(function () {
        Route::resource('branches', \App\Http\Controllers\Web\BranchController::class)->except(['create', 'edit', 'show']);
        Route::resource('departments', \App\Http\Controllers\Web\DepartmentController::class)->except(['create', 'edit', 'show']);
        Route::resource('positions', \App\Http\Controllers\Web\PositionController::class)->except(['create', 'edit', 'show']);
        Route::resource('payroll-components', \App\Http\Controllers\Web\PayrollComponentController::class)->except(['create', 'edit', 'show']);
        Route::resource('templates', \App\Http\Controllers\Web\PayrollTemplateController::class)->except(['show']);
        Route::resource('employees', \App\Http\Controllers\Web\EmployeeController::class)->except(['show']);
    });

    // Settings / User Management Routes (Super Admin Only)
    Route::middleware(['role:HR Admin'])->group(function () {
        Route::resource('roles', \App\Http\Controllers\Web\RoleController::class)->except(['show']);
        Route::resource('users', \App\Http\Controllers\Web\UserController::class)->except(['show']);
        Route::resource('permissions', \App\Http\Controllers\Web\PermissionController::class)->except(['show']);
    });
});

require __DIR__.'/auth.php';

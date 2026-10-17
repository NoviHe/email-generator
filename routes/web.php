<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailGeneratorController;
use App\Http\Controllers\InboxController;
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

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Email Generator
    Route::post('/emails/generate', [EmailGeneratorController::class, 'store'])->name('emails.generate');
    Route::delete('/emails/{generatedEmail}', [EmailGeneratorController::class, 'destroy'])->name('emails.destroy');

    // Inbox
    Route::get('/inbox/{generatedEmail}', [InboxController::class, 'index'])->name('inbox.index');
    Route::get('/inbox/{generatedEmail}/{receivedEmail}', [InboxController::class, 'show'])->name('inbox.show');
    Route::get('/inbox/{generatedEmail}/check', [InboxController::class, 'checkNew'])->name('inbox.check');
    Route::delete('/inbox/{generatedEmail}/{receivedEmail}', [InboxController::class, 'destroy'])->name('inbox.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Super Admin Routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [\App\Http\Controllers\AdminController::class, 'index'])->name('dashboard');
    Route::post('/users/{user}/toggle-admin', [\App\Http\Controllers\AdminController::class, 'toggleAdmin'])->name('users.toggle-admin');
    Route::delete('/users/{user}', [\App\Http\Controllers\AdminController::class, 'deleteUser'])->name('users.destroy');
    Route::delete('/emails/{generatedEmail}', [\App\Http\Controllers\AdminController::class, 'deleteGeneratedEmail'])->name('emails.destroy');
    Route::delete('/received-emails/{receivedEmail}', [\App\Http\Controllers\AdminController::class, 'deleteReceivedEmail'])->name('received-emails.destroy');
    Route::post('/sync-imap', [\App\Http\Controllers\AdminController::class, 'syncImap'])->name('sync-imap');
    Route::post('/cleanup-expired', [\App\Http\Controllers\AdminController::class, 'cleanupExpired'])->name('cleanup-expired');
});

require __DIR__.'/auth.php';

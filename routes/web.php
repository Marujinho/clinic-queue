<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::livewire('/login', 'pages::auth.login')
    ->name('login')
    ->middleware('guest');

Route::post('/logout', function () {
    Auth::logout();

    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::redirect('/', '/dashboard');

    Route::livewire('/dashboard', 'pages::dashboard.index')->name('dashboard');

    // Auto-load per-domain route files. Each domain owns its own file under
    // routes/domains/ so parallel work never conflicts in this file. Routes
    // defined there inherit this authenticated group.
    foreach (glob(base_path('routes/domains/*.php')) ?: [] as $domainRoutes) {
        require $domainRoutes;
    }
});

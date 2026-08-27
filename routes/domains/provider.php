<?php

use Illuminate\Support\Facades\Route;

// Provider management is admin-only (see the 'manage-providers' gate). The
// route middleware returns 403 for receptionists and providers.
Route::livewire('/providers', 'pages::provider.index')
    ->name('providers.index')
    ->middleware('can:manage-providers');

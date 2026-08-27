<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// Loaded inside the authenticated group from routes/web.php.
Route::livewire('/appointments', 'pages::appointment.index')
    ->name('appointments.index')
    ->middleware('can:view-appointments');

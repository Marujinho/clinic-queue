<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/patients', 'pages::patient.index')
    ->name('patients.index')
    ->middleware('can:view-patients');

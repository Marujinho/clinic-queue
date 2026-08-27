<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/queues', 'pages::queue.index')
    ->name('queues.index')
    ->middleware('can:manage-queues');

Route::livewire('/check-in', 'pages::queue.check-in')
    ->name('queue.check-in')
    ->middleware('can:check-in');

Route::livewire('/queue-board', 'pages::queue.board')
    ->name('queue.board');

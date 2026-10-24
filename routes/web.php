<?php

use App\Livewire\NagoyaMovementDashboard;
use Illuminate\Support\Facades\Route;

Route::get('/', NagoyaMovementDashboard::class)->name('home');
